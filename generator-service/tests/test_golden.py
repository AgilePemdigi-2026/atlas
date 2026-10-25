from __future__ import annotations

import ast
import json
from pathlib import Path

import pytest

from converter import convert

GOLDEN_DIR = Path(__file__).parent / "golden"


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

    result = convert(payload)

    if expected_path.suffix == ".py":
        assert result.status == "success", result.error
        assert result.script == expected_path.read_text(encoding="utf-8")
        assert ast.parse(result.script) is not None
        return

    assert result.status == "error"
    assert result.script is None
    assert result.error.to_dict() == json.loads(
        expected_path.read_text(encoding="utf-8")
    )
