from __future__ import annotations

import os
import subprocess
import sys
from pathlib import Path

import pytest
from support import load_scenario

from converter import convert

EXPECTATIONS = {
    "happy_path": 0,
    "failing_assertion": 1,
}


@pytest.mark.parametrize(("name", "expected_exit"), EXPECTATIONS.items())
def test_generated_script_exit_code(
    name: str,
    expected_exit: int,
    base_url: str,
    tmp_path: Path,
) -> None:
    payload = load_scenario(
        Path(__file__).parent / "fixtures" / "scenarios" / f"{name}.json",
        base_url,
    )
    result = convert(payload)

    assert result.status == "success", result.error

    script_path = tmp_path / f"{name}.py"
    script_path.write_text(result.script, encoding="utf-8")

    completed = subprocess.run(
        [sys.executable, str(script_path)],
        capture_output=True,
        text=True,
        timeout=180,
        env={**os.environ, "HEADLESS": "true"},
        check=False,
    )

    assert completed.returncode == expected_exit, completed.stderr
    assert "[ATLAS] result:" in completed.stdout

    if expected_exit == 0:
        assert "1 passed, 0 failed" in completed.stdout
    else:
        assert "FAIL" in completed.stdout
        assert "assert_text expected" in completed.stdout
