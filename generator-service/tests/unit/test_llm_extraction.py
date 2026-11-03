"""F10 tests: extractor tiers, Kenari client, and HTTP transport (all mocked)."""

from __future__ import annotations

import json

import httpx
import pytest

from testgen.codegen.selenium_generator import generate_script
from testgen.config import Settings
from testgen.llm.errors import LlmError
from testgen.llm.extractor import clean_dom, extract
from testgen.schemas.request import GenerateRequest

EXTRACTION_JSON = {
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
                    "target": "id_username",
                    "value": "user-imam",
                },
                {
                    "stage": "When",
                    "action": "click",
                    "by": "id",
                    "target": "btn_login",
                },
                {
                    "stage": "Then",
                    "action": "assert_url",
                    "value": "http://127.0.0.1:8000/tutorial",
                },
                {
                    "stage": "Then",
                    "action": "assert_text",
                    "value": "Tutorial",
                },
            ],
        }
    ]
}

HTML = """
<html><head><title>t</title><script>var x = 1;</script><style>.a{}</style></head>
<body><form>
<input id="id_username" name="u" type="text" placeholder="Username">
<button id="btn_login">Login</button>
</form></body></html>
"""


def _settings(**overrides: object) -> Settings:
    kwargs: dict[str, object] = {
        "kenari_api_key": "test-key",
        "service_api_key": "svc",
        "llm_base_url": "https://kenari.id/v1",
        "llm_model": "gpt-6-luna",
    }
    kwargs.update(overrides)
    return Settings(**kwargs)  # type: ignore[arg-type]


def _llm_transport(payload: object, status: int = 200) -> httpx.MockTransport:
    def handler(request: httpx.Request) -> httpx.Response:
        assert request.url.path == "/v1/chat/completions"
        assert request.headers["authorization"] == "Bearer test-key"
        assert json.loads(request.content)["model"] == "gpt-6-luna"
        return httpx.Response(status, json=payload)

    return httpx.MockTransport(handler)


def test_clean_dom_strips_non_visual_and_keeps_interactive() -> None:
    text, capped = clean_dom(HTML)

    assert "script" not in text
    assert 'input id="id_username"' in text
    assert "btn_login" in text
    assert capped is False


def test_clean_dom_caps_element_count() -> None:
    html = "<body>" + "".join(
        f'<input id="f{i}" name="f{i}" type="text">' for i in range(300)
    )
    text, capped = clean_dom(html + "</body>")

    assert capped is True
    assert len(text.splitlines()) == 200


def test_extract_live_tier_end_to_end() -> None:
    body = json.dumps(EXTRACTION_JSON)
    transport = httpx.MockTransport(
        lambda request: (
            httpx.Response(
                200,
                text=HTML,
                headers={"content-type": "text/html"},
            )
            if request.method == "GET"
            else httpx.Response(
                200,
                json={"choices": [{"message": {"content": body}}]},
            )
        )
    )
    request = GenerateRequest(
        user_story="As a user I want to login.",
        scenarios=[{"name": "Login", "scenario_url": "http://127.0.0.1:8000/login"}],
    )
    result = extract(request, _settings(), transport=transport)

    assert result.status == "ok"
    assert result.meta.dom_source == "live"
    assert result.data is not None
    assert generate_script(result.data)  # F12 consumes the raw envelope
    assert result.data["scenarios"][0]["scenario"] == "Login Success"
    assert result.data["scenarios"][0]["steps"][0]["action"] == "navigate"
    assert result.meta.model == "gpt-6-luna"
    # Output flows straight into the F12 converter.
    assert "def login_success(driver):" in generate_script(result.data)


def test_extract_snapshot_tier_skips_fetch() -> None:
    seen: list[str] = []

    def handler(request: httpx.Request) -> httpx.Response:
        seen.append(request.method)
        return httpx.Response(
            200,
            json={"choices": [{"message": {"content": json.dumps(EXTRACTION_JSON)}}]},
        )

    request = GenerateRequest(
        user_story="As a user I want to login.",
        scenarios=[{"name": "Login", "scenario_url": "http://example.test/login"}],
        dom=HTML,
    )
    result = extract(request, _settings(), transport=httpx.MockTransport(handler))

    assert result.meta.dom_source == "snapshot"
    assert seen == ["POST"]


