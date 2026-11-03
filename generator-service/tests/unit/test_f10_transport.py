"""F10 transport tests: auth gate and the POST /v1/generate contract."""

from __future__ import annotations

import json

import httpx
import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient

from testgen import __version__
from testgen.api.routes.generate import router as generate_router
from testgen.api.routes.health import router as health_router
from testgen.config import Settings, get_settings

EXTRACTION = {
    "scenarios": [
        {
            "scenario": "Login",
            "steps": [
                {
                    "stage": "Given",
                    "action": "navigate",
                    "value": "http://127.0.0.1:8000/login",
                },
                {
                    "stage": "Then",
                    "action": "assert_url",
                    "value": "http://127.0.0.1:8000/login",
                },
            ],
        }
    ]
}


def _transport() -> httpx.MockTransport:
    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(
            200,
            json={"choices": [{"message": {"content": json.dumps(EXTRACTION)}}]},
        )

    return httpx.MockTransport(handler)


def _client(transport: httpx.BaseTransport | None = None) -> TestClient:
    app = FastAPI()
    app.include_router(health_router)
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


def test_health() -> None:
    response = _client().get("/health")

    assert response.status_code == 200
    assert response.json() == {"status": "ok", "version": __version__}


def test_generate_requires_service_key() -> None:
    response = _client(_transport()).post(
        "/v1/generate", json={"user_story": "As a user I want to login."}
    )

    assert response.status_code == 401


def test_generate_ok_envelope() -> None:
    response = _client(_transport()).post(
        "/v1/generate",
        json={"user_story": "As a user I want to login."},
        headers={"X-Service-Key": "svc"},
    )

    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "ok"
    assert body["data"]["scenarios"][0]["scenario"] == "Login"
    assert body["data"]["scenarios"][0]["steps"][0]["action"] == "navigate"
    assert body["meta"]["dom_source"] == "minimal"
    assert body["meta"]["model"] == "gpt-6-luna"


def test_generate_empty_story_is_422() -> None:
    response = _client(_transport()).post(
        "/v1/generate",
        json={"user_story": "  "},
        headers={"X-Service-Key": "svc"},
    )

    assert response.status_code == 422


def test_generate_llm_error_stays_in_envelope() -> None:
    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(500, json={"error": "boom"})

    response = _client(httpx.MockTransport(handler)).post(
        "/v1/generate",
        json={"user_story": "As a user I want to login."},
        headers={"X-Service-Key": "svc"},
    )

    assert response.status_code == 502
    assert response.json()["status"] == "error"
    assert response.json()["error"]["code"] == "llm_unavailable"


def test_timeout_after_live_fetch_keeps_live_dom_source_and_502() -> None:
    dom = "<html><body><button id='go'>Go</button></body></html>"

    def handler(request: httpx.Request) -> httpx.Response:
        if request.method == "GET":
            return httpx.Response(200, text=dom, headers={"content-type": "text/html"})
        raise httpx.TimeoutException("LLM timed out")

    response = _client(httpx.MockTransport(handler)).post(
        "/v1/generate",
        json={
            "user_story": "As a user I want to login.",
            "scenarios": [
                {"name": "Login", "scenario_url": "https://example.test/login"}
            ],
        },
        headers={"X-Service-Key": "svc"},
    )

    assert response.status_code == 502
    body = response.json()
    assert body["status"] == "error"
    assert body["error"]["code"] == "llm_timeout"
    assert body["meta"]["dom_source"] == "live"


def test_generate_includes_generated_script() -> None:
    response = _client(_transport()).post(
        "/v1/generate",
        json={"user_story": "As a user I want to login."},
        headers={"X-Service-Key": "svc"},
    )

    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "ok"
    assert "from selenium import webdriver" in body["data"]["script"]
    assert "def login" in body["data"]["script"]


def test_generate_codegen_failure_is_enveloped(monkeypatch) -> None:
    from testgen.api.routes import generate as generate_module
    from testgen.errors import ConversionError

    def boom(payload: object) -> str:
        raise ConversionError(code="missing_navigation", message="No navigation step.")

    monkeypatch.setattr(generate_module, "generate_script", boom)

    response = _client(_transport()).post(
        "/v1/generate",
        json={"user_story": "As a user I want to login."},
        headers={"X-Service-Key": "svc"},
    )

    assert response.status_code == 502
    body = response.json()
    assert body["status"] == "error"
    assert body["error"]["code"] == "missing_navigation"


def test_settings_defaults_match_team_decision() -> None:
    settings = Settings(_env_file=None)

    assert settings.chat_completions_url == "https://kenari.id/v1/chat/completions"
    assert settings.llm_model == "gpt-6-luna"
    assert settings.llm_timeout == pytest.approx(30.0)
