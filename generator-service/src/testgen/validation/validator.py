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
_VALUED_ACTIONS = frozenset({"navigate", "fill", "select", "assert_url", "assert_text"})
_VOID_TAGS = frozenset(
    {
        "area",
        "base",
        "br",
        "col",
        "embed",
        "hr",
        "img",
        "input",
        "link",
        "meta",
        "param",
        "source",
        "track",
        "wbr",
    }
)
_XPATH_ATTR = re.compile(r"@([\w-]+)\s*=\s*(['\"])(.*?)\2")
_XPATH_TEXT = re.compile(r"(?<![\w-])text\(\)\s*=\s*(['\"])(.*?)\1")
_XPATH_NORMALIZED = re.compile(r"normalize-space\([^)]*\)\s*=\s*(['\"])(.*?)\1")
_XPATH_CONTAINS = re.compile(
    r"contains\(\s*(?:text\(\)|normalize-space\([^)]*\)|\.)\s*,\s*(['\"])(.*?)\1"
)
_XPATH_TAG = re.compile(r"(?:^|/)\s*([a-zA-Z][\w-]*|\*)")


@dataclass(frozen=True)
class ValidationResult:
    valid: bool
    data: dict[str, Any] | None = None
    errors: list[str] = field(default_factory=list)


@dataclass(frozen=True)
class _DomElement:
    tag: str
    attrs: dict[str, str]
    text: str = ""


def _normalize_text(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip()


class _DOMIndex(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self._tags: list[tuple[str, dict[str, str]]] = []
        self._texts: list[list[str]] = []
        self._open: list[int] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        tag = tag.lower()
        self._tags.append((tag, {k: v or "" for k, v in attrs}))
        self._texts.append([])
        if tag not in _VOID_TAGS:
            self._open.append(len(self._tags) - 1)

    def handle_data(self, data: str) -> None:
        if self._open:
            self._texts[self._open[-1]].append(data)

    def handle_endtag(self, tag: str) -> None:
        tag = tag.lower()
        for index in range(len(self._open) - 1, -1, -1):
            if self._tags[self._open[index]][0] == tag:
                del self._open[index:]
                return

    def elements(self) -> list[_DomElement]:
        return [
            _DomElement(tag, attrs, _normalize_text("".join(self._texts[index])))
            for index, (tag, attrs) in enumerate(self._tags)
        ]


def _dom_elements(dom: str) -> list[_DomElement]:
    """Read either raw HTML or F10's compact one-line DOM descriptors."""
    parser = _DOMIndex()
    if "<" in dom and ">" in dom:
        try:
            parser.feed(dom)
            elements = parser.elements()
            if elements:
                return elements
        except Exception:  # noqa: BLE001, S110 - malformed HTML falls back to line parsing
            pass
    elements: list[_DomElement] = []
    for line in dom.splitlines():
        match = re.match(r"^([a-zA-Z][\w-]*)\s*(.*)$", line.strip())
        if not match:
            continue
        attrs = {k: v for k, v in re.findall(r'([\w-]+)="([^"]*)"', match.group(2))}
        text = re.sub(r'[\w-]+="[^"]*"', "", match.group(2)).strip().strip('"')
        elements.append(
            _DomElement(match.group(1).lower(), attrs, _normalize_text(text))
        )
    return elements


def _css_matches(selector: str, element: _DomElement) -> bool:
    selector = selector.strip()
    if selector.startswith("#"):
        return element.attrs.get("id") == selector[1:]
    if selector.startswith("."):
        return selector[1:] in element.attrs.get("class", "").split()
    if re.fullmatch(r"[a-zA-Z][\w-]*", selector):
        return element.tag == selector.lower()
    if selector.startswith("[") and selector.endswith("]"):
        attr_match = re.fullmatch(r'\[([\w-]+)(?:=["\']?([^"\']+)["\']?)?\]', selector)
        if attr_match:
            key = attr_match.group(1)
            expected = attr_match.group(2)
            return key in element.attrs and (
                expected is None or element.attrs[key] == expected
            )
    return False


def _xpath_matches(target: str, element: _DomElement) -> bool:
    tags = _XPATH_TAG.findall(target)
    if tags and tags[-1] != "*" and element.tag != tags[-1].lower():
        return False
    for key, _, expected in _XPATH_ATTR.findall(target):
        if element.attrs.get(key) != expected:
            return False
    text = _normalize_text(element.text)
    for regex in (_XPATH_TEXT, _XPATH_NORMALIZED):
        for _, expected in regex.findall(target):
            if text != _normalize_text(expected):
                return False
    for _, expected in _XPATH_CONTAINS.findall(target):
        if _normalize_text(expected) not in text:
            return False
    return True


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
            if step.action not in _VALUED_ACTIONS and step.value is not None:
                errors.append(
                    f"{prefix}.value is not allowed for action {step.action!r}."
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
            matches: list[_DomElement] = []
            for element in dom_elements:
                matched = (
                    (step.by == "id" and element.attrs.get("id") == step.target)
                    or (step.by == "name" and element.attrs.get("name") == step.target)
                    or (step.by == "css" and _css_matches(step.target, element))
                    or (step.by == "xpath" and _xpath_matches(step.target, element))
                    # Compact DOM descriptors preserve link text in the line; raw
                    # HTML text is not indexed, so only accept if it is present.
                    or (
                        step.by in {"link_text", "partial_link_text"}
                        and element.tag == "a"
                        and step.target in (dom or "")
                    )
                )
                if matched:
                    matches.append(element)
            if not matches:
                errors.append(
                    f"{prefix}.target '{step.target}' was not found in the extracted DOM."
                )
                continue
            tags = {element.tag for element in matches}
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
