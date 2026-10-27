"""F10: user story + DOM -> structured extraction via Kenari LLM.

Tiered DOM strategy (F9 stopgap, stdlib only — no Selenium, no JS render,
no auto-login): fetch the target page live and strip it, else use a
caller-provided snapshot, else run minimal with every guess defaulted.
The chosen source is always reported as ``dom_source`` so callers know
how much to trust the output.

No retry here (retry is F11's job) and no full JSON validation (also F11):
one attempt, defensively parsed, then F12 revalidates downstream.
"""

from __future__ import annotations

import json
import re
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse

import httpx
from pydantic import ValidationError

from testgen.config import Settings
from testgen.llm.client import KenariClient
from testgen.llm.errors import (
    INVALID_INPUT,
    INVALID_RESPONSE,
    INVALID_URL,
    LlmError,
)
from testgen.schemas.extraction import ExtractionPayload
from testgen.schemas.request import GenerateRequest
from testgen.schemas.response import GenerateResponse, ResponseError, ResponseMeta

__all__ = ["clean_dom", "extract"]

MAX_ELEMENTS = 200
MAX_TEXT_CHARS = 80

_SKIP_TAGS = frozenset({"script", "style", "svg", "canvas", "noscript", "head"})
_INTERACTIVE_TAGS = frozenset(
    {"a", "button", "input", "select", "textarea", "option", "label", "form"}
)
_KEPT_ATTRS = ("id", "name", "type", "placeholder", "href", "value", "aria-label")


class _Cleaner(HTMLParser):
    """Collects interactive elements as compact one-line descriptors."""

    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self._skip = 0
        self.elements: list[str] = []
        self.capped = False
        self._prefix: str | None = None
        self._buf: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        tag = tag.lower()
        if tag in _SKIP_TAGS:
            self._skip += 1
            return
        if self._skip:
            return
        self._flush()
        if len(self.elements) >= MAX_ELEMENTS:
            self.capped = True
            return
        found = dict(attrs)
        if tag in _INTERACTIVE_TAGS or found.get("id") or found.get("name"):
            parts = [tag]
            for key in _KEPT_ATTRS:
                raw = (found.get(key) or "").strip()
                if raw:
                    parts.append(f'{key}="{raw[:60]}"')
            self._prefix = " ".join(parts)

    def handle_data(self, data: str) -> None:
        if self._prefix is not None and not self._skip:
            self._buf.append(data)

    def handle_endtag(self, tag: str) -> None:
        if tag.lower() in _SKIP_TAGS:
            self._skip = max(0, self._skip - 1)
            return
        self._flush()

    def close(self) -> None:
        self._flush()
        super().close()

    def _flush(self) -> None:
        if self._prefix is None:
            self._buf.clear()
            return
        text = re.sub(r"\s+", " ", "".join(self._buf).strip())
        line = self._prefix
        if text:
            line += f' "{text[:MAX_TEXT_CHARS]}"'
        self.elements.append(line)
        self._prefix = None
        self._buf.clear()


def clean_dom(html: str) -> tuple[str, bool]:
    """Strip a page to interactive-element lines. Returns (text, capped)."""
    cleaner = _Cleaner()
    cleaner.feed(html)
    cleaner.close()
    return "\n".join(cleaner.elements), cleaner.capped


def _target_url(request: GenerateRequest) -> tuple[str | None, str]:
    for index, scenario in enumerate(request.scenarios):
        if scenario.scenario_url and scenario.scenario_url.strip():
            return (
                scenario.scenario_url.strip(),
                f"scenarios[{index}].scenario_url",
            )
    if request.base_url and request.base_url.strip():
        return request.base_url.strip(), "base_url"
    return None, ""


def _is_absolute_http_url(value: str) -> bool:
    parsed = urlparse(value)
    return parsed.scheme in {"http", "https"} and bool(parsed.netloc)


def _fetch_html(
    url: str, field: str, settings: Settings, transport: httpx.BaseTransport | None
) -> str:
    try:
        with httpx.Client(transport=transport, timeout=settings.llm_timeout) as client:
            response = client.get(url, follow_redirects=True)
    except httpx.TimeoutException as error:
        raise LlmError(
            "Target URL timed out.",
            code=INVALID_URL,
            http_status=502,
            field=field,
        ) from error
    except httpx.HTTPError as error:
        raise LlmError(
            "Target URL is unreachable.",
            code=INVALID_URL,
            http_status=502,
            field=field,
        ) from error
    if response.status_code != 200:
        raise LlmError(
            "Target URL is unreachable.",
            code=INVALID_URL,
            http_status=502,
            field=field,
        )
    return response.text


