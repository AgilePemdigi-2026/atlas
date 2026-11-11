# generator-service

Python service for ATLAS. LLM extraction (F10/F11 incl. retry), DOM extraction
(F9), rule-based Selenium codegen (F12), and the error envelope (F13) are
implemented. `POST /v1/generate` returns both the parsed scenarios and the
generated script in one synchronous call.

## Layout

```
pyproject.toml
Dockerfile                 # headless Chrome image for DOM extraction
.env.example               # LLM/service keys
src/testgen/
├── main.py                # FastAPI app factory
├── config.py              # pydantic-settings
├── errors.py              # typed exceptions + error codes
├── api/                   # routers (health, /v1/generate) + service-key auth
├── schemas/               # request / response envelopes
├── dom/                   # tiered DOM extraction (F9)
├── llm/                   # Kenari client + extraction/retry loop (F10/F11)
├── validation/            # schema + Gherkin order + anti-hallucination (F11)
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
uv run uvicorn testgen.main:app --host 127.0.0.1 --port 8001   # run the service
uv run pytest           # golden + browser integration tests
uv run ruff check .
uv run ruff format --check .
uv run python tests/regenerate_golden.py
```

`LLM_TIMEOUT` defaults to 30s in `config.py`, but Kenari can exceed that on cold
requests; set `LLM_TIMEOUT=120` in `.env` for local e2e. The web side must use the
same `SERVICE_API_KEY` as `GENERATOR_SERVICE_KEY`.

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
  `empty_scenarios`, `missing_navigation`, `invalid_url`- `message`: English, mapped to Indonesian UI copy by feature 13
- `step_index`: 1-based, `null` for scenario/global errors
- `field`: fully qualified, e.g. `scenarios[0].steps[2].target`

## F10: LLM extraction (Kenari)

`POST /v1/generate` runs one extraction attempt: user story + cleaned DOM ->
structured `{"scenarios": [...]}` shaped for the F12 converter. Laravel is the
only caller (header `X-Service-Key`); the service never touches PostgreSQL and
is never called by the browser.

```json
{
  "user_story": "As a user I want to login.",
  "acceptance_criteria": "Given ... When ... Then ...",
  "base_url": "https://target.example.com",
  "scenarios": [{"name": "Login Success", "scenario_url": "https://target.example.com/login"}],
  "auth_profile": {"login_url": "https://target.example.com/login", "credentials": {}},
  "dom": "<optional pre-cleaned snapshot>"
}
```

DOM tiers: use the caller's `dom` snapshot, else open the target page with
Selenium (headless Chrome, logging in first when an `auth_profile` is
supplied), else run minimal with every guess marked `defaulted: true`. The
response always reports `meta.dom_source` (`live`/`snapshot`/`minimal`) so
callers know how much to trust the output. Env (values stay out of the repo):
`KENARI_API_KEY`, `LLM_BASE_URL` (default `https://kenari.id/v1`), `LLM_MODEL`
(default `gpt-6-luna`), `SERVICE_API_KEY`, `LLM_TIMEOUT=120`, `MAX_RETRIES=3`.

One extraction pass runs with up to `MAX_RETRIES` feedback retries (F11) and
F12 revalidates downstream before codegen, rejecting bad JSON with
`ConversionError` (enveloped as 502). F10 envelopes carry the same
`{code, message, step_index, field}` shape so F13 maps one format.

## F7: Auth Profile

When the request carries an `auth_profile`, the service logs in to the target
before extracting the DOM, and the generated script logs in before it runs the
scenarios.

The credential **values** never reach the generated script. The script carries
only the login URL and the field selectors; the values are read from
environment variables at run time:

```bash
# The script prints the exact variable names it needs in its header.
export ATLAS_AUTH_ID_EMAIL='qa@example.com'
export ATLAS_AUTH_PASSWORD='s3cr3t'
python generated_script.py
```

The variable name is `ATLAS_AUTH_` plus the uppercased selector with
non-alphanumeric runs replaced by `_` (`id:email` -> `ATLAS_AUTH_ID_EMAIL`).
Two selectors that slug to the same name get `_2`, `_3` suffixes.

Selector rules match the DOM extraction: `id:`, `name:`, `css:`, and `xpath:`
name their strategy; a plain key is looked up by name and then by id.

If the target rejects the login, the service returns the `auth_failed` error
code (502) and the web side shows "Gagal login. Periksa kredensial atau Login
URL.".

## Tests

- `tests/unit/test_codegen_golden.py` parametrizes over
  `tests/fixtures/golden/`: `<name>.json` with `<name>.py` (byte-for-byte) or
  `<name>.error.json` (full object equality). Add a fixture pair instead of
  writing a new test.
- `tests/integration/test_codegen_selenium.py` runs one test per scenario
  fixture against a local HTTP server.
- `tests/integration/test_generated_script.py` runs whole generated scripts and
  asserts exit codes.
- `tests/unit/test_llm_extraction.py` covers DOM cleaning, the three DOM
  tiers, Kenari client errors, and the F10->F12 handoff (all mocked, no network).
- `tests/unit/test_f10_transport.py` covers auth gating and the
  `POST /v1/generate` envelope over FastAPI's test client.
- `tests/unit/test_f7_auth_login.py` covers the login helper and the
  `auth_failed` error code.
- `tests/unit/test_f7_script_login.py` covers the login block in the generated
  script and proves no credential value is ever returned.
- `tests/integration/test_f7_login_script.py` runs the generated login in a real
  browser against the fixture login page.
- `tests/unit/test_f9_f11_dom_validation.py` covers DOM cleaning, extraction from
  a protected page, stage order, and DOM anti-hallucination.
- `tests/integration/test_f9_live_dom.py` extracts the DOM with headless Chrome
  and checks the non-visual nodes are gone.
- `tests/unit/test_package.py` checks the package version.

Regenerate expectations only after reviewing the diff:

```bash
uv run python tests/regenerate_golden.py
git diff tests/fixtures/golden
```

## Status

All F7–F13 service work is implemented. Orchestration lives in
`llm/extractor.py` (extraction → validation → retry) with codegen in
`api/routes/generate.py`. The service boots via the FastAPI factory and is
exercised by the test suite: `uv run pytest -q` → 91 passed (browser
integration tests skip when Chrome cannot be provisioned).
