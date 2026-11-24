"""Live browser demo of the ATLAS web application.

Run by tests/Demo/ApplicationDemoTest.php through:

    uv run --with selenium python tests/Demo/demo_app.py

The script starts a stub generator service, opens a visible Chrome window, and
walks the main user journey. Every [DEMO] line printed here also appears in the
test output, so the terminal tells the same story as the browser.
"""

from __future__ import annotations

import json
import os
import threading
import time
import traceback
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

APP_URL = os.environ.get("DEMO_APP_URL", "http://127.0.0.1:8123").rstrip("/")
STUB_PORT = int(os.environ.get("DEMO_STUB_PORT", "8999"))
PAUSE = float(os.environ.get("DEMO_PAUSE", "1"))
HEADLESS = os.environ.get("DEMO_HEADLESS", "0") == "1"
WAIT = 20

DEMO_NAME = "Rina Pratama"
DEMO_EMAIL = "demo@atlas.test"
DEMO_PASSWORD = "password123"
BASE_URL = "https://toko-demo.test"
USER_STORY = (
    "Sebagai pelanggan, saya ingin menyelesaikan checkout "
    "agar bisa membeli produk tanpa hambatan."
)
ACCEPTANCE = """Skenario: Checkout berhasil
Given saya berada di halaman checkout
When saya mengisi data pengiriman dengan benar
Then saya melihat halaman pembayaran

Skenario: Checkout gagal
Given saya berada di halaman checkout
When saya mengosongkan alamat pengiriman
Then saya melihat pesan kesalahan"""
SCENARIO_URLS = [BASE_URL + "/checkout", BASE_URL + "/checkout?fail=1"]

# The generated script the stub returns, written the way ATLAS itself would.
MOCK_SCRIPT = '''import os

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

WAIT_TIMEOUT = 10


def wait_for(driver, by, target):
    return WebDriverWait(driver, WAIT_TIMEOUT).until(
        EC.visibility_of_element_located((by, target))
    )


# Scenario: Checkout berhasil
def checkout_berhasil(driver):
    driver.get("https://toko-demo.test/checkout")
    wait_for(driver, By.ID, "shipping-address").send_keys(
        "Jl. Merdeka 10"
    )
    wait_for(driver, By.ID, "pay-now").click()
    assert "Pembayaran" in wait_for(driver, By.ID, "checkout-title").text


# Scenario: Checkout gagal
def checkout_gagal(driver):
    driver.get("https://toko-demo.test/checkout?fail=1")
    wait_for(driver, By.ID, "pay-now").click()
    assert "Alamat wajib diisi" in wait_for(
        driver, By.ID, "checkout-error"
    ).text


if __name__ == "__main__":
    options = webdriver.ChromeOptions()
    if os.environ.get("HEADLESS", "true").lower() != "false":
        options.add_argument("--headless=new")

    driver = webdriver.Chrome(options=options)
    try:
        checkout_berhasil(driver)
        checkout_gagal(driver)
    finally:
        driver.quit()
'''

SCENARIOS = [
    {"name": "Checkout berhasil", "scenario_url": None},
    {"name": "Checkout gagal", "scenario_url": None},
]


class StubHandler(BaseHTTPRequestHandler):
    """Minimal stand-in for generator-service, so the demo needs no LLM."""

    def do_POST(self) -> None:
        self.rfile.read(int(self.headers.get("Content-Length", 0)))

        if self.path == "/v1/scenario-urls":
            body = {"status": "ok", "data": {"scenarios": SCENARIOS}}
        elif self.path == "/v1/generate":
            body = {
                "status": "ok",
                "data": {"scenarios": SCENARIOS, "script": MOCK_SCRIPT},
                "meta": {
                    "attempts": 1,
                    "dom_source": "live",
                    "warnings": [],
                    "model": "demo-stub",
                    "prompt_version": "demo",
                },
            }
        else:
            self.send_error(404)
            return

        payload = json.dumps(body).encode()
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(payload)))
        self.end_headers()
        self.wfile.write(payload)

    def log_message(self, *args: object) -> None:
        pass


def start_stub() -> ThreadingHTTPServer:
    server = ThreadingHTTPServer(("127.0.0.1", STUB_PORT), StubHandler)
    server.daemon_threads = True
    threading.Thread(target=server.serve_forever, daemon=True).start()
    print(f"[DEMO] stub generator on 127.0.0.1:{STUB_PORT}", flush=True)

    return server


def build_options() -> webdriver.ChromeOptions:
    options = webdriver.ChromeOptions()
    options.add_argument("--window-size=1440,900")
    options.add_experimental_option("prefs", {
        "credentials_enable_service": False,
        "profile.password_manager_enabled": False,
    })
    if HEADLESS:
        options.add_argument("--headless=new")

    return options


def beat() -> None:
    time.sleep(PAUSE)


def wait_text(driver: webdriver.Chrome, text: str) -> None:
    WebDriverWait(driver, WAIT).until(
        EC.text_to_be_present_in_element((By.TAG_NAME, "body"), text)
    )


def visible(driver: webdriver.Chrome, css: str):
    return WebDriverWait(driver, WAIT).until(
        EC.visibility_of_element_located((By.CSS_SELECTOR, css))
    )


