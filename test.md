- **One test per scenario.** The test name is the scenario title exactly as written in this file: `Failed Register (email already registered)`.
- **Group by backlog item** with `describe('F1 Register', ...)`, so tests trace to the backlog.
- **Each Gherkin step becomes a comment** (`// Given`, `// And`, `// When`, `// Then`), followed by the code that performs it. `And` lines repeat the keyword they continue.
- **Protected flows** (everything behind `auth`) also need: a guest-redirect case, an ownership/policy-violation case, and the happy path.
- **Use factories and `fake()`**, `RefreshDatabase`, and HTTP-level tests. No browser in `web/` tests.
- **Service side (pytest):** same convention with `# Given / When / Then` comments and scenario-style test names. Unit tests stay offline (injected `httpx` transports, no Chrome).
- **Enabler items (F9–F12)** use technical scenarios, labeled `[technical]`, with pytest. They are not user-facing.

---

## 1. Traceability table

| Scenario | Primary item | Secondary | Source |
|---|---|---|---|
| Register Success | F1 | | report |
| Failed Register (missing fields) | F1 | | report |
| Failed Register (email already registered) | F1 | | report |
| Failed Register (invalid format or weak password) | F1 | | report |
| Failed Register (database unavailable) | F1 | | proposed |
| Login Success | F2 | | report |
| Failed Login (missing fields) | F2 | | report |
| Failed Login (invalid credentials) | F2 | | report |
| Failed Login (account not found) | F2 | | proposed |
| Failed Login (service unavailable) | F2 | | proposed |
| Logout ends the session | F3 | | proposed |
| Guest cannot log out | F3 | | proposed |
| Forgot Password | F4 | F2 | report |
| Reset password via email | F4 | | proposed |
| Landing page renders | F5 | | proposed |
| Landing page calls to action | F5 | | proposed |
| Failed Generate (missing user story) | F6 | | report |
| Story with several scenarios is split | F6 | | proposed |
| Story without scenario headings falls back to one scenario | F6 | | proposed |
| Generate with an auth profile | F7 | F9 | proposed |
| Generate without auth | F7 | | proposed |
| Login failed during DOM extraction | F7 | F9 | proposed |
| Auth profile is stored encrypted | F7 | | proposed |
| The Config tab renders the auth profile form | F7 | | proposed |
| The auth profile API never returns credential values | F7 | | proposed |
| Guest cannot manage an auth profile | F7 | | proposed |
| Another user's test case auth profile is not reachable | F7 | | proposed |
| Prompt for Missing Scenario URLs | F8 | F6 | report |
| Confirm pre-filled scenario URLs | F8 | | proposed |
| Reject an invalid scenario URL | F8 | | proposed |
| DOM source tiers | F9 | | proposed, technical |
| Non-visual elements are removed from the DOM | F9 | | proposed, technical |
| The DOM is extracted from a protected page | F9 | F7 | proposed, technical |
| Plain story becomes structured steps | F10 | | proposed, technical |
| Invalid output is retried then fails | F11 | | proposed, technical |
| Stage order must follow Given, When, Then | F11 | | proposed, technical |
| A target that is not in the DOM is rejected | F11 | F9 | proposed, technical |
| Each action and locator renders | F12 | | proposed, technical |
| Successful Script Generation | F12 | F6, F8, F9, F10, F11 | report |
| Failed Generate (target URL unreachable) | F13 | F9 | report |
| Failed Generate (LLM service unavailable) | F13 | F10 | report |
| Failed Generate (no matching DOM elements) | F13 | F11 | proposed |
| Create a collection | F14 | | proposed |
| Rename a collection | F14 | | proposed |
| Delete a collection and its test cases | F14 | | proposed |
| Another user's collection is forbidden | F14 | | proposed |
| Guest cannot manage collections | F14 | | proposed |
| Successfully Review Test Case Script | F15 | F14 | report |
| Another user's test case is forbidden | F15 | | proposed |
| Guest cannot review | F15 | | proposed |
| The script stays escaped in the view | F15 | | proposed |
| Download Test Case Script | F16 | F15 | report |
| Another user's script cannot be downloaded | F16 | | proposed |
| No Test Case History Found | F17 | F15 | report |
| Test history is paginated | F17 | | proposed |
| History cannot be loaded | F17 | | proposed |
| Selecting a row opens the test case detail | F17 | F15 | proposed |
| Guest cannot open test history | F17 | | proposed |
| Update account information | F18 | | proposed |
| Reject an invalid email | F18 | | proposed |
| Guest cannot update the profile | F18 | | proposed |
| Confirm deletes the account | F19 | | proposed |
| Cancel keeps the account | F19 | | proposed |
| Documentation page renders | F20 | | proposed |

