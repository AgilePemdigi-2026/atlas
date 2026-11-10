"""F11 tests: DOM anti-hallucination and per-action field rules."""

from __future__ import annotations

from testgen.validation.validator import validate_extraction

DOM = (
    'input id="name" name="name" type="text" placeholder="Enter your name"\n'
    'button type="submit" "Create account"\n'
    'a href="/login" "Sign in"'
)

HTML = (
    "<html><body><form>"
    '<input id="name" name="name" type="text">'
    '<button id="register" type="submit">Create account</button>'
    "</form></body></html>"
)


def _step(**overrides: object) -> dict[str, object]:
    step: dict[str, object] = {
        "stage": "When",
        "action": "click",
        "by": "id",
        "target": "name",
    }
    step.update(overrides)
    return step


def _payload(*steps: dict[str, object]) -> dict[str, object]:
    return {
        "scenarios": [
            {
                "scenario": "Register Success",
                "steps": [
                    {
                        "stage": "Given",
                        "action": "navigate",
                        "value": "http://example.test/register",
                    },
                    *steps,
                ],
            }
        ]
    }


def test_valid_click_passes() -> None:
    result = validate_extraction(_payload(_step()), DOM)

    assert result.valid is True


def test_value_on_click_is_rejected() -> None:
    result = validate_extraction(_payload(_step(value="Create account")), DOM)

    assert result.valid is False
    assert result.errors == [
        "scenarios[0].steps[1].value is not allowed for action 'click'."
    ]


def test_stage_order_must_follow_given_when_then() -> None:
    result = validate_extraction(
        _payload(
            {
                "stage": "Then",
                "action": "assert_url",
                "value": "http://example.test/tutorial",
            },
            _step(stage="When"),
        ),
        DOM,
    )

    assert result.valid is False
    assert result.errors == [
        "scenarios[0].steps[2].stage must follow Given → When → Then order."
    ]


def test_xpath_text_target_matches_the_compact_dom() -> None:
    result = validate_extraction(
        _payload(_step(by="xpath", target='//button[text()="Create account"]')),
        DOM,
    )

    assert result.valid is True


def test_xpath_text_target_missing_from_the_dom_is_rejected() -> None:
    result = validate_extraction(
        _payload(_step(by="xpath", target='//button[text()="Sign up"]')),
        DOM,
    )

    assert result.valid is False
    assert "was not found in the extracted DOM" in result.errors[0]


def test_xpath_attribute_predicate_matches_the_dom() -> None:
    result = validate_extraction(
        _payload(_step(by="xpath", target='//button[@type="submit"]')),
        DOM,
    )

    assert result.valid is True


def test_xpath_contains_text_matches_the_dom() -> None:
    result = validate_extraction(
        _payload(_step(by="xpath", target='//button[contains(text(), "Create")]')),
        DOM,
    )

    assert result.valid is True


def test_xpath_text_matches_raw_html() -> None:
    result = validate_extraction(
        _payload(
            _step(
                by="xpath",
                target='//button[normalize-space()="Create account"]',
            )
        ),
        HTML,
    )

    assert result.valid is True
