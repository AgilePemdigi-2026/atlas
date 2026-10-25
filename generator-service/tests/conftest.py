from __future__ import annotations

import functools
import http.server
import threading
from collections.abc import Iterator

import pytest
from support import APP_DIR


@pytest.fixture(scope="session")
def base_url() -> Iterator[str]:
    handler = functools.partial(
        http.server.SimpleHTTPRequestHandler,
        directory=str(APP_DIR),
    )

    with http.server.ThreadingHTTPServer(("127.0.0.1", 0), handler) as server:
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()

        try:
            yield f"http://127.0.0.1:{server.server_port}"
        finally:
            server.shutdown()
            thread.join(timeout=5)


@pytest.fixture(scope="session")
def chrome_driver() -> Iterator[object]:
    pytest.importorskip("selenium")
    from selenium import webdriver

    try:
        driver = webdriver.Chrome(options=webdriver.ChromeOptions())
    except Exception as error:  # noqa: BLE001 - provisioning failure is environmental
        pytest.skip(f"Chrome is not available: {error}")

    driver.set_page_load_timeout(30)

    try:
        yield driver
    finally:
        driver.quit()
