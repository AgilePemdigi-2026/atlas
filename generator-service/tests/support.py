from __future__ import annotations

import json
from pathlib import Path

import pytest

FIXTURES = Path(__file__).parent / "fixtures"
APP_DIR = FIXTURES / "app"
SCENARIO_DIR = FIXTURES / "scenarios"
GOLDEN_DIR = FIXTURES / "golden"


def load_scenario(path: Path, base_url: str) -> dict:
    return json.loads(
        path.read_text(encoding="utf-8").replace("__BASE_URL__", base_url)
    )


def scenario_cases() -> list[pytest.ParameterSet]:
    cases: list[pytest.ParameterSet] = []

    for path in sorted(SCENARIO_DIR.glob("*.json")):
        payload = json.loads(path.read_text(encoding="utf-8"))

        for index in range(len(payload["scenarios"])):
            cases.append(pytest.param(path, index, id=f"{path.stem}-{index}"))

    return cases
