from __future__ import annotations

import builtins
import json
import keyword
import re
from dataclasses import dataclass
from typing import Any
from urllib.parse import urlparse

from errors import (
    EMPTY_SCENARIOS,
    EMPTY_STEPS,
    INVALID_INPUT,
    INVALID_STAGE_ORDER,
    INVALID_URL,
    MISSING_FIELD,
    MISSING_NAVIGATION,
    UNSUPPORTED_ACTION,
    UNSUPPORTED_LOCATOR,
)
from models import ConversionError, ConversionResult

STAGE_ORDER = {"Given": 0, "When": 1, "Then": 2}

ACTIONS = {
    "navigate": frozenset({"value"}),
    "fill": frozenset({"by", "target", "value"}),
    "click": frozenset({"by", "target"}),
    "select": frozenset({"by", "target", "value"}),
    "assert_url": frozenset({"value"}),
    "assert_text": frozenset({"value"}),
}

LOCATORS = frozenset({"id", "name", "css", "xpath", "link_text", "partial_link_text"})

LOCATOR_CONSTANTS = {
    "id": "By.ID",
    "name": "By.NAME",
    "css": "By.CSS_SELECTOR",
    "xpath": "By.XPATH",
    "link_text": "By.LINK_TEXT",
    "partial_link_text": "By.PARTIAL_LINK_TEXT",
}

# Names the generated module defines or imports. A scenario function must not
# shadow them, or the generated script stops working.
RESERVED_NAMES = frozenset(
    {
        "os",
        "webdriver",
        "TimeoutException",
        "By",
        "EC",
        "Select",
        "WebDriverWait",
        "WAIT_TIMEOUT",
        "PAGE_LOAD_TIMEOUT",
        "wait_for",
        "assert_condition",
        "driver",
        "options",
        "index",
        "run_scenario",
        "passed",
        "failed",
    }
) | frozenset(dir(builtins))


HEADER = """import os

from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

WAIT_TIMEOUT = 10
PAGE_LOAD_TIMEOUT = 30


def wait_for(driver, by, target):
    return WebDriverWait(driver, WAIT_TIMEOUT).until(
        EC.visibility_of_element_located((by, target))
    )


def assert_condition(condition, scenario, action, expected):
    try:
        condition()
    except TimeoutException:
        raise AssertionError(
            f"{scenario}: {action} expected {expected!r}"
        ) from None"""

MAIN_TEMPLATE = """if __name__ == "__main__":
    options = webdriver.ChromeOptions()
    if os.environ.get("HEADLESS", "true").lower() != "false":
        options.add_argument("--headless=new")

    driver = webdriver.Chrome(options=options)
    driver.set_page_load_timeout(PAGE_LOAD_TIMEOUT)

    passed = 0
    failed = 0

    try:
        for index, run_scenario in enumerate(
            [
__SCENARIOS__
            ],
            start=1,
        ):
            try:
                run_scenario(driver)
            except AssertionError as error:
                failed += 1
                print(f"[ATLAS] scenario {index}: FAIL {error}", flush=True)
            except Exception as error:
                failed += 1
                print(f"[ATLAS] scenario {index}: ERROR {error}", flush=True)
                break
            else:
                passed += 1
                print(f"[ATLAS] scenario {index}: PASS", flush=True)
    finally:
        driver.quit()

    print(f"[ATLAS] result: {passed} passed, {failed} failed", flush=True)
    raise SystemExit(1 if failed else 0)"""


@dataclass(frozen=True)
class _Step:
    stage: str
    action: str
    by: str | None
    target: str | None
    value: str | None
    defaulted: bool


@dataclass(frozen=True)
class _Scenario:
    name: str
    function_name: str
    steps: list[_Step]


class _Invalid(Exception):
    def __init__(self, error: ConversionError) -> None:
        super().__init__(error.message)
        self.error = error


def convert(payload: Any) -> ConversionResult:
    """Convert one test case (a dict of scenarios) into a Selenium script."""
    try:
        scenarios = _parse(payload)
    except _Invalid as invalid:
        return ConversionResult.failure(invalid.error)

    return ConversionResult.success(_render(scenarios))


def _parse(payload: Any) -> list[_Scenario]:
    if not isinstance(payload, dict):
        _fail(INVALID_INPUT, "Input must be a JSON object.", field="input")

    _reject_unknown_keys(payload, {"scenarios"}, "input")

    if "scenarios" not in payload:
        _fail(MISSING_FIELD, "Field 'scenarios' is required.", field="scenarios")

    raw_scenarios = payload["scenarios"]

    if not isinstance(raw_scenarios, list):
        _fail(INVALID_INPUT, "Field 'scenarios' must be a list.", field="scenarios")

    if not raw_scenarios:
        _fail(EMPTY_SCENARIOS, "At least one scenario is required.", field="scenarios")

    parsed = [_parse_scenario(raw, index) for index, raw in enumerate(raw_scenarios)]
    function_names = _unique_function_names([name for name, _ in parsed])

    return [
        _Scenario(name=name, function_name=function_name, steps=steps)
        for (name, steps), function_name in zip(parsed, function_names, strict=True)
    ]


