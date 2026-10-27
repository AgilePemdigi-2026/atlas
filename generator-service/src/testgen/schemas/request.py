"""F10: Laravel -> Python request contract (F10 owns this transport)."""

from __future__ import annotations

from pydantic import BaseModel, ConfigDict, Field, field_validator


class ScenarioInput(BaseModel):
    """One scenario the Tester wants generated."""

    model_config = ConfigDict(extra="forbid")

    name: str = Field(min_length=1)
    scenario_url: str | None = None


class AuthProfileInput(BaseModel):
    """Login config for protected targets. Credentials stay in memory only."""

    model_config = ConfigDict(extra="forbid")

    login_url: str | None = None
    credentials: dict[str, str] = Field(default_factory=dict)


class GenerateOptions(BaseModel):
    model_config = ConfigDict(extra="forbid")

    max_retries: int = 3


class GenerateRequest(BaseModel):
    """Body of POST /v1/generate. ``dom`` is an optional pre-cleaned snapshot."""

    model_config = ConfigDict(extra="forbid")

    test_case_id: int | None = None
    user_story: str = Field(min_length=1)
    acceptance_criteria: str | None = None
    base_url: str | None = None
    scenarios: list[ScenarioInput] = Field(default_factory=list)
    auth_profile: AuthProfileInput | None = None
    dom: str | None = None
    options: GenerateOptions = Field(default_factory=GenerateOptions)

    @field_validator("user_story")
    @classmethod
    def _story_non_empty(cls, value: str) -> str:
        if not value.strip():
            raise ValueError("user_story must be a non-empty string")
        return value
