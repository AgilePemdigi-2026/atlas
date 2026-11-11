"""F9 integration test: the DOM is really extracted from a live page."""

from __future__ import annotations

from typing import Any

from testgen.dom.extractor import extract_dom


def test_the_dom_is_extracted_from_a_live_page(
    base_url: str,
    chrome_driver: Any,
) -> None:
    # Given a page served over HTTP
    # When the DOM is extracted with headless Chrome
    snapshot = extract_dom(
        f"{base_url}/index.html",
        driver=chrome_driver,
    )

    # Then the interactive elements are in the extracted HTML
    assert 'id="q"' in snapshot.html
    assert 'id="submit"' in snapshot.html
    # And the URL is the one that was opened
    assert snapshot.url.endswith("/index.html")


def test_the_extraction_removes_the_non_visual_elements(
    base_url: str,
    chrome_driver: Any,
) -> None:
    # Given a page served over HTTP
    # When the DOM is extracted with headless Chrome
    snapshot = extract_dom(
        f"{base_url}/index.html",
        driver=chrome_driver,
    )

    # Then the script, style, svg, and canvas nodes are gone
    assert "<canvas" not in snapshot.html
    assert "<svg" not in snapshot.html
    # And the page content is still there
    assert "ATLAS fixture form" in snapshot.html


def test_a_url_that_is_not_http_is_rejected() -> None:
    # Given a URL that is not http(s)
    # When the DOM is extracted
    # Then it is rejected before the browser is started
    try:
        extract_dom("file:///etc/passwd")
    except ValueError as error:
        assert "absolute http or https" in str(error)
    else:  # pragma: no cover
        raise AssertionError("invalid URL should be rejected")
