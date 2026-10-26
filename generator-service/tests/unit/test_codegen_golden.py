from __future__ import annotations

import ast
import json
from pathlib import Path

import pytest
from support import GOLDEN_DIR

from testgen.codegen.selenium_generator import generate_script
from testgen.errors import ConversionError


def _cases() -> list[pytest.ParameterSet]:
    cases = []

    for path in sorted(GOLDEN_DIR.glob("*.json")):
        if path.name.endswith(".error.json"):
            continue

        expected_script = path.with_suffix(".py")
        expected_error = path.with_name(f"{path.stem}.error.json")

        if expected_script.exists():
            expected = expected_script
        elif expected_error.exists():
            expected = expected_error
        else:
            raise AssertionError(f"Missing expectation for {path.name}")

        cases.append(pytest.param(path, expected, id=path.stem))

    return cases


@pytest.mark.parametrize(("input_path", "expected_path"), _cases())
def test_golden(input_path: Path, expected_path: Path) -> None:
    payload = json.loads(input_path.read_text(encoding="utf-8"))

    if expected_path.suffix == ".py":
        script = generate_script(payload)
        assert script == expected_path.read_text(encoding="utf-8")
        assert ast.parse(script) is not None
        return

    with pytest.raises(ConversionError) as excinfo:
        generate_script(payload)

    assert excinfo.value.to_dict() == json.loads(
        expected_path.read_text(encoding="utf-8")
    )