def test_extract_minimal_tier_without_url() -> None:
    request = GenerateRequest(user_story="As a user I want to login.")
    result = extract(
        request,
        _settings(),
        transport=_llm_transport(
            {"choices": [{"message": {"content": json.dumps(EXTRACTION_JSON)}}]}
        ),
    )

    assert result.meta.dom_source == "minimal"
    assert result.meta.warnings != []


def test_extract_rejects_relative_url() -> None:
    request = GenerateRequest(
        user_story="As a user I want to login.",
        scenarios=[{"name": "Login", "scenario_url": "/login"}],
    )
    with pytest.raises(LlmError) as excinfo:
        extract(request, _settings(), transport=_llm_transport({}))

    assert excinfo.value.to_dict()["code"] == "invalid_url"


def test_extract_rejects_bad_llm_json() -> None:
    request = GenerateRequest(user_story="As a user I want to login.")
    transport = _llm_transport({"choices": [{"message": {"content": "not json"}}]})
    with pytest.raises(LlmError) as excinfo:
        extract(request, _settings(), transport=transport)

    assert excinfo.value.to_dict()["code"] == "invalid_response"


def test_client_requires_api_key() -> None:
    request = GenerateRequest(user_story="As a user I want to login.")
    with pytest.raises(LlmError) as excinfo:
        extract(request, _settings(kenari_api_key=""))

    assert excinfo.value.http_status == 503


def _sequence_transport(responses: list[httpx.Response]) -> httpx.MockTransport:
    """Return each canned response in order, repeating the last one."""
    remaining = list(responses)

    def handler(request: httpx.Request) -> httpx.Response:
        return remaining.pop(0) if len(remaining) > 1 else remaining[0]

    return httpx.MockTransport(handler)


def _chat(content: str) -> httpx.Response:
    return httpx.Response(200, json={"choices": [{"message": {"content": content}}]})


def test_extract_retries_invalid_json_and_reports_attempts() -> None:
    transport = _sequence_transport(
        [_chat("not json"), _chat(json.dumps(EXTRACTION_JSON))]
    )
    request = GenerateRequest(user_story="As a user I want to login.", dom=HTML)

    result = extract(request, _settings(), transport=transport)

    assert result.status == "ok"
    assert result.meta.attempts == 2


def test_extract_stops_after_max_retries_and_keeps_meta() -> None:
    transport = _sequence_transport([_chat("not json")])
    request = GenerateRequest(user_story="As a user I want to login.")

    with pytest.raises(LlmError) as excinfo:
        extract(request, _settings(max_retries=1), transport=transport)

    assert excinfo.value.code == "invalid_response"
    assert excinfo.value.attempts == 2
    assert excinfo.value.dom_source == "minimal"


def test_extract_clamps_negative_retries_to_a_single_attempt() -> None:
    transport = _sequence_transport([_chat("not json")])
    request = GenerateRequest(
        user_story="As a user I want to login.",
        options={"max_retries": -5},
    )

    with pytest.raises(LlmError) as excinfo:
        extract(request, _settings(), transport=transport)

    assert excinfo.value.attempts == 1


def test_extract_retries_when_validation_rejects_the_target() -> None:
    """A target missing from the DOM is a validation error, so it retries."""
    bad = {
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
                        "stage": "When",
                        "action": "click",
                        "by": "id",
                        "target": "tidak_ada_di_dom",
                    },
                ],
            }
        ]
    }
    transport = _sequence_transport(
        [_chat(json.dumps(bad)), _chat(json.dumps(EXTRACTION_JSON))]
    )
    request = GenerateRequest(user_story="As a user I want to login.", dom=HTML)

    result = extract(request, _settings(), transport=transport)

    assert result.status == "ok"
    assert result.meta.attempts == 2
