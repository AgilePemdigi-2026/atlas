"""F7 tests: logging in with the Auth Profile before the DOM is extracted."""

from __future__ import annotations

import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys

from testgen.dom.auth import AuthError, login_with_profile


class FakeElement:
    def __init__(self, driver: FakeDriver, by: str, target: str) -> None:
        self.driver = driver
        self.by = by
        self.target = target
        self.value = ""
        self.cleared = False

    def clear(self) -> None:
        self.cleared = True

    def send_keys(self, value: str) -> None:
        self.value = value
        if value == Keys.ENTER:
            self.driver.submitted.append((self.by, self.target))
            if self.driver.redirect_on_submit:
                self.driver.current_url = self.driver.redirect_on_submit
        else:
            self.driver.fills.append((self.by, self.target, value))


class FakeDriver:
    """A driver that only knows the elements it was told to expose."""

    def __init__(
        self,
        available: set[tuple[str, str]] | None = None,
        password_visible: bool = False,
        current_url: str = "",
        redirect_on_submit: str = "",
    ) -> None:
        self.available = available or set()
        self.password_visible = password_visible
        self.current_url = current_url
        self.redirect_on_submit = redirect_on_submit
        self.visited: list[str] = []
        self.fills: list[tuple[str, str, str]] = []
        self.submitted: list[tuple[str, str]] = []
        self.elements: list[FakeElement] = []

    def get(self, url: str) -> None:
        self.visited.append(url)
        self.current_url = url

    def find_element(self, by: str, target: str) -> FakeElement:
        if (by, target) not in self.available:
            raise LookupError(f"no element for {by}={target}")
        element = FakeElement(self, by, target)
        self.elements.append(element)
        return element

    def find_elements(self, by: str, target: str) -> list[FakeElement]:
        if by == By.CSS_SELECTOR and target == "input[type='password']":
            return [FakeElement(self, by, target)] if self.password_visible else []
        return []


@pytest.fixture(autouse=True)
def _short_login_settle() -> None:
    """Keep the rejection check fast in tests."""
    import testgen.dom.auth as auth_module

    original = auth_module.LOGIN_SETTLE_TIMEOUT
    auth_module.LOGIN_SETTLE_TIMEOUT = 0.1
    yield
    auth_module.LOGIN_SETTLE_TIMEOUT = original


class FakeAuthProfile:
    def __init__(self, login_url: str | None, credentials: dict[str, str]) -> None:
        self.login_url = login_url
        self.credentials = credentials


def test_login_fills_every_credential_and_submits_the_last_field() -> None:
    # Given a driver exposing the email and password fields
    driver = FakeDriver(
        available={(By.ID, "email"), (By.NAME, "password")},
    )
    profile = FakeAuthProfile(
        "https://app.example.com/login",
        {"id:email": "qa@example.com", "password": "s3cr3t"},
    )

    # When the login runs
    login_with_profile(driver, profile)

    # Then the login URL is opened
    assert driver.visited == ["https://app.example.com/login"]
    # And every credential is filled and cleared first
    assert driver.fills == [
        (By.ID, "email", "qa@example.com"),
        (By.NAME, "password", "s3cr3t"),
    ]
    assert all(element.cleared for element in driver.elements)
    # And the last field submits the form
    assert driver.submitted == [(By.NAME, "password")]


def test_login_uses_the_declared_locator_strategy() -> None:
    # Given a driver exposing only the css and xpath targets
    driver = FakeDriver(
        available={
            (By.CSS_SELECTOR, "input[name=email]"),
            (By.XPATH, "//input[@type='password']"),
        },
    )
    profile = FakeAuthProfile(
        "https://app.example.com/login",
        {
            "css:input[name=email]": "qa@example.com",
            "xpath://input[@type='password']": "s3cr3t",
        },
    )

    # When the login runs
    login_with_profile(driver, profile)

    # Then each selector uses its own strategy
    assert [(by, target) for by, target, _ in driver.fills] == [
        (By.CSS_SELECTOR, "input[name=email]"),
        (By.XPATH, "//input[@type='password']"),
    ]