---

## 2. Scenarios by backlog item

Gherkin below follows the report's Behat-style wording. Steps flagged **(check code)** depend on behavior the agent must confirm in the repo before asserting.

### F1 Register (Must, Sprint 1)

```gherkin
Feature: Register

Scenario: Register Success
  Given I am on the "/register" page
  And I fill in "Name" with "Rizztul"
  And I fill in "Email" with "ristulop@gmail.com"
  And I fill in "Password" with "ristulristul"
  When I press the "Register" button
  Then I should see the message "Akun anda berhasil terdaftar"   (check code)
  And I should be redirected to the "/login" page
  And the account is saved

Scenario: Failed Register (missing fields)
  Given I am on the "/register" page
  And I leave one or all required fields blank
  When I press the "Register" button
  Then I should see the error message "Field wajib diisi"
  And I should remain on the "/register" page

Scenario: Failed Register (email already registered)
  Given I am on the "/register" page
  And I fill in "Email" with an already registered email "ristulop@gmail.com"
  When I press the "Register" button
  Then I should see the error message "Email sudah terdaftar"
  And I should remain on the "/register" page
  And no second account is created

Scenario: Failed Register (invalid format or weak password)
  Given I am on the "/register" page
  And I fill in an invalid email format or a password that does not meet criteria
  When I press the "Register" button
  Then I should see an error message corresponding to the invalid field
  And I should remain on the "/register" page

Scenario: Failed Register (database unavailable)   [proposed]
  Given the database cannot be reached
  When I press the "Register" button with valid data
  Then I should see the error message "Pendaftaran tidak dapat diproses saat ini"
```

Notes: validation order is required fields → email uniqueness → format. Password minimum in code is `min:8`. The database-unavailable scenario is hard to simulate in Pest; write it only if the repo has a clean way, otherwise report it as untested.

### F2 Login (Must, Sprint 1)

```gherkin
Feature: Login

Scenario: Login Success
  Given I am on the "/login" page
  And I fill in "Email" with "ristulop@gmail.com"
  And I fill in "Password" with "ristulristul"
  When I press the "Login" button
  Then I should be redirected to the "/dashboard"   (check code: likely the console route)
  And a session is active

Scenario: Failed Login (missing fields)
  Given I am on the "/login" page
  And I leave the "Email" or "Password" field blank
  When I press the "Login" button
  Then I should see the error message "Field wajib diisi"
  And I should remain on the "/login" page

Scenario: Failed Login (invalid credentials)
  Given I am on the "/login" page
  And I fill in an incorrect email or password
  When I press the "Login" button
  Then I should see the error message "Email atau password salah"
  And I should remain on the "/login" page

Scenario: Failed Login (account not found)   [proposed]
  Given no account exists for the email I enter
  When I press the "Login" button
  Then I should see the error message "Kredensial salah"   (check code: may not be implemented)

Scenario: Failed Login (service unavailable)   [proposed]
  Given the authentication service cannot be reached
  When I press the "Login" button
  Then I should see the error message "Login tidak dapat diproses saat ini"
```

Notes: the code currently emits `Email atau password salah.` with a trailing period. The last two scenarios may not exist in the code; report which are untestable instead of inventing behavior.

### F3 Logout (Must, Sprint 1) [proposed scenarios]

```gherkin
Feature: Logout

Scenario: Logout ends the session
  Given I am logged in
  And I am on the Profile page
  When I press the "Log Out" button
  Then my session should end
  And I should be redirected to a guest page   (check code for the exact target)

Scenario: Guest cannot log out
  Given I am not logged in
  When I send a logout request
  Then I should be redirected to the "/login" page   (check code)
```

Notes: the report specifies the button in the Profile sidebar but no flow. `POST /logout` is the route per `progress.md`.

### F4 Forgot Password (Should, Sprint 2, partial)

```gherkin
Feature: Forgot Password

Scenario: Forgot Password
  Given I am on the "/login" page
  When I click on the "Lupa Password" link
  Then I should be redirected to the "/reset-password" page   (check code for the real path)

Scenario: Reset password via email   [proposed]
  Given I am on the reset password page
  When I submit my registered email
  Then a reset link is sent to that email
  And I am sent back to the "/login" page after resetting
```

Notes: the POST is a placeholder today, so the second scenario is real TDD. Write the failing test first, then implement on the `Password::` broker.

