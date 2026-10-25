import os

from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

WAIT_TIMEOUT = 10
PAGE_LOAD_TIMEOUT = 30


def wait_for(driver, by, target):
    return WebDriverWait(driver, WAIT_TIMEOUT).until(
        EC.visibility_of_element_located((by, target))
    )


def assert_condition(condition, scenario, action, expected):
    try:
        condition()
    except TimeoutException:
        raise AssertionError(
            f"{scenario}: {action} expected {expected!r}"
        ) from None


# Scenario: Multiple navigates
def multiple_navigates(driver):
    # Given
    print("[ATLAS] scenario 1 step 1: navigate", flush=True)
    driver.get("https://example.test/")

    # When
    print("[ATLAS] scenario 1 step 2: click", flush=True)
    WebDriverWait(driver, WAIT_TIMEOUT).until(
        EC.element_to_be_clickable((By.LINK_TEXT, "Products"))
    ).click()

    print("[ATLAS] scenario 1 step 3: navigate", flush=True)
    driver.get("https://example.test/cart")

    # Then
    print("[ATLAS] scenario 1 step 4: assert_url", flush=True)
    assert_condition(
        lambda: WebDriverWait(driver, WAIT_TIMEOUT).until(
            EC.url_to_be("https://example.test/cart")
        ),
        "scenario 1 (Multiple navigates)",
        "assert_url",
        "https://example.test/cart",
    )


if __name__ == "__main__":
    options = webdriver.ChromeOptions()
    if os.environ.get("HEADLESS", "true").lower() != "false":
        options.add_argument("--headless=new")

    driver = webdriver.Chrome(options=options)
    driver.set_page_load_timeout(PAGE_LOAD_TIMEOUT)

    passed = 0
    failed = 0

    try:
        for index, run_scenario in enumerate(
            [
                multiple_navigates,
            ],
            start=1,
        ):
            try:
                run_scenario(driver)
            except AssertionError as error:
                failed += 1
                print(f"[ATLAS] scenario {index}: FAIL {error}", flush=True)
            except Exception as error:
                failed += 1
                print(f"[ATLAS] scenario {index}: ERROR {error}", flush=True)
                break
            else:
                passed += 1
                print(f"[ATLAS] scenario {index}: PASS", flush=True)
    finally:
        driver.quit()

    print(f"[ATLAS] result: {passed} passed, {failed} failed", flush=True)
    raise SystemExit(1 if failed else 0)
