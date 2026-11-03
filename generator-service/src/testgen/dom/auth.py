# F7/F9 implementation: auth profile login.
"""F9 helper for using the supplied authentication profile before extraction."""

from __future__ import annotations

from typing import Any


def _find_element(driver: Any, selector: str) -> Any:
    """Resolve explicit ``strategy:selector`` keys; default to name then id."""
    from selenium.webdriver.common.by import By

    strategies = {
        "id": By.ID,
        "name": By.NAME,
        "css": By.CSS_SELECTOR,
        "xpath": By.XPATH,
    }
    if ":" in selector:
        strategy, value = selector.split(":", 1)
        if strategy.lower() in strategies and value:
            return driver.find_element(strategies[strategy.lower()], value)
    try:
        return driver.find_element(By.NAME, selector)
    except Exception:  # noqa: BLE001 - plain keys fall back from name to id
        return driver.find_element(By.ID, selector)


def login_with_profile(driver: Any, auth_profile: Any) -> None:
    """Open login URL and fill configured selector/value credentials.

    Credential keys may be ``id:email``, ``name:email``, ``css:input[name=email]``
    or ``xpath://...``. Plain keys are resolved by name then id. The final field
    receives Enter to submit the form; credentials are never logged or returned.
    """
    login_url = getattr(auth_profile, "login_url", None)
    credentials = getattr(auth_profile, "credentials", {}) or {}
    if not login_url:
        return
    driver.get(login_url)
    last_element = None
    for selector, value in credentials.items():
        element = _find_element(driver, str(selector))
        element.clear()
        element.send_keys(str(value))
        last_element = element
    if last_element is not None:
        try:
            from selenium.webdriver.common.keys import Keys

            last_element.send_keys(Keys.ENTER)
        except Exception:  # noqa: BLE001, S110 - leave the failure to later steps
            # A page may use a non-standard submit flow; leave the failure visible
            # to the subsequent target-page/validation steps rather than hiding it.
            pass