### F5 Landing Page (Should, Sprint 1) [proposed scenarios]

```gherkin
Feature: Landing Page

Scenario: Landing page renders
  Given I am a visitor
  When I open the "/" page
  Then I should see the hero section and the feature sections

Scenario: Landing page calls to action
  Given I am on the landing page
  Then the primary button should link to the register page
  And the secondary button should link to further information
```

### F6 Input User Story & Acceptance Criteria (Must, Sprint 1)

```gherkin
Feature: Input User Story

Scenario: Failed Generate (missing user story)
  Given I am on the "Test Case" page
  And I leave the User Story field blank
  When I press the "Generate" button
  Then I should see the error message "Field wajib diisi"

Scenario: Story with several scenarios is split   [proposed]
  Given I fill in a story whose criteria contain two "Scenario:" headings
  When I press the "Generate" button
  Then two scenario rows are created for the test case

Scenario: Story without scenario headings falls back to one scenario   [proposed]
  Given I fill in a story with no scenario headings
  When I press the "Generate" button
  Then one scenario row is created
```

Notes: the parser accepts `Skenario:` and `Scenario:` headings. The draft test case is stored with status `pending` in the user's root collection if none is chosen. Existing coverage: `TestCaseDraftTest`, `ScenarioParserTest`.

### F7 Auth Profile Config (Must, Sprint 2)

```gherkin
Feature: Auth Profile Config

Scenario: Generate with an auth profile
  Given I am on the Config tab
  And I choose "Auth"
  And I add a credential row and a Login URL
  When I press the "Generate Test" button
  Then the auth profile is stored for the test case
  And the generator request includes the login URL and credentials
  And the generated script logs in before the scenarios

Scenario: Generate without auth
  Given I am on the Config tab
  And I choose "No auth"
  When I press the "Generate Test" button
  Then no auth profile is stored for the test case
  And the generated script has no login step

Scenario: Login failed during DOM extraction   [proposed]
  Given the target rejects the stored credentials
  When I press the "Generate Test" button
  Then I should see the error message "Gagal login. Periksa kredensial atau Login URL."
  And the test case status is "failed"

Scenario: Auth profile is stored encrypted
  Given I stored an auth profile for a test case
  Then the stored credentials are not readable in the database
  And reading the profile returns the original credentials

Scenario: The Config tab renders the auth profile form
  Given I am on the Config tab
  Then I should see the "Auth Profile" form
  And I should see the "No auth" and "Auth" options
  And I should see the "Login URL" field
  And I should see a credential row and a "Tambah kredensial" button

Scenario: The auth profile API never returns credential values
  Given I stored an auth profile for a test case
  When the auth profile is read back
  Then the response does not contain the credential values
```

Notes: this is the project's key differentiator (research question c). Credentials live only in the `auth_profiles` table and never in free text, logs, API responses, or scripts. The generated script therefore reads the credential values from environment variables at run time (`ATLAS_AUTH_<SLUG>`); the script itself only carries the login URL and the field selectors.

Existing coverage: `AuthProfileTest` (web), `test_f7_auth_login.py` and `test_f7_script_login.py` (service), `test_f7_login_script.py` (browser integration).

### F8 Scenario URL Table (Must, Sprint 1)

```gherkin
Feature: Scenario URL Table

Scenario: Prompt for Missing Scenario URLs
  Given I am on the "Test Case" page
  And I fill in the User Story without specifying URLs for each scenario
  When I press the "Generate" button
  Then the system displays a table for entering scenario URLs

Scenario: Confirm pre-filled scenario URLs   [proposed]
  Given every scenario already has a URL
  When I press the "Generate" button
  Then the table is shown pre-filled for me to confirm

Scenario: Reject an invalid scenario URL   [proposed]
  Given the URL table is shown
  When I submit a row with an empty or malformed URL
  Then I should see "Field wajib diisi." or "Format URL tidak valid."
```

Notes: Base URL pre-fills rows. Existing coverage: `ScenarioUrlTableTest`.

### F9 DOM Extraction (Must, Sprint 2, enabler) [technical]

```gherkin
Scenario: DOM source tiers
  Given a request with a "dom" snapshot
  Then meta.dom_source is "snapshot"
  Given a request with a reachable URL and no snapshot
  Then meta.dom_source is "live"
  Given a request with neither
  Then meta.dom_source is "minimal" and guessed targets are flagged "defaulted"

Scenario: Non-visual elements are removed from the DOM   [proposed]
  Given a page with script, style, svg, and canvas elements
  When the DOM is extracted
  Then the cleaned DOM contains none of them
  And the interactive elements are still there

Scenario: The DOM is extracted from a protected page   [proposed]
  Given the target requires a login
  And an auth profile is supplied
  When the DOM is extracted
  Then the login happens before the page is read
  And the extracted DOM is the page behind the login form
```

