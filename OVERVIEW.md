# Project Overview (Ground Truth)

## 1. Purpose
A web system that turns **natural-language user stories** (with Gherkin-style acceptance criteria) into **automated browser test scripts**. No special syntax or markup is required in the input.

**Problem addressed (prior work):**
- Existing tools require user stories in a modified, markup-heavy format (explicit element IDs, action tags, URL markers), which is inflexible.
- They fail on pages behind authentication.

**Solution:**
- An LLM extracts structured information from plain-language stories.
- A deterministic, rule-based converter turns the validated result into a script.
- An explicit **auth profile** (credentials + login URL) lets scripts log in before running the scenarios.

**Research questions:**
- How to extract information from free-form user stories with an LLM.
- How to convert the extraction into an automated test script.
- How well the output matches story intent, including authenticated scenarios.

## 2. Actor and Scope
- **Single actor:** Tester (QA).
- **Four core use cases:** Register, Login, Generate Test Case Script, Review Test Case Script.
- **Backlog extras beyond the report's use cases:** Logout, Collection Management, Profile Settings, Delete Account, Landing Page, Documentation Page.
- **Output format:** browser-automation scripts. The report targets Selenium, so treat that as the default output format, but keep the pipeline decoupled from it.

## 3. Process (As-Is → To-Be)
**As-Is (manual):** write story → open target app → inspect HTML elements by hand → hand-write script → run. Slow and skill-dependent.

**To-Be (system-assisted):**
1. The user enters the story and acceptance criteria.
2. If the target needs login, the user supplies an auth profile and login URL.
3. If scenarios lack URLs, the user supplies a URL per scenario.
4. The system extracts page elements (DOM) from the target.
5. The LLM extracts structured info (JSON) from the story.
6. The system validates the result and retries on failure.
7. A rule-based converter generates the script.
8. The user reviews and downloads the script, then runs it.

## 4. Functional Requirements

**Register**
- Input: name, email, password.
- Validation order: required fields → email uniqueness → email/password format.
- On success: save the account and redirect to Login.
- Errors: "Field wajib diisi", "Email sudah terdaftar", a field-specific format error, "Pendaftaran tidak dapat diproses saat ini" (DB unavailable).

**Login**
- Input: email, password.
- Validation: required fields → service availability → account exists → credentials match.
- On success: start a session and redirect to the dashboard.
- Alternate flow: "Lupa Password" leads to reset (via email) and back to Login.
- Errors: "Field wajib diisi", "Email atau password salah", "Kredensial salah" (account not found), "Login tidak dapat diproses saat ini".

**Logout** (backlog only): the report specifies the Log Out button in the Profile sidebar but no flow.

**Generate Test Case Script** (requires login)
- Inputs:
  - Test case name.
  - User story and Gherkin acceptance criteria (required).
  - Base URL (optional).
  - Auth config: no auth, or auth with key-value credentials plus login URL.
- Flow:
  - Validate input.
  - Check URL coverage. If any scenario lacks a URL, show a table to fill in. If all are present, show a pre-filled table to confirm.
  - Extract the DOM from the target.
  - Send the story to the LLM for structured JSON.
  - Validate the JSON against the schema and the DOM elements.
  - On invalid or incomplete output, retry up to a maximum.
  - Convert the valid output to a script and mark the test case completed.
- Errors:
  - "Field wajib diisi" (missing story).
  - "URL tidak dapat diakses" (target down).
  - "Proses generate gagal, layanan tidak tersedia" (LLM timeout or error).
  - A generic generation-failure message when no matching DOM elements are found after retries.
- Any failure sets the test case status to `failed`.

**Review Test Case Script** (requires login and at least one generated test case)
- Browse collections (sidebar) → select a collection → select a test case → view script details → optional "Download Script".
- Alternate entry: Profile → Test History.
- Errors: "Belum ada hasil test case" (no history), "Data tidak dapat dimuat saat ini" (DB unavailable).

## 5. UI Structure (low-fidelity)
- **Global nav:** Docs, Profile, Console, Log in.
- **Landing:** hero with primary and secondary calls to action, illustrated feature sections, a closing call to action and footer.
- **Console (core page):**
  - Left sidebar: "Test Scenario", with collections that expand to test-case history.
  - Main tabs:
    - *User Story:* title, large text area, optional Base URL, "Generate Test" button.
    - *Config:* No auth / Auth toggle. With Auth: key-value credential table (add rows) and Login URL.
  - Results panel (after generate), with tabs:
    - *Scenario URL:* table of Scenario and Initial URL.
    - *Generated Script:* large script view.
- **Profile:** left card with avatar and Log Out. Tabs:
  - *Test History:* paginated list of name, status (Succeed/Fail) and extra info.
  - *Settings:* account info, two notification toggles, API/integrations, delete account.
- **Documentation:** collapsible category sidebar, topic content (before/after illustration, sample code, 3-step generate flow, info cards), "on this page" index, tip card.

