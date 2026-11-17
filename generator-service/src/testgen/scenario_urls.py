"""F8: scenario segmentation and starting-URL inference from acceptance criteria."""

from __future__ import annotations

import re
from pathlib import Path
from urllib.parse import urljoin, urlparse

import httpx

from testgen.config import Settings
from testgen.errors import EMPTY_SCENARIOS
from testgen.llm.client import KenariClient
from testgen.llm.errors import INVALID_INPUT, INVALID_RESPONSE, INVALID_URL, LlmError
from testgen.llm.extractor import parse_llm_json
from testgen.schemas.scenario_urls import (
    ScenarioUrlData,
    ScenarioUrlItem,
    ScenarioUrlMeta,
    ScenarioUrlRequest,
    ScenarioUrlResponse,
)

MAX_SCENARIOS = 20
MAX_NAME_LENGTH = 256

_SYSTEM_PROMPT = (Path(__file__).parent / "prompts" / "scenario_urls.txt").read_text(
    encoding="utf-8"
)


def _is_absolute_http_url(value: str) -> bool:
    parsed = urlparse(value)
    return parsed.scheme in {"http", "https"} and bool(parsed.netloc)


def _resolve_url(raw: object, base_url: str | None) -> tuple[str | None, str | None]:
    """Return ``(url, warning)``; the URL is ``None`` when it cannot be used."""
    if not isinstance(raw, str):
        return None, None

    value = raw.strip()
    if not value:
        return None, None

    if base_url and not _is_absolute_http_url(value):
        value = urljoin(base_url, value)

    if not _is_absolute_http_url(value):
        return None, "Dropped a scenario URL that is not absolute http or https."

    if base_url and urlparse(value).netloc.lower() != urlparse(base_url).netloc.lower():
        return None, "Dropped a scenario URL on a different origin."

    return value, None


def _normalise(
    items: list[object], base_url: str | None
) -> tuple[list[ScenarioUrlItem], list[str]]:
    warnings: list[str] = []
    seen: set[str] = set()
    result: list[ScenarioUrlItem] = []

    for raw in items:
        if not isinstance(raw, dict):
            continue
        name = raw.get("name")
        if not isinstance(name, str):
            continue
        name = re.sub(r"\s+", " ", name).strip()
        if not name:
            continue
        if len(name) > MAX_NAME_LENGTH:
            name = name[:MAX_NAME_LENGTH].rstrip()
            warnings.append(
                "A scenario name was longer than 256 characters and was truncated."
            )
        key = name.casefold()
        if key in seen:
            continue
        seen.add(key)
        scenario_url, url_warning = _resolve_url(raw.get("scenario_url"), base_url)
        if url_warning:
            warnings.append(url_warning)
        result.append(ScenarioUrlItem(name=name, scenario_url=scenario_url))

    if len(result) > MAX_SCENARIOS:
        result = result[:MAX_SCENARIOS]
        warnings.append("Scenario list was capped at 20.")

    return result, warnings


def _user_message(name: str | None, base_url: str | None, criteria: str) -> str:
    lines: list[str] = []
    if name and name.strip():
        lines.append(f"Test case name:\n{name.strip()}")
    if base_url:
        lines.append(f"Base URL:\n{base_url}")
    lines.append(f"Acceptance criteria:\n{criteria}")
    return "\n\n".join(lines)


def generate_scenario_urls(
    request: ScenarioUrlRequest,
    settings: Settings,
    transport: httpx.BaseTransport | None = None,
) -> ScenarioUrlResponse:
    """Split the criteria into scenarios and infer each scenario's start page."""
    criteria = request.acceptance_criteria.strip()
    if not criteria:
        raise LlmError(
            "Field 'acceptance_criteria' must be a non-empty string.",
            code=INVALID_INPUT,
            http_status=422,
            field="acceptance_criteria",
        )

    base_url = request.base_url.strip() if request.base_url else None
    if base_url and not _is_absolute_http_url(base_url):
        raise LlmError(
            "base_url must be an absolute http or https URL.",
            code=INVALID_URL,
            http_status=422,
            field="base_url",
        )

    user_message = _user_message(request.test_case_name, base_url, criteria)
    raw = KenariClient(settings, transport=transport).complete(
        _SYSTEM_PROMPT, user_message
    )
    parsed = parse_llm_json(raw)
    raw_items = parsed.get("scenarios")
    if not isinstance(raw_items, list):
        raise LlmError(
            "LLM output has no 'scenarios' list.",
            code=INVALID_RESPONSE,
            http_status=502,
            field="scenarios",
        )

    items, warnings = _normalise(raw_items, base_url)
    if not items:
        raise LlmError(
            "LLM returned no usable scenarios.",
            code=EMPTY_SCENARIOS,
            http_status=502,
            field="scenarios",
        )

    return ScenarioUrlResponse(
        status="ok",
        data=ScenarioUrlData(scenarios=items),
        meta=ScenarioUrlMeta(
            model=settings.llm_model,
            prompt_version=settings.prompt_version,
            warnings=warnings,
        ),
    )
