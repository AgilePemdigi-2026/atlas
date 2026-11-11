"""F10/F12: POST /v1/generate — Laravel -> Python extraction + script generation."""

from __future__ import annotations

from fastapi import APIRouter, Depends, Request
from fastapi.responses import JSONResponse

from testgen.api.deps import require_service_key
from testgen.codegen.selenium_generator import AuthSpec, generate_script
from testgen.config import Settings, get_settings
from testgen.errors import ConversionError
from testgen.llm.errors import LlmError
from testgen.llm.extractor import error_response, extract
from testgen.schemas.request import AuthProfileInput, GenerateRequest
from testgen.schemas.response import GenerateResponse, ResponseError

__all__ = ["router"]

router = APIRouter()


@router.post("/v1/generate")
def generate(
    body: GenerateRequest,
    request: Request,
    _: None = Depends(require_service_key),
    settings: Settings = Depends(get_settings),  # noqa: B008 - FastAPI DI idiom
) -> JSONResponse:
    """Extract once, then render the Selenium script from the validated extraction."""
    transport = getattr(request.app.state, "llm_transport", None)
    try:
        result: GenerateResponse = extract(body, settings, transport=transport)
    except LlmError as error:
        payload = error_response(error, settings).model_dump()
        return JSONResponse(status_code=error.http_status, content=payload)

    if result.status == "ok" and result.data is not None:
        try:
            result.data["script"] = generate_script(
                result.data, auth=_auth_spec(body.auth_profile)
            )
        except ConversionError as error:
            payload = GenerateResponse(
                status="error",
                data=None,
                meta=result.meta,
                error=ResponseError(**error.to_dict()),
            ).model_dump()
            return JSONResponse(status_code=502, content=payload)

    return JSONResponse(status_code=200, content=result.model_dump())


def _auth_spec(profile: AuthProfileInput | None) -> AuthSpec | None:
    """Turn the request's auth profile into what the script needs.

    Only the login URL and the field selectors cross over: the credential
    values stay on the Laravel side and never reach the generated script.
    """
    if profile is None or not profile.login_url or not profile.credentials:
        return None

    return AuthSpec(
        login_url=profile.login_url,
        selectors=tuple(profile.credentials.keys()),
    )
