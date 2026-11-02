"""F11: JSON schema, Gherkin order, and DOM anti-hallucination validation."""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from html.parser import HTMLParser
from typing import Any
from urllib.parse import urlparse

from pydantic import ValidationError

from testgen.schemas.extraction import ExtractionPayload

_SUPPORTED_ACTIONS = {
    "navigate",
    "fill",
    "click",
    "select",
    "assert_url",
    "assert_text",
}
_SUPPORTED_LOCATORS = {"id", "name", "css", "xpath", "link_text", "partial_link_text"}
_STAGE_ORDER = {"Given": 0, "When": 1, "Then": 2}


@dataclass(frozen=True)
class ValidationResult:
    valid: bool
    data: dict[str, Any] | None = None
    errors: list[str] = field(default_factory=list)


class _DOMIndex(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.elements: list[tuple[str, dict[str, str]]] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        self.elements.append((tag.lower(), {k: v or "" for k, v in attrs}))

    def handle_startendtag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        self.handle_starttag(tag, attrs)


def _dom_elements(dom: str) -> list[tuple[str, dict[str, str]]]:
    """Read either raw HTML or F10's compact one-line DOM descriptors."""
    parser = _DOMIndex()
    if "<" in dom and ">" in dom:
        try:
            parser.feed(dom)
            if parser.elements:
                return parser.elements
        except Exception:
            pass
    elements: list[tuple[str, dict[str, str]]] = []
    for line in dom.splitlines():
        match = re.match(r"^([a-zA-Z][\w-]*)\s*(.*)$", line.strip())
        if not match:
            continue
        attrs = {k: v for k, v in re.findall(r'([\w-]+)="([^"]*)"', match.group(2))}
        elements.append((match.group(1).lower(), attrs))
    return elements


def _absolute_http_url(value: str) -> bool:
    parsed = urlparse(value)
    return parsed.scheme in {"http", "https"} and bool(parsed.netloc)


def validate_extraction(raw: Any, dom: str | None = None) -> ValidationResult:
    """Validate a parsed object; ``dom=None`` disables DOM checks for minimal mode."""
    errors: list[str] = []
    if not isinstance(raw, dict):
        return ValidationResult(False, errors=["Output must be a JSON object."])
    try:
        payload = ExtractionPayload.model_validate(raw)
    except ValidationError as error:
        for item in error.errors():
            location = (
                ".".join(str(part) for part in item.get("loc", ())) or "scenarios"
            )
            errors.append(f"{location}: {item.get('msg', 'invalid value')}")
        return ValidationResult(False, errors=errors)

    dom_elements = _dom_elements(dom or "") if dom is not None and dom.strip() else []
    dom_available = bool(dom_elements)
    for scenario_index, scenario in enumerate(payload.scenarios):
        previous_stage = -1
        if not scenario.steps:
            errors.append(f"scenarios[{scenario_index}].steps must not be empty.")
            continue
        first = scenario.steps[0]
        if first.stage != "Given":
            errors.append(f"scenarios[{scenario_index}].steps[0].stage must be Given.")
        if first.action != "navigate":
            errors.append(f"scenarios[{scenario_index}].steps[0] must navigate first.")
        elif not first.value or not _absolute_http_url(first.value):
            errors.append(
                f"scenarios[{scenario_index}].steps[0].value must be an absolute HTTP(S) URL."
            )
        for step_index, step in enumerate(scenario.steps):
            prefix = f"scenarios[{scenario_index}].steps[{step_index}]"
            stage = _STAGE_ORDER[step.stage]
            if stage < previous_stage:
                errors.append(f"{prefix}.stage must follow Given → When → Then order.")
            previous_stage = stage
            if step.action not in _SUPPORTED_ACTIONS:
                errors.append(f"{prefix}.action is unsupported.")
            if step.by is not None and step.by not in _SUPPORTED_LOCATORS:
                errors.append(f"{prefix}.by is unsupported.")
            if step.action in {"fill", "click", "select"}:
                if not step.by or not step.target:
                    errors.append(
                        f"{prefix} requires both by and target for {step.action}."
                    )
            elif step.by is not None or step.target is not None:
                errors.append(
                    f"{prefix}.by and target are only allowed for fill, click, or select."
                )
            if (
                step.action in {"navigate", "assert_url"}
                and step.value
                and not _absolute_http_url(step.value)
            ):
                errors.append(f"{prefix}.value must be an absolute HTTP(S) URL.")
            if not dom_available or step.action not in {"fill", "click", "select"}:
                continue
            assert step.by is not None and step.target is not None
            matches: list[tuple[str, dict[str, str]]] = []
            for tag, attrs in dom_elements:
                if step.by == "id" and attrs.get("id") == step.target or step.by == "name" and attrs.get("name") == step.target:
                    matches.append((tag, attrs))
                elif step.by == "css":
                    selector = step.target.strip()
                    if selector.startswith("#") and attrs.get("id") == selector[1:] or (
                        selector.startswith(".")
                        and selector[1:] in attrs.get("class", "").split()
                    ) or (
                        re.fullmatch(r"[a-zA-Z][\w-]*", selector)
                        and tag == selector.lower()
                    ):
                        matches.append((tag, attrs))
                    elif selector.startswith("[") and selector.endswith("]"):
                        attr_match = re.fullmatch(
                            r'\[([\w-]+)(?:=["\']?([^"\']+)["\']?)?\]', selector
                        )
                        if (
                            attr_match
                            and attr_match.group(1) in attrs
                            and (
                                attr_match.group(2) is None
                                or attrs[attr_match.group(1)] == attr_match.group(2)
                            )
                        ):
                            matches.append((tag, attrs))
                elif step.by == "xpath":
                    id_match = re.search(r"@id=['\"]([^'\"]+)['\"]", step.target)
                    name_match = re.search(r"@name=['\"]([^'\"]+)['\"]", step.target)
                    if id_match and attrs.get("id") == id_match.group(1) or name_match and attrs.get("name") == name_match.group(1):
                        matches.append((tag, attrs))
                elif step.by in {"link_text", "partial_link_text"} and tag == "a":
                    # Compact DOM descriptors preserve link text in the line; raw HTML
                    # text is not indexed, so only accept if the text is present in DOM.
                    if step.target in (dom or "") and (
                        step.by == "link_text" or step.target in (dom or "")
                    ):
                        matches.append((tag, attrs))
            if not matches:
                errors.append(
                    f"{prefix}.target '{step.target}' was not found in the extracted DOM."
                )
                continue
            tags = {tag for tag, _ in matches}
            if step.action == "fill" and not tags.intersection({"input", "textarea"}):
                errors.append(
                    f"{prefix}.action 'fill' requires an input or textarea element."
                )
            elif step.action == "select" and "select" not in tags:
                errors.append(f"{prefix}.action 'select' requires a select element.")
            elif step.action == "click" and not tags.intersection(
                {"a", "button", "input", "select", "textarea", "option"}
            ):
                errors.append(
                    f"{prefix}.action 'click' requires an interactive element."
                )

    if errors:
        return ValidationResult(False, errors=errors)
    return ValidationResult(True, data=payload.model_dump(exclude_none=True))
