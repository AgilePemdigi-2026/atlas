"""F11 tests: what happens when the LLM keeps failing validation."""

from __future__ import annotations

import httpx
import pytest

from testgen.config import Settings
from testgen.llm.errors import LlmError
from testgen.llm.extractor import extract
from testgen.schemas.request import GenerateRequest


def _settings(**overrides: object) -> Settings:
    kwargs: dict[str, object] = {
        "kenari_api_key": "test-key",
        "service_api_key": "svc",
        "llm_base_url": "https://kenari.id/v1",
        "llm_model": "gpt-6-luna",
    }
    kwargs.update(overrides)
    return Settings(**kwargs)  # type: ignore[arg-type]


def _same_invalid_response() -> httpx.MockTransport:
    """Always returns malformed JSON to force the full F11 retry loop."""

    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(
            200,
            json={"choices": [{"message": {"content": "not json"}}]},
        )

    return httpx.MockTransport(handler)


def test_invalid_output_is_retried_then_fails() -> None:
    # Given the LLM keeps returning output that fails validation
    # When extraction runs
    request = GenerateRequest(user_story="As a user I want to login.")
    # Then it retries up to the configured maximum (hard cap 3)
    # And finally returns the error code "invalid_response"
    # And meta.attempts records the number of attempts
    with pytest.raises(LlmError) as excinfo:
        extract(request, _settings(), transport=_same_invalid_response())

    assert excinfo.value.code == "invalid_response"
    assert excinfo.value.attempts == 4