def _parse_scenario(raw: Any, index: int) -> tuple[str, list[_Step]]:
    field = f"scenarios[{index}]"

    if not isinstance(raw, dict):
        _fail(INVALID_INPUT, "Each scenario must be a JSON object.", field=field)

    _reject_unknown_keys(raw, {"scenario", "steps"}, field)

    if "scenario" not in raw:
        _fail(MISSING_FIELD, "Field 'scenario' is required.", field=f"{field}.scenario")

    name = raw["scenario"]

    if not isinstance(name, str) or not name.strip():
        _fail(
            INVALID_INPUT,
            "Field 'scenario' must be a non-empty string.",
            field=f"{field}.scenario",
        )

    if "steps" not in raw:
        _fail(MISSING_FIELD, "Field 'steps' is required.", field=f"{field}.steps")

    raw_steps = raw["steps"]

    if not isinstance(raw_steps, list):
        _fail(INVALID_INPUT, "Field 'steps' must be a list.", field=f"{field}.steps")

    if not raw_steps:
        _fail(
            EMPTY_STEPS,
            "Each scenario needs at least one step.",
            field=f"{field}.steps",
        )

    steps: list[_Step] = []
    last_stage = -1

    for step_index, raw_step in enumerate(raw_steps, start=1):
        step = _parse_step(raw_step, index, step_index, last_stage)
        last_stage = STAGE_ORDER[step.stage]
        steps.append(step)

    if steps[0].action != "navigate":
        _fail(
            MISSING_NAVIGATION,
            "The first step of each scenario must be 'navigate'.",
            step_index=1,
            field=f"{field}.steps[0].action",
        )

    return name, steps


def _parse_step(
    raw: Any,
    scenario_index: int,
    step_index: int,
    last_stage: int,
) -> _Step:
    field = f"scenarios[{scenario_index}].steps[{step_index - 1}]"

    if not isinstance(raw, dict):
        _fail(
            INVALID_INPUT,
            "Each step must be a JSON object.",
            step_index=step_index,
            field=field,
        )

    if "stage" not in raw:
        _fail(
            MISSING_FIELD,
            "Field 'stage' is required.",
            step_index=step_index,
            field=f"{field}.stage",
        )

    stage = raw["stage"]

    if not isinstance(stage, str) or stage not in STAGE_ORDER:
        _fail(
            INVALID_INPUT,
            "Field 'stage' must be Given, When, or Then.",
            step_index=step_index,
            field=f"{field}.stage",
        )

    if STAGE_ORDER[stage] < last_stage:
        _fail(
            INVALID_STAGE_ORDER,
            "Stages must follow Given, When, Then order.",
            step_index=step_index,
            field=f"{field}.stage",
        )

    if "action" not in raw:
        _fail(
            MISSING_FIELD,
            "Field 'action' is required.",
            step_index=step_index,
            field=f"{field}.action",
        )

    action = raw["action"]

    if not isinstance(action, str) or action not in ACTIONS:
        _fail(
            UNSUPPORTED_ACTION,
            f"Unsupported action {action!r}.",
            step_index=step_index,
            field=f"{field}.action",
        )

    required = ACTIONS[action]
    allowed = {"stage", "action"} | required

    if "value" in required:
        allowed.add("defaulted")

    for key in raw:
        if key not in allowed:
            _fail(
                INVALID_INPUT,
                f"Field {key!r} is not allowed for action {action!r}.",
                step_index=step_index,
                field=f"{field}.{key}",
            )

    for key in sorted(required):
        if key not in raw:
            _fail(
                MISSING_FIELD,
                f"Field {key!r} is required for action {action!r}.",
                step_index=step_index,
                field=f"{field}.{key}",
            )

        value = raw[key]

        if not isinstance(value, str):
            _fail(
                INVALID_INPUT,
                f"Field {key!r} must be a string.",
                step_index=step_index,
                field=f"{field}.{key}",
            )

        if not value.strip():
            _fail(
                MISSING_FIELD,
                f"Field {key!r} must be a non-empty string.",
                step_index=step_index,
                field=f"{field}.{key}",
            )

    by = raw.get("by")

    if by is not None and (not isinstance(by, str) or by not in LOCATORS):
        _fail(
            UNSUPPORTED_LOCATOR,
            f"Unsupported locator {by!r}.",
            step_index=step_index,
            field=f"{field}.by",
        )

    defaulted = raw.get("defaulted", False)

    if not isinstance(defaulted, bool):
        _fail(
            INVALID_INPUT,
            "Field 'defaulted' must be a boolean.",
            step_index=step_index,
            field=f"{field}.defaulted",
        )

    value = raw.get("value")

    if action in {"navigate", "assert_url"} and not _is_absolute_http_url(value):
        _fail(
            INVALID_URL,
            "URL must be an absolute http or https URL.",
            step_index=step_index,
            field=f"{field}.value",
        )

    return _Step(
        stage=stage,
        action=action,
        by=by,
        target=raw.get("target"),
        value=value,
        defaulted=defaulted,
    )


