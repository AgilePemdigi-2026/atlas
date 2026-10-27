"""F10/F11: structured LLM extraction JSON (Pydantic side of the F12 contract).

This mirrors the rule-based converter's input contract
(``testgen.codegen.selenium_generator``) so anything produced here is shaped
for F12. Structural rules that need cross-field context (stage order, first
step ``navigate``, absolute URLs) stay owned by F12 — F12 revalidates and is
the single source of truth until the F11 validator lands.
"""

from __future__ import annotations

from typing import Literal

from pydantic import BaseModel, ConfigDict, Field, field_validator, model_validator

Stage = Literal["Given", "When", "Then"]
Action = Literal["navigate", "fill", "click", "select", "assert_url", "assert_text"]
Locator = Literal["id", "name", "css", "xpath", "link_text", "partial_link_text"]

_VALUED_ACTIONS = frozenset({"navigate", "fill", "select", "assert_url", "assert_text"})
_TARGETED_ACTIONS = frozenset({"fill", "click", "select"})


class Step(BaseModel):
    """One executable step. Field requirements depend on ``action`` (F12 rules)."""

    model_config = ConfigDict(extra="forbid")

    stage: Stage
    action: Action
    by: Locator | None = None
    target: str | None = None
    value: str | None = None
    defaulted: bool = False

    @field_validator("target", "value")
    @classmethod
    def _non_empty(cls, value: str | None) -> str | None:
        if value is not None and not value.strip():
            raise ValueError("must be a non-empty string")
        return value

    @model_validator(mode="after")
    def _required_by_action(self) -> Step:
        required: dict[str, tuple[str, ...]] = {
            "navigate": ("value",),
            "fill": ("by", "target", "value"),
            "click": ("by", "target"),
            "select": ("by", "target", "value"),
            "assert_url": ("value",),
            "assert_text": ("value",),
        }
        for field_name in required[self.action]:
            if getattr(self, field_name) in (None, ""):
                raise ValueError(
                    f"field {field_name!r} is required for {self.action!r}"
                )
        if self.defaulted and self.action not in _VALUED_ACTIONS:
            raise ValueError(f"'defaulted' is not allowed for {self.action!r}")
        return self


class Scenario(BaseModel):
    """One named scenario with at least one step."""

    model_config = ConfigDict(extra="forbid")

    scenario: str = Field(min_length=1)
    steps: list[Step] = Field(min_length=1)

    @field_validator("scenario")
    @classmethod
    def _scenario_non_empty(cls, value: str) -> str:
        if not value.strip():
            raise ValueError("must be a non-empty string")
        return value


class ExtractionPayload(BaseModel):
    """Top-level F10 output: the exact shape F12 consumes."""

    model_config = ConfigDict(extra="forbid")

    scenarios: list[Scenario] = Field(min_length=1)