def test_login_falls_back_from_name_to_id_for_a_plain_selector() -> None:
    # Given a driver exposing the password field by id only
    driver = FakeDriver(available={(By.ID, "password")})
    profile = FakeAuthProfile(
        "https://app.example.com/login",
        {"password": "s3cr3t"},
    )

    # When the login runs
    login_with_profile(driver, profile)

    # Then the id is used
    assert [(by, target) for by, target, _ in driver.fills] == [(By.ID, "password")]


def test_login_reports_a_missing_field_as_an_auth_error() -> None:
    # Given a driver without the password field
    driver = FakeDriver(available={(By.ID, "email")})
    profile = FakeAuthProfile(
        "https://app.example.com/login",
        {"id:email": "qa@example.com", "password": "s3cr3t"},
    )

    # When the login runs
    # Then it raises an auth error instead of a generic failure
    with pytest.raises(AuthError):
        login_with_profile(driver, profile)


def test_login_reports_a_rejected_login_as_an_auth_error() -> None:
    # Given a driver that still shows the password field on the login page
    driver = FakeDriver(
        available={(By.ID, "email"), (By.ID, "password")},
        password_visible=True,
    )
    profile = FakeAuthProfile(
        "https://app.example.com/login",
        {"id:email": "qa@example.com", "password": "wrong"},
    )

    # When the login runs
    # Then it raises an auth error
    with pytest.raises(AuthError):
        login_with_profile(driver, profile)


def test_a_login_that_leaves_the_login_page_is_accepted() -> None:
    # Given a driver that navigates away on submit but keeps a password field
    driver = FakeDriver(
        available={(By.ID, "email"), (By.ID, "password")},
        password_visible=True,
        redirect_on_submit="https://app.example.com/dashboard",
    )
    profile = FakeAuthProfile(
        "https://app.example.com/login",
        {"id:email": "qa@example.com", "password": "s3cr3t"},
    )

    # When the login runs
    login_with_profile(driver, profile)

    # Then the browser left the login page, so the login is not a failure
    assert driver.current_url == "https://app.example.com/dashboard"


def test_login_never_writes_the_credential_values(
    capsys: pytest.CaptureFixture[str],
) -> None:
    # Given a valid profile
    driver = FakeDriver(available={(By.ID, "email"), (By.ID, "password")})
    profile = FakeAuthProfile(
        "https://app.example.com/login",
        {"id:email": "qa@example.com", "password": "s3cr3t"},
    )

    # When the login runs
    login_with_profile(driver, profile)

    # Then nothing is printed
    captured = capsys.readouterr()
    assert captured.out == ""
    assert captured.err == ""
    assert "s3cr3t" not in captured.out + captured.err


def test_login_without_a_login_url_does_nothing() -> None:
    # Given a profile without a login URL
    driver = FakeDriver()
    profile = FakeAuthProfile(None, {"id:email": "qa@example.com"})

    # When the login runs
    login_with_profile(driver, profile)

    # Then the browser is untouched
    assert driver.visited == []
    assert driver.fills == []


def test_a_failed_login_becomes_the_auth_failed_error_code(monkeypatch) -> None:
    from testgen.config import Settings
    from testgen.llm import extractor as extractor_module
    from testgen.llm.errors import LlmError
    from testgen.schemas.request import GenerateRequest

    def boom(url: str, **kwargs: object) -> None:
        raise AuthError("The target still shows a password field after submitting.")

    monkeypatch.setattr(extractor_module, "extract_dom", boom)

    settings = Settings(
        _env_file=None,
        kenari_api_key="test-key",
        service_api_key="svc",
        llm_base_url="https://kenari.id/v1",
        llm_model="gpt-6-luna",
    )
    request = GenerateRequest(
        user_story="As a user I want to log in.",
        scenarios=[{"name": "Login", "scenario_url": "https://app.example.com/login"}],
        auth_profile={
            "login_url": "https://app.example.com/login",
            "credentials": {"id:email": "qa@example.com"},
        },
    )

    # When extraction runs
    # Then it reports the auth failure with its own error code
    with pytest.raises(LlmError) as excinfo:
        extractor_module.extract(request, settings)

    assert excinfo.value.code == "auth_failed"
    assert excinfo.value.http_status == 502
    # And the message never carries a credential value
    assert "qa@example.com" not in excinfo.value.message
