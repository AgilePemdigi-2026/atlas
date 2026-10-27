"""F10: shared auth dependency for Laravel -> Python calls."""

from __future__ import annotations

from fastapi import Depends, Header, HTTPException

from testgen.config import Settings, get_settings

__all__ = ["require_service_key"]


async def require_service_key(
    x_service_key: str | None = Header(default=None),
    settings: Settings = Depends(get_settings),  # noqa: B008 - FastAPI DI idiom
) -> None:
    """Reject calls without the internal service key (401) or misconfig (503)."""
    if not settings.service_api_key:
        raise HTTPException(status_code=503, detail="Service is not configured.")
    if x_service_key != settings.service_api_key:
        raise HTTPException(status_code=401, detail="Invalid service key.")