def click(driver: webdriver.Chrome, by: str, target: str) -> None:
    element = WebDriverWait(driver, WAIT).until(
        EC.presence_of_element_located((by, target))
    )
    # The page's inline handlers attach on DOMContentLoaded; wait for the load
    # to finish so a click cannot land before the listener exists.
    WebDriverWait(driver, WAIT).until(
        lambda page: page.execute_script("return document.readyState") == "complete"
    )
    driver.execute_script(
        "arguments[0].scrollIntoView({block: 'center', behavior: 'instant'});",
        element,
    )
    # Real mouse clicks are occasionally swallowed by the browser window, so
    # trigger the element's click handler directly.
    driver.execute_script("arguments[0].click();", element)


def fill(driver: webdriver.Chrome, element, value: str) -> None:
    # send_keys proved flaky here (it silently left some fields empty, which
    # then tripped the native `required` validation). Set the value directly.
    driver.execute_script(
        "arguments[0].scrollIntoView({block: 'center', behavior: 'instant'});"
        "arguments[0].focus();"
        "arguments[0].value = arguments[1];"
        "arguments[0].dispatchEvent(new Event('input', {bubbles: true}));"
        "arguments[0].dispatchEvent(new Event('change', {bubbles: true}));",
        element,
        value,
    )

    assert element.get_attribute("value") == value, "field tidak terisi"


def run_demo(driver: webdriver.Chrome) -> None:
    print("[DEMO] 1/5 Landing page", flush=True)
    driver.get(APP_URL + "/")
    wait_text(driver, "Turn User Stories")
    beat()

    print("[DEMO] 2/5 Register a new account", flush=True)
    click(driver, By.XPATH, "//a[normalize-space()='Create Your Account']")
    fill(driver, visible(driver, "#name"), DEMO_NAME)
    fill(driver, visible(driver, "#email"), DEMO_EMAIL)
    fill(driver, visible(driver, "#password"), DEMO_PASSWORD)
    beat()
    click(driver, By.CSS_SELECTOR, "form button[type='submit']")
    wait_text(driver, "Akun anda berhasil terdaftar")
    beat()

    print("[DEMO] 3/5 Sign in and open the console", flush=True)
    fill(driver, visible(driver, "#email"), DEMO_EMAIL)
    password = visible(driver, "#password")
    fill(driver, password, DEMO_PASSWORD)
    # The submit click is occasionally swallowed at browser level, so submit
    # the already-filled form directly instead of relying on the button.
    driver.execute_script("arguments[0].form.requestSubmit()", password)
    WebDriverWait(driver, WAIT).until(EC.url_contains("/console"))
    wait_text(driver, "Test Scenario")
    wait_text(driver, "Belum ada test case.")
    beat()

    print("[DEMO] 4/5 Create a collection and a test case", flush=True)
    click(driver, By.CSS_SELECTOR, "[data-new-collection]")
    composer = visible(
        driver, "[data-collection-composer]:not([hidden]) input[name='name']"
    )
    fill(driver, composer, "Toko Online")
    composer.send_keys(Keys.ENTER)
    wait_text(driver, "Toko Online")
    beat()

    click(driver, By.CSS_SELECTOR, "button[aria-label='Opsi Toko Online']")
    click(
        driver,
        By.CSS_SELECTOR,
        "[data-menu-panel]:not([hidden]) button[data-action='new-test-case']",
    )
    draft = visible(
        driver, "[data-draft-composer]:not([hidden]) input[name='name']"
    )
    fill(driver, draft, "Checkout")
    draft.send_keys(Keys.ENTER)
    visible(driver, "#user-story")

    fill(driver, visible(driver, "#user-story"), USER_STORY)
    fill(driver, visible(driver, "#acceptance-criteria"), ACCEPTANCE)
    fill(driver, visible(driver, "#base-url"), BASE_URL)
    beat()
    click(driver, By.XPATH, "//button[normalize-space()='Lanjutkan']")
    WebDriverWait(driver, WAIT).until(
        lambda page: len(
            page.find_elements(By.CSS_SELECTOR, "[data-scenario-row]")
        ) == 2
    )
    beat()

    print("[DEMO] 5/5 Fill the scenario URLs and generate the script", flush=True)
    for element, url in zip(
        driver.find_elements(By.CSS_SELECTOR, "[data-scenario-url]"),
        SCENARIO_URLS,
    ):
        fill(driver, element, url)
    beat()
    click(driver, By.CSS_SELECTOR, "[data-generate-button]")
    wait_text(driver, "Skrip berhasil dibuat")
    wait_text(driver, "webdriver.Chrome")
    wait_text(driver, "Download Script")
    beat()


def save_failure_screenshot(driver: webdriver.Chrome) -> None:
    directory = Path(__file__).resolve().parent / "screenshots"
    directory.mkdir(exist_ok=True)
    path = directory / f"failure-{int(time.time())}.png"

    try:
        driver.save_screenshot(str(path))
        print(f"[DEMO] screenshot: {path}", flush=True)
    except Exception:
        pass


def main() -> int:
    stub = start_stub()
    driver = webdriver.Chrome(options=build_options())

    try:
        run_demo(driver)
    except Exception:
        print("[DEMO] FAILED", flush=True)
        traceback.print_exc()
        save_failure_screenshot(driver)

        return 1
    finally:
        driver.quit()
        stub.shutdown()

    print("[DEMO] PASSED", flush=True)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
