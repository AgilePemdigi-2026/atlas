# F7/F9 implementation: auth profile login.
"""F7/F9: Selenium login for the supplied auth profile before extraction."""

from __future__ import annotations

from typing import Any

__all__ = ["LOGIN_TIMEOUT", "AuthError", "login_with_profile"]

# How long the login form may take to render, submit, and settle.
LOGIN_TIMEOUT = 10.0

_STRATEGIES = ("id", "name", "css", "xpath")


class AuthError(RuntimeError):
    """The supplied Auth Profile could not log in to the target.

    The message never carries a credential value; it only says what to check.
    """


def _parse_selector(selector: str) -> tuple[str, str]:
    """Split ``strategy:selector`` keys; plain keys fall back name→id.

    Raises ``AuthError`` for unknown strategies so a typo fails loudly.
    """
    stripped = selector.strip()
    if not stripped:
        raise AuthError("Credential selector must not be empty.")
    if ":" in stripped:
        strategy, value = stripped.split(":", 1)
        if strategy.lower() in _STRATEGIES:
            if not value:
                raise AuthError(f"Credential selector '{selector}' has no target.")
            return strategy.lower(), value
        raise AuthError(
            f"Credential selector '{selector}' uses unknown strategy "
            f"'{strategy}'; allowed: id, name, css, xpath or a plain key."
        )
    return "name_or_id", stripped


def _locator_tuple(selector: str) -> tuple[Any, str]:
    """Return the ``(By.X, target)`` tuple for one selector."""
    from selenium.webdriver.common.by import By

    strategies = {
        "id": By.ID,
        "name": By.NAME,
        "css": By.CSS_SELECTOR,
        "xpath": By.XPATH,
    }
    strategy, target = _parse_selector(selector)
    if strategy == "name_or_id":
        return (By.NAME, target)  # type: ignore[return-value]
    return strategies[strategy], target  # type: ignore[return-value]


def _wait_for_visible(driver: Any, selector: str) -> Any:
    """Wait explicitly for one credential field to become visible."""
    # Poll find_element directly so injected fakes without a full
    # WebDriverWait stack still exercise the wait path.
    import time

    by, target = _locator_tuple(selector)
    deadline = time.monotonic() + LOGIN_TIMEOUT
    while True:
        try:
            element = driver.find_element(by, target)
            is_displayed = getattr(element, "is_displayed", True)
            if callable(is_displayed):
                is_displayed = is_displayed()
            if is_displayed:
                return element
        except Exception:  # noqa: BLE001, S110 - retry until the deadline
            pass
        if time.monotonic() >= deadline:
            break
        time.sleep(0.05)
    raise AuthError(
        f"Could not find the login field '{selector}'. "
        "Check the credentials and the Login URL."
    )


def _wait_for_name_or_id(driver: Any, target: str) -> Any:
    """Plain keys try name first, then id — each with the same explicit wait."""
    # Direct poll loop (not two WebDriverWait calls) so a missing name field
    # does not burn the full timeout before id is tried, and fakes polling
    # find_element still exercise the wait path.
    import time

    from selenium.webdriver.common.by import By
    from selenium.webdriver.support import expected_conditions as EC  # noqa: F401

    deadline = time.monotonic() + LOGIN_TIMEOUT
    while True:
        for by in (By.NAME, By.ID):
            try:
                return driver.find_element(by, target)
            except Exception:  # noqa: BLE001, S112 - try the next strategy / next poll
                continue
        if time.monotonic() >= deadline:
            break
        time.sleep(0.05)
    raise AuthError(
        f"Could not find the login field '{target}' by name or id. "
        "Check the credentials and the Login URL."
    )


def _find_element(driver: Any, selector: str) -> Any:
    """Resolve one selector with an explicit wait for visibility."""
    strategy, target = _parse_selector(selector)
    if strategy == "name_or_id":
        return _wait_for_name_or_id(driver, target)
    return _wait_for_visible(driver, selector)


def _login_rejected(driver: Any, login_url: str) -> bool:
    """True when the login form is still there after the submit settled.

    The wait keeps a slow navigation from looking like a rejected login. A
    login only counts as rejected when the browser is still on the login URL
    *and* the password field is still on screen, so a successful login that
    stays on the same page is not mistaken for a failure.
    """
    from selenium.webdriver.common.by import By

    def still_on_login_form(drv: Any) -> bool:
        try:
            current = str(drv.current_url or "")
        except Exception:  # noqa: BLE001 - a driver without current_url cannot tell
            current = ""

        if current and login_url and not current.startswith(login_url.split("#")[0]):
            return False

        try:
            return bool(drv.find_elements(By.CSS_SELECTOR, "input[type='password']"))
        except Exception:  # noqa: BLE001 - a driver without find_elements cannot tell
            return False

    try:
        from selenium.webdriver.support.ui import WebDriverWait

        WebDriverWait(driver, LOGIN_TIMEOUT).until_not(still_on_login_form)
    except Exception:  # noqa: BLE001 - the form is still there, so the login failed
        return True
    return False


