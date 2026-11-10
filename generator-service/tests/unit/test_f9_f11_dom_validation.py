"""F9/F11 tests: DOM cleaning, extraction from a protected page, and validation."""

from __future__ import annotations

import json

import httpx
import pytest

from testgen.config import Settings
from testgen.llm.errors import LlmError
from testgen.llm.extractor import clean_dom, extract
from testgen.schemas.request import GenerateRequest

PAGE_WITH_NOISE = """
<html>
<head><script>var tracker = 1;</script><style>.a{color:red}</style></head>
<body>
<svg width="10" height="10"><circle r="5"/></svg>
<canvas id="chart"></canvas>
<input id="email" name="email" type="text">
<button id="submit">Kirim</button>
</body>
</html>
"""

VALID_EXTRACTION = {
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
                },
            ],
        }
    ]
}

WRONG_STAGE_ORDER = {
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
                },
                {
                    "stage": "Given",
                    "action": "assert_text",
                    "value": "Welcome",
                },
            ],
        }
    ]
}

HALLUCINATED_TARGET = {
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
                    "target": "not_on_the_page",
                    "value": "qa@example.test",
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


def _transport(payload: dict) -> httpx.MockTransport:
    """An LLM that always answers with the given extraction."""

    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(
            200,
            json={"choices": [{"message": {"content": json.dumps(payload)}}]},
        )

    return httpx.MockTransport(handler)


def test_non_visual_elements_are_removed_from_the_dom() -> None:
    # Given a page with script, style, svg, and canvas elements
    # When the DOM is cleaned
    text, capped = clean_dom(PAGE_WITH_NOISE)

    # Then none of them are in the cleaned DOM
    assert "tracker" not in text
    assert "color:red" not in text
    assert "<svg" not in text
    assert "<canvas" not in text
    # And the interactive elements are still there
    assert "email" in text
    assert "submit" in text
    assert capped is False


def test_the_dom_is_extracted_from_a_protected_page() -> None:
    # Given the target requires a login and an auth profile is supplied
    from testgen.dom.extractor import extract_dom

    visited: list[str] = []

    class FakeElement:
        def __init__(self, target: str) -> None:
            self.target = target

        def clear(self) -> None:
            pass

        def send_keys(self, value: str) -> None:
            pass

    class FakeDriver:
        page_source = "<html><body><input id='secret'></body></html>"
        current_url = "http://127.0.0.1:8000/protected"

        def set_page_load_timeout(self, value: float) -> None:
            pass

        def get(self, url: str) -> None:
            visited.append(url)

        def find_element(self, by: str, target: str) -> FakeElement:
            return FakeElement(target)

        def find_elements(self, by: str, target: str) -> list[FakeElement]:
            return []

        def quit(self) -> None:
            pass

    class FakeAuthProfile:
        def __init__(self) -> None:
            self.login_url = "http://127.0.0.1:8000/login"
            self.credentials = {"id:email": "qa@example.test"}

    # When the DOM is extracted
    snapshot = extract_dom(
        "http://127.0.0.1:8000/protected",
        auth_profile=FakeAuthProfile(),
        driver=FakeDriver(),
    )

    # Then the login happens before the page is read
    assert visited == [
        "http://127.0.0.1:8000/login",
        "http://127.0.0.1:8000/protected",
    ]
    # And the extracted DOM is the page behind the login form
    assert "secret" in snapshot.html


def test_stage_order_must_follow_given_when_then() -> None:
    # Given the LLM returns a step that goes back to "Given" after a "When"
    request = GenerateRequest(
        user_story="As a user I want to log in.",
        scenarios=[{"name": "Login", "scenario_url": "http://127.0.0.1:8000/login"}],
    )

    # When extraction runs
    # Then the output is rejected
    with pytest.raises(LlmError) as excinfo:
        extract(request, _settings(), transport=_transport(WRONG_STAGE_ORDER))

    # And the LLM is asked again with the reason
    assert excinfo.value.code == "invalid_response"
    assert "stage must follow" in excinfo.value.message


def test_a_target_that_is_not_in_the_dom_is_rejected() -> None:
    # Given the LLM returns a step pointing at an element the page does not have
    request = GenerateRequest(
        user_story="As a user I want to log in.",
        scenarios=[{"name": "Login", "scenario_url": "http://127.0.0.1:8000/login"}],
        dom="<html><body><input id='email'></body></html>",
    )

    # When extraction runs
    # Then the output is rejected as a hallucinated target
    with pytest.raises(LlmError) as excinfo:
        extract(request, _settings(), transport=_transport(HALLUCINATED_TARGET))

    assert excinfo.value.code == "invalid_response"
    assert "not_on_the_page" in excinfo.value.message
