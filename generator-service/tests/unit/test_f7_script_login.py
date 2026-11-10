"""F7 tests: the generated script logs in before it runs the scenarios."""

from __future__ import annotations

import ast
import json

from fastapi import FastAPI
from fastapi.testclient import TestClient
from httpx import MockTransport

from testgen.api.routes.generate import router as generate_router
from testgen.codegen.selenium_generator import AuthSpec, generate_script
from testgen.config import Settings, get_settings

EXTRACTION = {
    "scenarios": [
        {
            "scenario": "Dashboard",
            "steps": [
                {
                    "stage": "Given",
                    "action": "navigate",
                    "value": "https://example.test/dashboard",
                },
                {
                    "stage": "Then",
                    "action": "assert_text",
                    "value": "Welcome",
                },
            ],
        }
    ]
}

CREDENTIALS = {"id:email": "qa@example.com", "password": "s3cr3t"}


def _transport() -> MockTransport:
    def handler(request: object) -> object:
        return json_response(EXTRACTION)

    return MockTransport(handler)


def json_response(payload: dict) -> object:
    import httpx

    return httpx.Response(
        200,
        json={"choices": [{"message": {"content": json.dumps(payload)}}]},
    )


def _client(transport: MockTransport | None = None) -> TestClient:
    app = FastAPI()
    app.include_router(generate_router)
    if transport is not None:
        app.state.llm_transport = transport
    app.dependency_overrides[get_settings] = lambda: Settings(
        kenari_api_key="test-key",
        service_api_key="svc",
        llm_base_url="https://kenari.id/v1",
        llm_model="gpt-6-luna",
    )
    return TestClient(app)


def test_the_script_logs_in_before_the_scenarios() -> None:
    # Given an extraction and an auth profile
    script = generate_script(
        EXTRACTION,
        auth=AuthSpec(
            login_url="https://app.example.com/login",
            selectors=("id:email", "password"),
        ),
    )

    # Then the script defines a login step
    assert "def atlas_login(driver):" in script
    # And the login runs before the first scenario
    assert script.index("atlas_login(driver)") < script.index("def dashboard(driver):")
    # And it opens the login URL
    assert 'driver.get("https://app.example.com/login")' in script
    # And it is valid Python
    assert ast.parse(script) is not None


def test_the_script_never_contains_the_credential_values() -> None:
    # Given an extraction and an auth profile
    script = generate_script(
        EXTRACTION,
        auth=AuthSpec(
            login_url="https://app.example.com/login",
            selectors=("id:email", "password"),
        ),
    )

    # Then no credential value appears in the script
    assert "qa@example.com" not in script
    assert "s3cr3t" not in script
    # But the selectors do, so the Tester knows which fields to fill
    assert "id:email" in script
    assert "password" in script


def test_the_script_names_the_environment_variables_it_needs() -> None:
    # Given an extraction and an auth profile
    script = generate_script(
        EXTRACTION,
        auth=AuthSpec(
            login_url="https://app.example.com/login",
            selectors=("id:email", "password"),
        ),
    )

    # Then the script tells the Tester which variables to export
    assert "ATLAS_AUTH_ID_EMAIL" in script
    assert "ATLAS_AUTH_PASSWORD" in script


def test_a_script_without_auth_has_no_login_step() -> None:
    # Given an extraction without an auth profile
    script = generate_script(EXTRACTION)

    # Then the script has no login step
    assert "def atlas_login(driver):" not in script
    assert "atlas_login(driver)" not in script


def test_two_credentials_with_the_same_slug_get_distinct_variables() -> None:
    # Given two selectors that slug to the same name
    script = generate_script(
        EXTRACTION,
        auth=AuthSpec(
            login_url="https://app.example.com/login",
            selectors=("id:email", "id-email"),
        ),
    )

    # Then the variables are distinct
    assert "ATLAS_AUTH_ID_EMAIL" in script
    assert "ATLAS_AUTH_ID_EMAIL_2" in script


def test_the_api_never_returns_the_credential_values() -> None:
    # Given a request carrying an auth profile
    response = _client(_transport()).post(
        "/v1/generate",
        json={
            "user_story": "As a user I want to log in.",
            "auth_profile": {
                "login_url": "https://app.example.com/login",
                "credentials": CREDENTIALS,
            },
        },
        headers={"X-Service-Key": "svc"},
    )

    # Then the response is ok
    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "ok"
    # And the script it returns has no credential value
    script = body["data"]["script"]
    assert "qa@example.com" not in script
    assert "s3cr3t" not in script
    # And no credential value appears anywhere in the response
    assert "qa@example.com" not in response.text
    assert "s3cr3t" not in response.text
    # And the script does log in
    assert "def atlas_login(driver):" in script
