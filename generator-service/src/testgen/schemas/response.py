"""F10: Python -> Laravel response contract."""

from __future__ import annotations

from typing import Any, Literal

from pydantic import BaseModel, ConfigDict, Field


class ResponseMeta(BaseModel):
    model_config = ConfigDict(extra="forbid")

    model: str = ""
    prompt_version: str = "v1"
    attempts: int = 1
    dom_source: Literal["live", "snapshot", "minimal"] = "minimal"
    warnings: list[str] = Field(default_factory=list)


class ResponseError(BaseModel):
    """Same shape as F12 ConversionError dicts so F13 maps one format."""

    model_config = ConfigDict(extra="forbid")

    code: str
    message: str
    step_index: int | None = None
    field: str | None = None


class GenerateResponse(BaseModel):
    model_config = ConfigDict(extra="forbid")

    status: Literal["ok", "error"]
    data: dict[str, Any] | None = None
    meta: ResponseMeta = Field(default_factory=ResponseMeta)
    error: ResponseError | None = None
