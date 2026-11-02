"""F10: POST /v1/generate — Laravel -> Python extraction endpoint."""

from __future__ import annotations

from fastapi import APIRouter, Depends, Request
from fastapi.responses import JSONResponse

from testgen.api.deps import require_service_key
from testgen.config import Settings, get_settings
from testgen.llm.errors import LlmError
from testgen.llm.extractor import error_response, extract
from testgen.schemas.request import GenerateRequest
from testgen.schemas.response import GenerateResponse

__all__ = ["router"]

router = APIRouter()


@router.post("/v1/generate")
def generate(
    body: GenerateRequest,
    request: Request,
    _: None = Depends(require_service_key),
    settings: Settings = Depends(get_settings),  # noqa: B008 - FastAPI DI idiom
) -> JSONResponse:
    """Run one extraction attempt; domain errors stay in the envelope."""
    transport = getattr(request.app.state, "llm_transport", None)
    try:
        result: GenerateResponse = extract(body, settings, transport=transport)
    except LlmError as error:
        payload = error_response(error, settings).model_dump()
        return JSONResponse(status_code=error.http_status, content=payload)
    return JSONResponse(status_code=200, content=result.model_dump())
