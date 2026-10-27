"""F10 errors: transport and LLM-response failures.

Shapes follow the F12 ``ConversionError`` dicts
(``{code, message, step_index, field}``) so the F13 error-handling layer can
map them without inventing a new format. F13 owns the user-facing copy;
messages here stay technical and never include secrets.
"""

from __future__ import annotations

from typing import Any

from testgen.errors import INVALID_INPUT, INVALID_URL, TestgenError

LLM_TIMEOUT = "llm_timeout"
LLM_UNAVAILABLE = "llm_unavailable"
INVALID_RESPONSE = "invalid_response"

__all__ = [
    "INVALID_INPUT",
    "INVALID_RESPONSE",
    "INVALID_URL",
    "LLM_TIMEOUT",
    "LLM_UNAVAILABLE",
    "LlmError",
]


class LlmError(TestgenError):
    """Failure of the F10 extraction step (input, network, or LLM output)."""

    def __init__(
        self,
        message: str,
        *,
        code: str = "internal_error",
        http_status: int = 502,
        step_index: int | None = None,
        field: str | None = None,
    ) -> None:
        super().__init__(message, code=code)
        self.http_status = http_status
        self.step_index = step_index
        self.field = field

    def to_dict(self) -> dict[str, Any]:
        return {
            "code": self.code,
            "message": self.message,
            "step_index": self.step_index,
            "field": self.field,
        }