def _render(scenarios: list[_Scenario]) -> str:
    blocks = [HEADER]
    blocks.extend(
        _render_scenario(scenario, index)
        for index, scenario in enumerate(scenarios, start=1)
    )
    blocks.append(_render_main(scenarios))

    return "\n\n\n".join(block.rstrip("\n") for block in blocks) + "\n"


def _render_scenario(scenario: _Scenario, scenario_index: int) -> str:
    lines = [
        f"# Scenario: {_sanitize_comment(scenario.name)}",
        f"def {scenario.function_name}(driver):",
    ]
    blocks: list[str] = []
    previous_stage: str | None = None

    for step_index, step in enumerate(scenario.steps, start=1):
        block: list[str] = []

        if step.stage != previous_stage:
            block.append(f"    # {step.stage}")
            previous_stage = step.stage

        block.append(
            f'    print("[ATLAS] scenario {scenario_index} step {step_index}: '
            f'{step.action}", flush=True)'
        )
        block.extend(_render_step(step, scenario_index, scenario.name, step_index))
        blocks.append("\n".join(block))

    lines.append("\n\n".join(blocks))

    return "\n".join(lines)


def _render_step(
    step: _Step,
    scenario_index: int,
    scenario_name: str,
    step_index: int,
) -> list[str]:
    by = LOCATOR_CONSTANTS[step.by] if step.by is not None else None
    target = _py_string(step.target) if step.target is not None else None
    value = _py_string(step.value) if step.value is not None else None
    defaulted = "  # ATLAS DEFAULT" if step.defaulted else ""

    if step.action == "navigate":
        return [f"    driver.get({value}){defaulted}"]

    if step.action == "fill":
        element = f"element_{step_index}"
        return [
            f"    {element} = wait_for(driver, {by}, {target})",
            f"    {element}.clear()",
            f"    {element}.send_keys({value}){defaulted}",
        ]

    if step.action == "click":
        return [
            "    WebDriverWait(driver, WAIT_TIMEOUT).until(",
            f"        EC.element_to_be_clickable(({by}, {target}))",
            "    ).click()",
        ]

    if step.action == "select":
        element = f"element_{step_index}"
        return [
            f"    {element} = wait_for(driver, {by}, {target})",
            f"    Select({element}).select_by_value({value}){defaulted}",
        ]

    label = _py_string(
        f"scenario {scenario_index} ({_sanitize_comment(scenario_name)})"
    )

    if step.action == "assert_url":
        condition = f"            EC.url_to_be({value})"
    else:
        condition = (
            f"            EC.text_to_be_present_in_element("
            f'(By.TAG_NAME, "body"), {value})'
        )

    return [
        "    assert_condition(",
        "        lambda: WebDriverWait(driver, WAIT_TIMEOUT).until(",
        condition,
        "        ),",
        f"        {label},",
        f"        {_py_string(step.action)},",
        f"        {value},{defaulted}",
        "    )",
    ]


def _render_main(scenarios: list[_Scenario]) -> str:
    names = "\n".join(
        f"                {scenario.function_name}," for scenario in scenarios
    )

    return MAIN_TEMPLATE.replace("__SCENARIOS__", names)


def _unique_function_names(names: list[str]) -> list[str]:
    used: set[str] = set()
    result: list[str] = []

    for name in names:
        base = _slugify(name)
        candidate = base
        suffix = 2

        while candidate in used:
            candidate = f"{base}_{suffix}"
            suffix += 1

        used.add(candidate)
        result.append(candidate)

    return result


def _slugify(name: str) -> str:
    slug = re.sub(r"[^a-z0-9]+", "_", name.lower()).strip("_")

    if not slug:
        return "scenario"

    if slug[0].isdigit():
        slug = f"scenario_{slug}"

    if keyword.iskeyword(slug) or slug in RESERVED_NAMES:
        slug = f"{slug}_scenario"

    return slug


def _sanitize_comment(text: str) -> str:
    return "".join(
        " " if char in "\r\n" or ord(char) < 32 or ord(char) == 127 else char
        for char in text
    )


def _py_string(value: str) -> str:
    return json.dumps(value, ensure_ascii=False)


def _is_absolute_http_url(value: Any) -> bool:
    if not isinstance(value, str):
        return False

    parsed = urlparse(value)

    return parsed.scheme in {"http", "https"} and bool(parsed.netloc)


def _reject_unknown_keys(payload: dict, allowed: set[str], field: str) -> None:
    for key in payload:
        if key not in allowed:
            _fail(
                INVALID_INPUT,
                f"Unknown field {key!r}.",
                field=key if field == "input" else f"{field}.{key}",
            )


def _fail(
    code: str,
    message: str,
    *,
    step_index: int | None = None,
    field: str | None = None,
) -> None:
    raise _Invalid(
        ConversionError(
            code=code,
            message=message,
            step_index=step_index,
            field=field,
        )
    )
