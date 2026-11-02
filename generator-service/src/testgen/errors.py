from __future__ import annotations

from typing import Any

INVALID_INPUT = "invalid_input"
MISSING_FIELD = "missing_field"
UNSUPPORTED_ACTION = "unsupported_action"
UNSUPPORTED_LOCATOR = "unsupported_locator"
INVALID_STAGE_ORDER = "invalid_stage_order"
EMPTY_STEPS = "empty_steps"
EMPTY_SCENARIOS = "empty_scenarios"
MISSING_NAVIGATION = "missing_navigation"
INVALID_URL = "invalid_url"


class TestgenError(Exception):
    def __init__(self, message: str, *, code: str = "internal_error") -> None:
        super().__init__(message)
        self.message = message
        self.code = code

    def to_dict(self) -> dict[str, Any]:
        return {"code": self.code, "message": self.message}


class ConversionError(TestgenError):
    def __init__(
        self,
        *,
        code: str,
        message: str,
        step_index: int | None = None,
        field: str | None = None,
    ) -> None:
        super().__init__(message, code=code)
        self.step_index = step_index
        self.field = field

    def to_dict(self) -> dict[str, Any]:
        return {
            "code": self.code,
            "message": self.message,
            "step_index": self.step_index,
            "field": self.field,
        }
