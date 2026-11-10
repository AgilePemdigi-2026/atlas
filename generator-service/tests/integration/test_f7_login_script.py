"""F7 integration test: the generated script really logs in before the scenarios."""

from __future__ import annotations

import ast
import importlib.util
import os
import subprocess
import sys
from pathlib import Path
from types import ModuleType

import pytest
from support import SCENARIO_DIR, load_scenario

from testgen.codegen.selenium_generator import AuthSpec, generate_script


def _import_script(script: str, tmp_path: Path) -> ModuleType:
    path = tmp_path / "generated_auth_script.py"
    path.write_text(script, encoding="utf-8")

    spec = importlib.util.spec_from_file_location("generated_auth_script", path)
    assert spec is not None and spec.loader is not None

    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module

    try:
        spec.loader.exec_module(module)
    finally:
        sys.modules.pop(spec.name, None)

    return module


def _script_with_auth(base_url: str) -> str:
    payload = load_scenario(SCENARIO_DIR / "happy_path.json", base_url)
    return generate_script(
        payload,
        auth=AuthSpec(
            login_url=f"{base_url}/login.html",
            selectors=("id:email", "id:password"),
        ),
    )


def test_the_generated_script_is_valid_python(base_url: str) -> None:
    # Given a script generated with an auth profile
    script = _script_with_auth(base_url)

    # Then it parses as Python
    assert ast.parse(script) is not None
    # And it calls the login before the scenario loop
    assert script.index("atlas_login(driver)") < script.index("for index, run_scenario")


def test_the_generated_script_logs_in_before_running_the_scenario(
    base_url: str,
    tmp_path: Path,
) -> None:
    # Given a script generated with an auth profile
    script = _script_with_auth(base_url)
    script_path = tmp_path / "auth_script.py"
    script_path.write_text(script, encoding="utf-8")

    # When it runs with the credential values in the environment
    completed = subprocess.run(
        [sys.executable, str(script_path)],
        capture_output=True,
        text=True,
        timeout=180,
        env={
            **os.environ,
            "HEADLESS": "true",
            "ATLAS_AUTH_ID_EMAIL": "qa@example.test",
            "ATLAS_AUTH_ID_PASSWORD": "s3cr3t",
        },
        check=False,
    )

    # Then the scenario passed, so the login did not break the run
    assert completed.returncode == 0, completed.stderr
    assert "1 passed, 0 failed" in completed.stdout
    # And the login left no error behind
    assert "Login failed" not in completed.stdout + completed.stderr


def test_the_generated_script_reports_a_missing_environment_variable(
    base_url: str,
    tmp_path: Path,
) -> None:
    # Given a script generated with an auth profile
    script = _script_with_auth(base_url)
    script_path = tmp_path / "auth_script.py"
    script_path.write_text(script, encoding="utf-8")

    # When it runs without the credential value
    completed = subprocess.run(
        [sys.executable, str(script_path)],
        capture_output=True,
        text=True,
        timeout=180,
        env={**os.environ, "HEADLESS": "true"},
        check=False,
    )

    # Then it exits non-zero and says which variable is missing
    assert completed.returncode != 0
    assert "ATLAS_AUTH_ID_EMAIL" in completed.stdout + completed.stderr


def test_the_login_fills_the_form_with_the_environment_values(
    base_url: str,
    chrome_driver: object,
    tmp_path: Path,
    monkeypatch: pytest.MonkeyPatch,
) -> None:
    # Given a script generated with an auth profile
    script = _script_with_auth(base_url)
    script_path = tmp_path / "auth_script.py"
    script_path.write_text(script, encoding="utf-8")

    # And the credential values live only in the environment
    monkeypatch.setenv("ATLAS_AUTH_ID_EMAIL", "qa@example.test")
    monkeypatch.setenv("ATLAS_AUTH_ID_PASSWORD", "s3cr3t")

    # When the login runs in a real browser
    module = _import_script(script, tmp_path)
    module.atlas_login(chrome_driver)

    # Then the browser left the login page for the page behind the form
    assert not chrome_driver.current_url.endswith("/login.html")
    # And the form carried the email value from the environment
    assert "email=qa%40example.test" in chrome_driver.current_url


def test_the_environment_variable_names_match_the_selectors() -> None:
    # Given a selector with a prefix
    script = generate_script(
        {
            "scenarios": [
                {
                    "scenario": "S",
                    "steps": [
                        {
                            "stage": "Given",
                            "action": "navigate",
                            "value": "https://example.test/",
                        }
                    ],
                }
            ]
        },
        auth=AuthSpec(login_url="https://example.test/login", selectors=("id:email",)),
    )

    # Then the variable name is derived from the selector
    assert "ATLAS_AUTH_ID_EMAIL" in script
    # And the value is nowhere in the script
    assert "qa@example.test" not in script
