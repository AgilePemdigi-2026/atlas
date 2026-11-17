"""F8: POST /v1/scenario-urls — Laravel -> Python scenario inference."""

from __future__ import annotations

from fastapi import APIRouter, Depends, Request
from fastapi.responses import JSONResponse

from testgen.api.deps import require_service_key
from testgen.config import Settings, get_settings
from testgen.llm.errors import LlmError
from testgen.scenario_urls import generate_scenario_urls
from testgen.schemas.response import ResponseError
from testgen.schemas.scenario_urls import (
    ScenarioUrlMeta,
    ScenarioUrlRequest,
    ScenarioUrlResponse,
)

__all__ = ["router"]

router = APIRouter()


@router.post("/v1/scenario-urls")
def scenario_urls(
    body: ScenarioUrlRequest,
    request: Request,
    _: None = Depends(require_service_key),
    settings: Settings = Depends(get_settings),  # noqa: B008 - FastAPI DI idiom
) -> JSONResponse:
    """Split the criteria into scenarios and infer each scenario's start page."""
    transport = getattr(request.app.state, "llm_transport", None)
    try:
        result = generate_scenario_urls(body, settings, transport=transport)
    except LlmError as error:
        payload = ScenarioUrlResponse(
            status="error",
            meta=ScenarioUrlMeta(
                model=settings.llm_model,
                prompt_version=settings.prompt_version,
            ),
            error=ResponseError(**error.to_dict()),
        )
        return JSONResponse(status_code=error.http_status, content=payload.model_dump())

    return JSONResponse(status_code=200, content=result.model_dump())
