"""F9 tests: one scenario test covering the DOM source tiers."""

from __future__ import annotations

import json
from typing import Any

import httpx

from testgen.config import Settings
from testgen.llm.extractor import extract
from testgen.schemas.request import GenerateRequest

HTML = "<html><body><input id='email' type='text'><button id='submit'>Kirim</button></body></html>"
EXTRACTION_JSON: dict[str, Any] = {
    "scenarios": [
        {
            "scenario": "Login Success",
            "steps": [
                {
                    "stage": "Given",
                    "action": "navigate",
                    "value": "http://127.0.0.1:8000/login",
                },
                {
                    "stage": "When",
                    "action": "fill",
                    "by": "id",
                    "target": "email",
                    "value": "qa@example.test",
                    "defaulted": True,
                },
                {
                    "stage": "Then",
                    "action": "assert_url",
                    "value": "http://127.0.0.1:8000/tutorial",
                },
            ],
        }
    ]
}


def _settings(**overrides: object) -> Settings:
    kwargs: dict[str, object] = {
        "kenari_api_key": "test-key",
        "service_api_key": "svc",
        "llm_base_url": "https://kenari.id/v1",
        "llm_model": "gpt-6-luna",
    }
    kwargs.update(overrides)
    return Settings(**kwargs)  # type: ignore[arg-type]


def _chat(content: str) -> httpx.Response:
    return httpx.Response(200, json={"choices": [{"message": {"content": content}}]})


def _transport(pages: str | None = None) -> httpx.MockTransport:
    chat = json.dumps(EXTRACTION_JSON)

    def handler(request: httpx.Request) -> httpx.Response:
        if request.method == "GET":
            if pages is None:
                return httpx.Response(200, text="")
            return httpx.Response(200, text=pages, headers={"content-type": "text/html"})
        return _chat(chat)

    return httpx.MockTransport(handler)


def test_dom_source_tiers() -> None:
    # Given a request with a "dom" snapshot
    snapshot_request = GenerateRequest(
        user_story="As a user I want to login.",
        scenarios=[{"name": "Login", "scenario_url": "http://127.0.0.1:8000/login"}],
        dom=HTML,
    )
    # Then meta.dom_source is "snapshot"
    snapshot = extract(snapshot_request, _settings(), transport=_transport())
    assert snapshot.meta.dom_source == "snapshot"

    # Given a request with a reachable URL and no snapshot
    live_request = GenerateRequest(
        user_story="As a user I want to login.",
        scenarios=[{"name": "Login", "scenario_url": "http://127.0.0.1:8000/login"}],
    )
    # Then meta.dom_source is "live"
    live = extract(live_request, _settings(), transport=_transport(HTML))
    assert live.meta.dom_source == "live"

    # Given a request with neither
    minimal_request = GenerateRequest(user_story="As a user I want to login.")
    # Then meta.dom_source is "minimal" and guessed targets are flagged "defaulted"
    minimal = extract(minimal_request, _settings(), transport=_transport())
    assert minimal.meta.dom_source == "minimal"
    assert minimal.meta.warnings != []
    assert minimal.data is not None
    assert minimal.data["scenarios"][0]["steps"][1]["defaulted"] is True
