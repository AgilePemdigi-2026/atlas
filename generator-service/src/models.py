from __future__ import annotations

from dataclasses import dataclass
from typing import Any, Literal


@dataclass(frozen=True)
class ConversionError:
    code: str
    message: str
    step_index: int | None = None
    field: str | None = None

    def to_dict(self) -> dict[str, Any]:
        return {
            "code": self.code,
            "message": self.message,
            "step_index": self.step_index,
            "field": self.field,
        }


@dataclass(frozen=True)
class ConversionResult:
    status: Literal["success", "error"]
    script: str | None = None
    error: ConversionError | None = None

    @classmethod
    def success(cls, script: str) -> ConversionResult:
        return cls(status="success", script=script)

    @classmethod
    def failure(cls, error: ConversionError) -> ConversionResult:
        return cls(status="error", error=error)
