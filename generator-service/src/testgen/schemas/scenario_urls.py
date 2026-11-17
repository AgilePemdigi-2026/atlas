"""F8: scenario URL inference contract (Laravel -> Python)."""

from __future__ import annotations

from typing import Literal

from pydantic import BaseModel, ConfigDict, Field

from testgen.schemas.response import ResponseError


class ScenarioUrlRequest(BaseModel):
    """Acceptance criteria the Tester wants split into scenarios."""

    model_config = ConfigDict(extra="forbid")

    acceptance_criteria: str
    base_url: str | None = None
    test_case_name: str | None = None


class ScenarioUrlItem(BaseModel):
    """One inferred scenario and the page it starts on."""

    model_config = ConfigDict(extra="forbid")

    name: str = Field(min_length=1, max_length=256)
    scenario_url: str | None = None


class ScenarioUrlMeta(BaseModel):
    model_config = ConfigDict(extra="forbid")

    model: str = ""
    prompt_version: str = "v1"
    warnings: list[str] = Field(default_factory=list)


class ScenarioUrlData(BaseModel):
    model_config = ConfigDict(extra="forbid")

    scenarios: list[ScenarioUrlItem]


class ScenarioUrlResponse(BaseModel):
    """Envelope returned by ``POST /v1/scenario-urls``."""

    model_config = ConfigDict(extra="forbid")

    status: Literal["ok", "error"]
    data: ScenarioUrlData | None = None
    meta: ScenarioUrlMeta = Field(default_factory=ScenarioUrlMeta)
    error: ResponseError | None = None