Tests: pytest, `tests/unit/` (fixtures) and `tests/integration/` (headless Chrome). Existing coverage: `test_f9_dom_source_tiers.py`, `test_f9_dom_extraction.py`, `test_f9_f11_dom_validation.py`, `test_f9_live_dom.py`.

### F10 LLM Extraction (Must, Sprint 1, enabler) [technical]

```gherkin
Scenario: Plain story becomes structured steps
  Given a plain-language story with Gherkin criteria
  When the LLM transport returns a valid extraction
  Then the result has scenarios whose steps follow Given, When, Then
  And no special markup was required in the input
```

Tests: pytest with an injected `httpx` transport. No real LLM.

### F11 JSON Validation & Retry (Must, Sprint 2, enabler) [technical]

```gherkin
Scenario: Invalid output is retried then fails
  Given the LLM keeps returning output that fails validation
  When extraction runs
  Then it retries up to the configured maximum (hard cap 3)
  And finally returns the error code "invalid_response"
  And meta.attempts records the number of attempts

Scenario: Stage order must follow Given, When, Then   [proposed]
  Given the LLM returns a step that goes back to "Given" after a "When"
  When extraction runs
  Then the output is rejected
  And the LLM is asked again with the reason

Scenario: A target that is not in the DOM is rejected   [proposed]
  Given the LLM returns a step pointing at an element the page does not have
  When extraction runs
  Then the output is rejected as a hallucinated target
  And the LLM is asked again with the reason
```

Notes: only the extraction layer retries. The retry limit is a hard cap of 3, and `meta.attempts` counts every attempt including the first. Existing coverage: `test_f11_retry.py`, `test_validator.py`, `test_f9_f11_dom_validation.py`.

### F12 Rule-based Script Generator (Must, Sprint 1, enabler)

```gherkin
Feature: Generate Test Case Script

Scenario: Successful Script Generation
  Given I am on the "Test Case" page
  And I fill in the User Story / Acceptance Criteria and optional Base URL
  When I press the "Generate" button
  Then I should see a confirmation that the script was successfully generated
  And I should still be on the "Test Case" page
  And the test case status is "completed"
  And the script is saved

Scenario: Each action and locator renders   [technical]
  Given a validated extraction using each action and locator type
  When the script is generated
  Then the output matches the reviewed golden fixture
```

Notes: "Successful Script Generation" is the end-to-end scenario for F6, F8, F9, F10, F11 and F12. Existing coverage: `GenerateFlowTest`. The technical scenario maps to the existing goldens; do not hand-edit them.

### F13 Error Handling for Generate (Must, Sprint 2)

```gherkin
Feature: Generate errors

Scenario: Failed Generate (target URL unreachable)
  Given I have filled in the test case generation data
  When I press the "Generate" button
  And the target web application is down or unreachable
  Then I should see the error message "URL tidak dapat diakses"
  And the test case status is "failed"

Scenario: Failed Generate (LLM service unavailable)
  Given I have filled in the test case generation data
  When I press the "Generate" button
  And the LLM service encounters a timeout or server error
  Then I should see the error message "Proses generate gagal, layanan tidak tersedia"
  And the test case status is "failed"

Scenario: Failed Generate (no matching DOM elements)   [proposed]
  Given extraction cannot match any DOM element after the maximum retries
  When I press the "Generate" button
  Then I should see the generic generation-failure message
  And the test case status is "failed"
```

Notes: use the existing `ErrorCopy` map for exact strings. Never show raw service messages to users. Existing coverage: `ErrorCopyTest`, `GeneratorClientTest`.

### F14 Collection Management (Should, Sprint 2) [proposed scenarios]

```gherkin
Feature: Collection Management

Scenario: Create a collection
  Given I am logged in
  When I create a collection with a name
  Then it appears in my sidebar

Scenario: Rename a collection
  Given I own a collection
  When I change its name
  Then the new name is saved

Scenario: Delete a collection and its test cases
  Given I own a collection with test cases
  When I delete the collection
  Then the collection and its test cases are removed

Scenario: Another user's collection is forbidden
  Given a collection belongs to another user
  When I try to update or delete it
  Then the request is forbidden
```

Notes: existing coverage is 8 Pest feature tests. Add a guest-redirect case.

### F15 Review Test Case Script (Must, Sprint 2)

