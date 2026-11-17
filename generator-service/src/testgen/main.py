"""F10: FastAPI application factory (F10 owns this transport)."""

from __future__ import annotations

from fastapi import FastAPI

from testgen.api.routes.generate import router as generate_router
from testgen.api.routes.health import router as health_router
from testgen.api.routes.scenario_urls import router as scenario_urls_router

__all__ = ["app", "create_app"]


def create_app() -> FastAPI:
    app = FastAPI(title="generator-service")
    app.include_router(health_router)
    app.include_router(generate_router)
    app.include_router(scenario_urls_router)
    return app


app = create_app()