def _wait_for_success(driver: Any, auth_profile: Any, login_url: str) -> None:
    """Confirm the login settled, preferring an explicit success indicator."""
    import time

    indicator = getattr(auth_profile, "success_indicator", None)
    if indicator:
        strategy, _target = _parse_selector(str(indicator))
        if strategy == "name_or_id":
            raise AuthError(
                "success_indicator must use an explicit strategy prefix "
                "(id:, name:, css: or xpath:), not a plain key."
            )
        by, target = _locator_tuple(str(indicator))
        deadline = time.monotonic() + LOGIN_TIMEOUT
        while True:
            try:
                element = driver.find_element(by, target)
                is_displayed = getattr(element, "is_displayed", True)
                if callable(is_displayed):
                    is_displayed = is_displayed()
                if is_displayed:
                    return
            except Exception:  # noqa: BLE001, S110 - retry until the deadline
                pass
            if time.monotonic() >= deadline:
                break
            time.sleep(0.05)
        raise AuthError(
            "The target did not show the expected logged-in page after "
            "submitting. Check the credentials and the Login URL."
        )

    if _login_rejected(driver, login_url):
        raise AuthError(
            "The target still shows a password field after submitting. "
            "Check the credentials and the Login URL."
        )


def _submit(driver: Any, auth_profile: Any, last_element: Any | None) -> None:
    """Click the submit button, falling back to Enter on the last field.

    Selenium 4 guidance recommends clicking the form's submission button
    rather than relying on Enter.
    """
    import time

    submit_selector = getattr(auth_profile, "submit_selector", None)
    if submit_selector:
        strategy, _target = _parse_selector(str(submit_selector))
        if strategy == "name_or_id":
            raise AuthError(
                "submit_selector must use an explicit strategy prefix "
                "(id:, name:, css: or xpath:), not a plain key."
            )
        by, target = _locator_tuple(str(submit_selector))
        deadline = time.monotonic() + LOGIN_TIMEOUT
        while True:
            try:
                element = driver.find_element(by, target)
                is_enabled = getattr(element, "is_enabled", True)
                if callable(is_enabled):
                    is_enabled = is_enabled()
                is_displayed = getattr(element, "is_displayed", True)
                if callable(is_displayed):
                    is_displayed = is_displayed()
                if is_displayed and is_enabled:
                    element.click()
                    return
            except Exception:  # noqa: BLE001, S110 - retry until the deadline
                pass
            if time.monotonic() >= deadline:
                break
            time.sleep(0.05)
        raise AuthError(
            f"Could not find the submit button '{submit_selector}'. "
            "Check the credentials and the Login URL."
        )

    if last_element is not None:
        from selenium.webdriver.common.keys import Keys

        last_element.send_keys(Keys.ENTER)


def login_with_profile(driver: Any, auth_profile: Any) -> bool:
    """Open the login URL and fill configured selector/value credentials.

    Credential keys may be ``id:email``, ``name:email``, ``css:input[name=email]``
    or ``xpath://...``. Plain keys are resolved by name then id, each with an
    explicit wait. The form is submitted by clicking ``submit_selector`` when
    configured, otherwise with Enter on the final field. When
    ``success_indicator`` is configured the login is verified by waiting for
    that element; otherwise the password-field heuristic applies. Credentials
    are never logged or returned.

    Raises ``AuthError`` when a field is missing, the submit control cannot be
    found, or the login does not settle, so the caller can ask the Tester to
    check the credentials or the Login URL. Returns True when a login ran.
    """
    login_url = getattr(auth_profile, "login_url", None)
    credentials = getattr(auth_profile, "credentials", {}) or {}
    if not login_url:
        return False
    if not credentials:
        raise AuthError(
            "auth_profile has a login_url but no credentials. "
            "Check the credentials and the Login URL."
        )
    try:
        driver.get(login_url)
    except Exception as error:
        raise AuthError(
            "Could not open the Login URL. Check the login_url value."
        ) from error

    last_element: Any | None = None
    try:
        for selector, value in credentials.items():
            element = _find_element(driver, str(selector))
            element.clear()
            element.send_keys(str(value))
            last_element = element
        _submit(driver, auth_profile, last_element)
    except AuthError:
        raise
    except Exception as error:
        raise AuthError(
            "Could not fill the login form. Check the credentials and the Login URL."
        ) from error

    _wait_for_success(driver, auth_profile, login_url)
    return True
