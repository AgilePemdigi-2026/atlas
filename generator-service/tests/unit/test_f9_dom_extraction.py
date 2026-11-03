"""F9 DOM extraction tests without launching a real browser."""

from __future__ import annotations

from testgen.dom.extractor import extract_dom


class FakeDriver:
    def __init__(self) -> None:
        self.page_source = '<html><script>secret</script><body><button id="go">Go</button></body></html>'
        self.current_url = "https://example.test/page"
        self.calls: list[object] = []
        self.closed = False

    def set_page_load_timeout(self, value: float) -> None:
        self.calls.append(("timeout", value))

    def get(self, url: str) -> None:
        self.calls.append(("get", url))

    def execute_script(self, script: str) -> None:
        self.calls.append(("script", script))

    def quit(self) -> None:
        self.closed = True


def test_extract_dom_uses_driver_and_closes_owned_instance() -> None:
    driver = FakeDriver()
    result = extract_dom("https://example.test/page", driver_factory=lambda: driver)

    assert result.url == "https://example.test/page"
    assert 'button id="go"' in result.html
    assert any(call[0] == "get" for call in driver.calls)  # type: ignore[index]
    assert driver.closed is True


def test_extract_dom_rejects_non_http_url() -> None:
    try:
        extract_dom("file:///etc/passwd", driver_factory=FakeDriver)
    except ValueError as error:
        assert "absolute http or https" in str(error)
    else:  # pragma: no cover
        raise AssertionError("invalid URL should be rejected")


def test_caller_owned_driver_is_not_closed() -> None:
    driver = FakeDriver()
    extract_dom("https://example.test/page", driver=driver)
    assert driver.closed is False
