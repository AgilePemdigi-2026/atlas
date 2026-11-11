# F7/F9 implementation: auth profile login.
"""F9 helper for using the supplied authentication profile before extraction."""

from __future__ import annotations

from typing import Any

__all__ = ["AuthError", "login_with_profile"]

# How long the login form may stay on screen before the login counts as rejected.
LOGIN_SETTLE_TIMEOUT = 5.0


class AuthError(RuntimeError):
    """The supplied Auth Profile could not log in to the target.

    The message never carries a credential value; it only says what to check.
    """


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

        WebDriverWait(driver, LOGIN_SETTLE_TIMEOUT).until_not(still_on_login_form)
    except Exception:  # noqa: BLE001 - the form is still there, so the login failed
        return True
    return False


def login_with_profile(driver: Any, auth_profile: Any) -> None:
    """Open login URL and fill configured selector/value credentials.

    Credential keys may be ``id:email``, ``name:email``, ``css:input[name=email]``
    or ``xpath://...``. Plain keys are resolved by name then id. The final field
    receives Enter to submit the form; credentials are never logged or returned.

    Raises ``AuthError`` when a field is missing or the target still shows a
    password field after submitting, so the caller can ask the Tester to check
    the credentials or the Login URL.
    """
    login_url = getattr(auth_profile, "login_url", None)
    credentials = getattr(auth_profile, "credentials", {}) or {}
    if not login_url:
        return
    driver.get(login_url)
    last_element = None
    try:
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
    except AuthError:
        raise
    except Exception as error:
        raise AuthError(
            "Could not fill the login form. Check the credentials and the Login URL."
        ) from error

    if _login_rejected(driver, login_url):
        raise AuthError(
            "The target still shows a password field after submitting. "
            "Check the credentials and the Login URL."
        )