def _resolve_dom(
    request: GenerateRequest,
    settings: Settings,
    transport: httpx.BaseTransport | None,
) -> tuple[str, str, list[str]]:
    """Returns (dom_text, dom_source, warnings)."""
    if request.dom and request.dom.strip():
        text, capped = clean_dom(request.dom)
        warnings = ["Element list was capped."] if capped else []
        return text, "snapshot", warnings
    url, field = _target_url(request)
    if url is None:
        return (
            "",
            "minimal",
            ["No DOM available; guessed targets are marked defaulted."],
        )
    if not _is_absolute_http_url(url):
        raise LlmError(
            "URL must be an absolute http or https URL.",
            code=INVALID_URL,
            http_status=422,
            field=field,
        )
    text, capped = clean_dom(_fetch_html(url, field, settings, transport))
    warnings = ["Element list was capped."] if capped else []
    return text, "live", warnings


def _user_message(request: GenerateRequest, dom_text: str, dom_source: str) -> str:
    lines = [f"User story:\n{request.user_story.strip()}"]
    if request.acceptance_criteria and request.acceptance_criteria.strip():
        lines.append(f"Acceptance criteria:\n{request.acceptance_criteria.strip()}")
    urls = [
        s.scenario_url.strip()
        for s in request.scenarios
        if s.scenario_url and s.scenario_url.strip()
    ]
    if request.base_url and request.base_url.strip():
        urls.append(request.base_url.strip())
    if urls:
        lines.append("Target URLs:\n" + "\n".join(f"- {url}" for url in urls))
    if request.auth_profile and request.auth_profile.login_url:
        lines.append(
            "Login URL (open it first if the flow needs auth): "
            f"{request.auth_profile.login_url.strip()}"
        )
    if dom_source == "minimal":
        lines.append("DOM: (none provided — guess targets, mark defaulted true)")
    else:
        lines.append(f"DOM ({dom_source}, cleaned):\n{dom_text or '(no elements)'}")
    return "\n\n".join(lines)


def _parse_llm_json(raw: str) -> dict:
    text = raw.strip()
    if text.startswith("```"):
        text = re.sub(r"^```[a-zA-Z]*\n?", "", text)
        text = re.sub(r"\n?```$", "", text).strip()
    try:
        data = json.loads(text)
    except ValueError as error:
        raise LlmError(
            "LLM did not return valid JSON.",
            code=INVALID_RESPONSE,
            http_status=502,
            field="llm",
        ) from error
    if not isinstance(data, dict):
        raise LlmError(
            "LLM did not return a JSON object.",
            code=INVALID_RESPONSE,
            http_status=502,
            field="llm",
        )
    if "scenarios" in data:
        return data
    if "scenario" in data and "steps" in data:
        return {"scenarios": [data]}  # shortcut: single-scenario SRS shape
    raise LlmError(
        "LLM output has no 'scenarios'.",
        code=INVALID_RESPONSE,
        http_status=502,
        field="scenarios",
    )


def extract(
    request: GenerateRequest,
    settings: Settings,
    transport: httpx.BaseTransport | None = None,
) -> GenerateResponse:
    """Run one F10 extraction attempt. Raises LlmError on any failure."""
    if not request.user_story.strip():
        raise LlmError(
            "Field 'user_story' must be a non-empty string.",
            code=INVALID_INPUT,
            http_status=422,
            field="user_story",
        )
    dom_text, dom_source, warnings = _resolve_dom(request, settings, transport)
    raw = KenariClient(settings, transport=transport).complete(
        _SYSTEM_PROMPT, _user_message(request, dom_text, dom_source)
    )
    try:
        payload = ExtractionPayload.model_validate(_parse_llm_json(raw))
    except ValidationError as error:
        first = error.errors()[0]
        field = ".".join(str(part) for part in first["loc"]) or "scenarios"
        raise LlmError(
            "LLM output is not valid extraction JSON.",
            code=INVALID_RESPONSE,
            http_status=502,
            field=field,
        ) from error
    return GenerateResponse(
        status="ok",
        data={"scenarios": _dump_scenarios(payload)},
        meta=ResponseMeta(
            model=settings.llm_model,
            prompt_version=settings.prompt_version,
            attempts=1,
            dom_source=dom_source,  # type: ignore[arg-type]
            warnings=warnings,
        ),
    )


def _load_system_prompt() -> str:
    return (Path(__file__).resolve().parent / "prompts" / "extraction.txt").read_text(
        encoding="utf-8"
    )


_SYSTEM_PROMPT = _load_system_prompt()


def _dump_scenarios(payload: ExtractionPayload) -> list[dict]:
    """Envelope rows for F12: drop ``None`` and ``defaulted: False`` (F12 rejects both)."""
    scenarios: list[dict] = []
    for scenario in payload.model_dump(exclude_none=True)["scenarios"]:
        steps = [
            {key: value for key, value in step.items() if value is not False}
            for step in scenario["steps"]
        ]
        scenarios.append({"scenario": scenario["scenario"], "steps": steps})
    return scenarios


def error_response(error: LlmError, settings: Settings) -> GenerateResponse:
    """Build the uniform error envelope (F13 maps codes to user copy)."""
    return GenerateResponse(
        status="error",
        meta=ResponseMeta(
            model=settings.llm_model, prompt_version=settings.prompt_version
        ),
        error=ResponseError(**error.to_dict()),
    )