```gherkin
Feature: Review Test Case Script

Scenario: Successfully Review Test Case Script
  Given I am on the "Test Case" page
  And at least one generated test case exists
  When I select a test case to review
  Then I should see the details of the generated Selenium script
  And I should see the scenario URLs

Scenario: Another user's test case is forbidden   [proposed]
  Given a test case belongs to another user
  When I try to open it
  Then the request is forbidden

Scenario: Guest cannot review   [proposed]
  Given I am not logged in
  When I open a test case
  Then I am redirected to the "/login" page

Scenario: The script stays escaped in the view   [proposed]
  Given a generated script contains markup
  When I open the test case
  Then the markup is shown as text and not rendered
```

Notes: existing coverage is `TestCaseReviewTest` and `ConsoleScriptTest`. The script must stay escaped in the view.

### F16 Download Script (Must, Sprint 3, shipped early)

```gherkin
Feature: Download Script

Scenario: Download Test Case Script
  Given I am on one of the "Test Case" pages
  When I press the "Download Script" button
  Then the Selenium script file should be downloaded to my device

Scenario: Another user's script cannot be downloaded   [proposed]
  Given a test case belongs to another user
  When I request its download
  Then the request is forbidden
```

Notes: already routed as `test-cases.script.download` and covered by `ConsoleScriptTest`.

### F17 Test History (Should, Sprint 2)

```gherkin
Feature: Test History

Scenario: No Test Case History Found
  Given I am on the "Test Case" page
  And no test case history has been created yet
  Then I should see the message "Belum ada riwayat test case."   (check code)

Scenario: Test history is paginated   [proposed]
  Given I have more than 6 test cases
  When I open the Profile "Test History" tab
  Then I see 6 per page with status and scenario counts

Scenario: History cannot be loaded   [proposed]
  Given the database cannot be reached
  When I open the Profile "Test History" tab
  Then I should see the message "Data tidak dapat dimuat saat ini"

Scenario: Selecting a row opens the test case detail   [proposed]
  Given I have at least one test case in my history
  When I select a row
  Then I should see the details of that test case
```

Notes: the empty state and the load failure are different messages, so a failed load never looks like an empty history. The copy is `Belum ada riwayat test case.` for the empty state and `Data tidak dapat dimuat saat ini` for a failed load. A row links to the test case detail, so the history is an entry point to the review flow. Existing coverage: `TestHistoryTest`.

### F18 Profile Settings (Could, Sprint 2) [proposed scenarios]

```gherkin
Feature: Profile Settings

Scenario: Update account information
  Given I am logged in
  When I change my name and email and save
  Then the new information is saved

Scenario: Reject an invalid email
  Given I am on the Settings tab
  When I save an invalid email
  Then I should see an error for the email field
```

### F19 Delete Account (Could, Sprint 3, shipped early) [proposed scenarios]

```gherkin
Feature: Delete Account

Scenario: Confirm deletes the account
  Given I am on the Settings tab
  When I confirm account deletion
  Then my account, collections and test cases are removed
  And I am logged out

Scenario: Cancel keeps the account
  Given the confirmation modal is open
  When I cancel
  Then my account is unchanged
```

### F20 Documentation Page (Should, Sprint 3, pending) [proposed scenarios]

```gherkin
Feature: Documentation Page

Scenario: Documentation page renders
  Given I am a visitor
  When I open the documentation page
  Then I should see the category sidebar and the topic content
```

Notes: no route, view, or controller exists yet, so this is real TDD.

---

## 3. Example test (the only one; follow this pattern for every scenario)

```php
<?php

use App\Models\User;

describe('F1 Register', function () {
    test('Failed Register (email already registered)', function () {
        // Given I am on the "/register" page
        $existing = User::factory()->create(['email' => 'ristulop@gmail.com']);
        $this->get(route('register'))->assertOk();

        // And I fill in "Email" with an already registered email "ristulop@gmail.com"
        $payload = [
            'name' => 'Rizztul',
            'email' => $existing->email,
            'password' => 'ristulristul',
        ];

        // When I press the "Register" button
        $response = $this->from(route('register'))->post(route('register.store'), $payload);

        // Then I should see the error message "Email sudah terdaftar"
        $response->assertSessionHasErrors(['email' => 'Email sudah terdaftar.']);

        // And I should remain on the "/register" page
        $response->assertRedirect(route('register'));

        // And no second account is created
        expect(User::where('email', $existing->email)->count())->toBe(1);
    });
});
```

Adjust the route names and the exact error string to what the code emits.

---