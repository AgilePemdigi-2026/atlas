# generator-service

Python service for ATLAS. Feature 12 (rule-based Selenium script generator) is
implemented. DOM extraction (F9), LLM extraction (F10), validation (F11), and
the API/error-handling layer (F13) are one-line placeholders.

## Layout

```
pyproject.toml
Dockerfile                 # F9 placeholder: headless Chrome image
.env.example               # F10/F13 placeholder: LLM/service keys
src/testgen/
├── main.py                # F13 placeholder: FastAPI app factory
├── config.py              # F9-F13 placeholder: pydantic-settings
├── errors.py              # F12 typed exceptions + error codes
├── api/                   # F13 placeholders
├── schemas/               # F10/F11/F13 placeholders
├── pipeline/              # F9-F13 placeholder
├── dom/                   # F7/F9 placeholders
├── llm/                   # F10 placeholders
├── validation/            # F11 placeholder
└── codegen/
    ├── selenium_generator.py
    └── templates/
tests/
├── unit/                  # fast, no network
├── integration/           # local HTTP server + headless Chrome
└── fixtures/              # HTML app, scenarios, golden expectations
```

The package is installable; tests import `testgen` directly.

## Commands

Run from `generator-service/`:

```bash
uv sync                 # install dependencies (test group is default)
uv run pytest           # golden + browser integration tests
uv run ruff check .
uv run ruff format --check .
uv run python tests/regenerate_golden.py
```

`uv run pytest` runs the golden tests and the browser integration tests.
Selenium Manager downloads Chrome for Testing on first run; if Chrome cannot be
provisioned, the integration tests skip. Golden tests never need a browser.

## Input contract

`generate_script()` takes a parsed dict (feature 11 owns JSON parsing)
describing one test case:

```json
{
  "scenarios": [
    {
      "scenario": "Login berhasil",
      "steps": [
        {"stage": "Given", "action": "navigate", "value": "https://example.test/login"},
        {"stage": "When", "action": "fill", "by": "id", "target": "email", "value": "user@example.test", "defaulted": true}
      ]
    }
  ]
}
```

- `scenarios` is a non-empty list. Each scenario needs `scenario` and a
  non-empty `steps` list.
- `stage` is `Given`, `When`, or `Then`, in non-decreasing order (repeats
  allowed).
- The first step of every scenario must be `navigate` with an absolute
  `http`/`https` URL. There is no implicit `base_url`.
- Actions and required fields:

  | action | required | notes |
  | --- | --- | --- |
  | `navigate` | `value` | absolute http(s) URL |
  | `fill` | `by`, `target`, `value` | clear, then send keys |
  | `click` | `by`, `target` | |
  | `select` | `by`, `target`, `value` | `select_by_value` |
  | `assert_url` | `value` | exact URL, absolute http(s) |
  | `assert_text` | `value` | substring of visible body text |

- `by` is one of `id`, `name`, `css`, `xpath`, `link_text`,
  `partial_link_text`.
- `defaulted` is an optional boolean allowed only on actions with `value`; it
  emits `# ATLAS DEFAULT` inline.
- Unknown keys and empty strings are rejected.

The generator revalidates this structure, the action/locator whitelists, stage
order, and navigation. It never checks the DOM; feature 11 owns schema and
anti-hallucination validation.

## Output

One self-contained Python module per test case, with one function per scenario
(`def login_berhasil(driver):`, driver injected). Function names are ASCII
slugs; duplicates get `_2`, `_3`; an empty slug becomes `scenario`,
`scenario_2`, …; names that would shadow module imports or builtins get a
`_scenario` suffix.

The artifact is deterministic: no timestamps, versions, paths, or random IDs.
4-space indentation, LF, one trailing newline. Progress markers are stable:

```
[ATLAS] scenario 1 step 2: fill
[ATLAS] scenario 1: PASS|FAIL|ERROR
[ATLAS] result: 1 passed, 0 failed
```

When run directly, the script uses headless Chrome unless `HEADLESS=false`,
one driver for all scenarios, continues after `AssertionError`, stops on
unexpected exceptions, and exits `1` if anything failed. Generated scripts
target Python 3.10+ and Selenium >=4.6.

`partial` is not a generator status. `generate_script()` either returns a
script or raises `ConversionError`; a caller marks a test case `partial` when
at least one scenario converts and at least one fails. No script ever contains
skipped or commented-out steps.

## Errors

`generate_script()` raises `testgen.errors.ConversionError`, a `TestgenError`
subclass. `to_dict()` returns exactly:

```json
{
  "code": "invalid_input",
  "message": "Input must be a JSON object.",
  "step_index": null,
  "field": "input"
}
```

- `code`: `invalid_input`, `missing_field`, `unsupported_action`,
  `unsupported_locator`, `invalid_stage_order`, `empty_steps`,
  `empty_scenarios`, `missing_navigation`, `invalid_url`
- `message`: English, mapped to Indonesian UI copy by feature 13
- `step_index`: 1-based, `null` for scenario/global errors
- `field`: fully qualified, e.g. `scenarios[0].steps[2].target`

## Tests

- `tests/unit/test_codegen_golden.py` parametrizes over
  `tests/fixtures/golden/`: `<name>.json` with `<name>.py` (byte-for-byte) or
  `<name>.error.json` (full object equality). Add a fixture pair instead of
  writing a new test.
- `tests/integration/test_codegen_selenium.py` runs one test per scenario
  fixture against a local HTTP server.
- `tests/integration/test_generated_script.py` runs whole generated scripts and
  asserts exit codes.
- `tests/unit/test_package.py` checks the package version.

Regenerate expectations only after reviewing the diff:

```bash
uv run python tests/regenerate_golden.py
git diff tests/fixtures/golden
```

## Placeholders

These files are intentionally one-line placeholders until their feature lands:

- `main.py`, `config.py`, `api/`, `schemas/`, `pipeline/`, `dom/`, `llm/`,
  `validation/`
- `Dockerfile` and `.env.example`

The FastAPI app does not boot yet. Do not add behavior there without the
corresponding feature work (F7-F13).
