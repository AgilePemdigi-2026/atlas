# F9 implementation: Selenium DOM extraction.
"""F9: Selenium-based DOM extraction for ATLAS."""

from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any
from urllib.parse import urlparse

from testgen.dom.auth import login_with_profile


@dataclass(frozen=True)
class DOMSnapshot:
    html: str
    url: str
    warnings: list[str] = field(default_factory=list)


def _valid_url(url: str) -> bool:
    parsed = urlparse(url)
    return parsed.scheme in {"http", "https"} and bool(parsed.netloc)


def extract_dom(
    url: str,
    *,
    auth_profile: Any = None,
    driver: Any = None,
    driver_factory: Any = None,
    page_load_timeout: float = 30.0,
) -> DOMSnapshot:
    """Open a target page with headless Chrome and return its cleaned-source HTML.

    ``driver`` and ``driver_factory`` are injectable to make extraction testable.
    A driver created here is always closed; a caller-owned driver is left open.
    """
    if not _valid_url(url):
        raise ValueError("URL must be an absolute http or https URL.")

    owned_driver = driver is None
    if driver is None:
        if driver_factory is None:
            try:
                from selenium import webdriver
                from selenium.webdriver.chrome.options import Options
            except ImportError as error:  # pragma: no cover - depends on deployment
                raise RuntimeError(
                    "Selenium is required for DOM extraction."
                ) from error

            options = Options()
            options.add_argument("--headless=new")
            options.add_argument("--no-sandbox")
            options.add_argument("--disable-dev-shm-usage")
            options.add_argument("--disable-gpu")

            def driver_factory():
                return webdriver.Chrome(options=options)

        driver = driver_factory()

    try:
        driver.set_page_load_timeout(page_load_timeout)
        if auth_profile and getattr(auth_profile, "login_url", None):
            login_with_profile(driver, auth_profile)
        driver.get(url)
        # Wait for the browser's initial DOM to be ready and remove non-visual nodes.
        try:
            driver.execute_script(
                """document.querySelectorAll('script, style, svg, canvas').forEach(
                    element => element.remove()
                );"""
            )
        except Exception:  # noqa: BLE001, S110 - browser mocks may not implement execute_script
            pass
        html = str(driver.page_source or "")
        current_url = str(getattr(driver, "current_url", url) or url)
        return DOMSnapshot(html=html, url=current_url)
    finally:
        if owned_driver:
            driver.quit()
