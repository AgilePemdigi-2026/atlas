"""F7/F10: Laravel -> Python request contract (F10 owns this transport)."""

from __future__ import annotations

from urllib.parse import urlparse

from pydantic import BaseModel, ConfigDict, Field, field_validator, model_validator


class ScenarioInput(BaseModel):
    """One scenario the Tester wants generated."""

    model_config = ConfigDict(extra="forbid")

    name: str = Field(min_length=1)
    scenario_url: str | None = None


_CREDENTIAL_STRATEGIES = frozenset({"id", "name", "css", "xpath"})


def _validate_selector(selector: str, *, field: str = "credentials") -> None:
    """Reject empty selectors and unknown ``strategy:`` prefixes loudly."""
    stripped = selector.strip()
    if not stripped:
        raise ValueError(f"{field} must not be empty")
    if ":" in stripped:
        strategy = stripped.split(":", 1)[0].strip()
        if strategy.lower() not in _CREDENTIAL_STRATEGIES:
            raise ValueError(
                f"{field} '{selector}' uses unknown strategy '{strategy}'; "
                "allowed: id, name, css, xpath or a plain key"
            )


class AuthProfileInput(BaseModel):
    """Login config for protected targets. Credentials stay in memory only.

    ``submit_selector`` and ``success_indicator`` use the same selector rules
    as ``credentials`` keys (``id:``/``name:``/``css:``/``xpath:`` prefix or a
    plain name→id lookup) and default to heuristic fallbacks when absent.
    """

    model_config = ConfigDict(extra="forbid")

    login_url: str | None = None
    credentials: dict[str, str] = Field(default_factory=dict)
    submit_selector: str | None = None
    success_indicator: str | None = None

    @model_validator(mode="after")
    def _check_login_url_and_credentials(self) -> AuthProfileInput:
        login_url = (self.login_url or "").strip()
        if login_url:
            parsed = urlparse(login_url)
            if parsed.scheme not in {"http", "https"} or not parsed.netloc:
                raise ValueError(
                    "auth_profile.login_url must be an absolute http or https URL"
                )
        credentials_present = bool(self.credentials)
        if bool(login_url) != credentials_present:
            raise ValueError(
                "auth_profile requires both login_url and at least one "
                "credential, or neither"
            )
        for selector, value in self.credentials.items():
            _validate_selector(selector)
            if not value.strip():
                raise ValueError(
                    f"auth_profile.credentials['{selector}'] must not be empty"
                )
        if self.submit_selector is not None:
            _validate_selector(self.submit_selector, field="submit_selector")
        if self.success_indicator is not None:
            _validate_selector(self.success_indicator, field="success_indicator")
        return self


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
