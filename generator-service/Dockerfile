# syntax=docker/dockerfile:1

# F9 implementation: headless Chrome/Chromium image for DOM extraction.
FROM python:3.14-slim AS runtime

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    UV_PROJECT_ENVIRONMENT=/opt/venv \
    UV_LINK_MODE=copy \
    PATH="/opt/venv/bin:$PATH"

RUN apt-get update \
    && apt-get install -y --no-install-recommends chromium chromium-driver \
    && rm -rf /var/lib/apt/lists/* \
    && ln -sf /usr/bin/chromium /usr/bin/google-chrome \
    && ln -sf /usr/bin/chromium /usr/bin/chrome

COPY --from=ghcr.io/astral-sh/uv:0.13.7 /uv /uvx /bin/

WORKDIR /app

# Dependency layer: only rebuilds when the lockfile changes.
COPY pyproject.toml uv.lock ./
RUN --mount=type=cache,target=/root/.cache/uv \
    uv sync --frozen --no-install-project

COPY . .
RUN --mount=type=cache,target=/root/.cache/uv \
    uv sync --frozen \
    && groupadd --system --gid 1000 appuser \
    && useradd --system --uid 1000 --gid appuser --create-home --shell /usr/sbin/nologin appuser

USER appuser

EXPOSE 8001

CMD ["uvicorn", "testgen.main:app", "--host", "0.0.0.0", "--port", "8001"]
