"""F10 settings: LLM provider and internal service configuration.

Only F10 settings live here. F9 (DOM/Selenium) and F13 (queue, CORS, …)
extend this module when their features land. Values always come from the
environment — this file only declares names and safe defaults.
"""

from __future__ import annotations

from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Environment-driven configuration for the generator service (F10)."""

    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    llm_base_url: str = "https://kenari.id/v1"
    llm_model: str = "gpt-6-luna"
    kenari_api_key: str = ""
    service_api_key: str = ""
    llm_timeout: float = 30.0
    max_retries: int = 3
    prompt_version: str = "v1"

    @property
    def chat_completions_url(self) -> str:
        """Full Kenari chat-completions endpoint (OpenAI-compatible)."""
        return f"{self.llm_base_url.rstrip('/')}/chat/completions"


@lru_cache
def get_settings() -> Settings:
    """Process-wide cached settings (override via FastAPI dependencies in tests)."""
    return Settings()