## 6. Data Model
| Entity | Key fields | Relationships |
|---|---|---|
| users | id, name, email, password, timestamps | 1 → many collections |
| collections | id, user_id, name, timestamps | 1 → many test_cases |
| test_cases | id, collection_id, name, user_story, base_url, status, timestamps | 1 → many scenarios; 1 → 1 llm_outputs; 0..1 auth_profile |
| scenarios | id, test_case_id, name, scenario_url, timestamps | many → 1 test_case |
| auth_profiles | id, test_case_id, credentials, login_url, timestamps | 0..1 per test_case |
| llm_outputs | id, test_case_id, output (JSON), validation_status, retry_count, timestamps | 1 → 1 generated_scripts |
| generated_scripts | id, llm_output_id, content, status, unresolved_step, created_at | 1 per llm_output |

**Test-case status values seen:** `failed`, `completed`.

## 7. Architecture (logical roles)
- **Interface layer:** Register, Login, Console pages.
- **Controllers:**
  - Auth: register, login, validate, session.
  - TestCase: store, update, validate input, URL check, orchestration.
  - Collection: index, show.
- **Services:**
  - LLM service: extract user story, validate and retry extraction.
  - Script generator: extract DOM, convert to script.
- **Entities:** as in section 6.

## 8. Delivery Plan (Sprint 1, Week 9 progress report)

**Effort scale:** S=1, M=2, L=3, XL=4, XXL=5. **Total: 48 points.**
**Priority:** MoSCoW (Must / Should / Could Have).

### 8.1 Backlog
| # | Backlog item | Priority | Size | Pts | Sprint |
|---|---|---|---|---|---|
| 1 | Register | Must | S | 1 | 1 |
| 2 | Login | Must | S | 1 | 1 |
| 3 | Logout | Must | S | 1 | 1 |
| 4 | Forgot Password | Should | M | 2 | 2 |
| 5 | Landing Page | Should | M | 2 | 1 |
| 6 | Input User Story & Acceptance Criteria | Must | M | 2 | 1 |
| 7 | Auth Profile Config | Must | XL | 4 | 2 |
| 8 | Scenario URL Table | Must | M | 2 | 1 |
| 9 | DOM Extraction | Must | XL | 4 | 2 |
| 10 | LLM Extraction | Must | XL | 4 | 1 |
| 11 | JSON Validation & Retry | Must | L | 3 | 2 |
| 12 | Rule-based Script Generator | Must | XXL | 5 | 1 |
| 13 | Error Handling (Generate) | Must | M | 2 | 2 |
| 14 | Collection Management | Should | M | 2 | 2 |
| 15 | Review Test Case Script | Must | M | 2 | 2 |
| 16 | Download Script | Must | S | 1 | 3 |
| 17 | Test History | Should | M | 2 | 2 |
| 18 | Profile Settings | Could | L | 3 | 2 |
| 19 | Delete Account | Could | S | 1 | 3 |
| 20 | Documentation Page | Should | XL | 4 | 3 |

### 8.2 Sprint Plan
| Sprint | Weeks | Items | Points |
|---|---|---|---|
| 1 | 10–11 | 1, 2, 3, 5, 6, 8, 10, 12 | 18 |
| 2 | 12–13 | 4, 7, 9, 11, 13, 14, 15, 17, 18 | 24 |
| 3 | 14–15 | 16, 19, 20 | 6 |

### 8.3 Spec-to-Backlog Mapping
- **Register / Login / Logout / Forgot Password** → items 1–4
- **Generate flow** → items 6 (input), 7 (auth), 8 (URL table), 9 (DOM), 10 (LLM), 11 (validate/retry), 12 (converter), 13 (errors)
- **Review flow** → items 14 (collections), 15 (review), 16 (download), 17 (history)
- **Profile / Docs / Landing** → items 18, 19, 20, 5

### 8.4 Planning Risks
- **Sprint 1 dependency gap:**
  - The LLM extraction (10) and script generator (12) are planned before DOM extraction (9) and validation/retry (11).
  - The validation step checks the JSON against DOM elements, so Sprint 1 can only produce ungrounded or mocked-element output. A true end-to-end flow arrives in Sprint 2.
- **Authenticated testing is deferred:** Auth Profile Config (7, XL) lands in Sprint 2. It is the project's key differentiator from prior work, so slippage there directly affects research question (c).
- **Load imbalance:** Sprint 2 has 24 points (50% of the total) against 6 in Sprint 3. This is the most likely overrun point, and moving a few items would balance it, for example Test History (17) or Profile Settings (18) into Sprint 3.
- **Priority vs. order:**
  - Profile Settings (Could, 18) is scheduled in Sprint 2 ahead of the Should-Have Documentation Page (20).
  - Download Script (Must, 16) is in Sprint 3 even though Review (15) is in Sprint 2.
  - Neither is blocking, but the review flow won't be complete until Sprint 3.
- **Estimate caveat:** the Script Generator (XXL) is the largest single item and carries the most uncertainty. The retry limit and JSON schema are still undefined (see section 9).