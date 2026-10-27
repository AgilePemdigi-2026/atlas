"""F10: liveness probe."""

from __future__ import annotations

from fastapi import APIRouter

from testgen import __version__

__all__ = ["router"]

router = APIRouter()


@router.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok", "version": __version__}
