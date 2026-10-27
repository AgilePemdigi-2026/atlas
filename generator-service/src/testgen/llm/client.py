"""F10: Kenari chat-completions client (OpenAI-compatible).

Best practice: thin wrapper over httpx, no retry here (retry is F11's job),
no DB, no logging of secrets. Timeouts fixed at 30s per PRD section 12.
"""

from __future__ import annotations

from typing import Any

import httpx

from testgen.config import Settings
from testgen.llm.errors import (
    INVALID_RESPONSE,
    LLM_TIMEOUT,
    LLM_UNAVAILABLE,
    LlmError,
)

__all__ = ["KenariClient", "complete_json"]


class KenariClient:
    """POSTs chat-completions requests to Kenari and returns the raw text."""

    def __init__(
        self, settings: Settings, transport: httpx.BaseTransport | None = None
    ) -> None:
        self._settings = settings
        self._transport = transport

    def complete(self, system: str, user: str) -> str:
        if not self._settings.kenari_api_key:
            raise LlmError(
                "KENARI_API_KEY is not configured.",
                code=LLM_UNAVAILABLE,
                http_status=503,
                field="config",
            )
        payload = {
            "model": self._settings.llm_model,
            "messages": [
                {"role": "system", "content": system},
                {"role": "user", "content": user},
            ],
            "temperature": 0,
            "response_format": {"type": "json_object"},
        }
        try:
            with httpx.Client(
                transport=self._transport, timeout=self._settings.llm_timeout
            ) as client:
                response = client.post(
                    self._settings.chat_completions_url,
                    headers={
                        "Authorization": f"Bearer {self._settings.kenari_api_key}"
                    },
                    json=payload,
                )
        except httpx.TimeoutException as error:
            raise LlmError(
                "LLM request timed out.",
                code=LLM_TIMEOUT,
                http_status=504,
                field="llm",
            ) from error
        except httpx.HTTPError as error:
            raise LlmError(
                "LLM service is unreachable.",
                code=LLM_UNAVAILABLE,
                http_status=502,
                field="llm",
            ) from error
        if response.status_code >= 500:
            raise LlmError(
                "LLM service is unavailable.",
                code=LLM_UNAVAILABLE,
                http_status=502,
                field="llm",
            )
        if response.status_code != 200:
            raise LlmError(
                "LLM request was rejected.",
                code=LLM_UNAVAILABLE,
                http_status=502,
                field="llm",
            )
        try:
            body: Any = response.json()
            return str(body["choices"][0]["message"]["content"])
        except (ValueError, KeyError, IndexError, TypeError) as error:
            raise LlmError(
                "LLM returned an unexpected response shape.",
                code=INVALID_RESPONSE,
                http_status=502,
                field="llm",
            ) from error


def complete_json(system: str, user: str, settings: Settings) -> str:
    """One-shot helper used by tests and simple callers."""
    return KenariClient(settings).complete(system, user)
