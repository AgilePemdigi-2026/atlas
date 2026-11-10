# ATLAS: Product Requirements Document

**Automated Test case generation via LLM-Assisted Selenium scripting**

Prepared from: SRS v1.0 (5 Oct 2026) and Sprint Planning, Sprint 1 / Week 9 · Group 4, Universitas Airlangga · Status: Draft for team review

*Items marked **\[Proposed\]** are not in the source documents and need team confirmation. Items marked **\[Gap\]** are inconsistencies or missing pieces found while consolidating the sources (see Section 15).*

## 1. Overview

ATLAS is a web application that turns a Tester’s user story and Gherkin-style acceptance criteria into a runnable Python Selenium test script.

**How it works:** ATLAS opens the target web app with Selenium and extracts its DOM. It sends the user story and the cleaned DOM to an LLM, which returns structured JSON test steps. A validator checks that JSON against a schema and against the real DOM elements (anti-hallucination), retrying up to 3 times with error feedback. A deterministic rule-based converter then turns the valid JSON into a Selenium script. The Tester reviews, runs, and downloads the script.

**Product principle:** the LLM never writes code directly, and unvalidated output is never shown as a result or executed. ATLAS assists the Tester and does not replace the Tester’s judgment. The Tester supplies the inputs, URLs, and credentials, and reviews every script before use.

**Stack:** Laravel (Blade, Alpine.js, Tailwind) frontend and backend, a separate Python microservice for generation, Selenium WebDriver (headless Chrome/Chromium), PostgreSQL, and an external LLM.

## 2. Problem Statement

- **Manual scripting is slow.** Testers must open the target app, find HTML elements, hand-write Selenium scripts, and run them. This is repetitive and brittle.
- **Prior tooling is limited.** The rule-based user-story-to-Selenium approach of Raharjana et al. (2023) reported a 72% success rate and failed on features behind authentication.
- **Raw LLM output is untrustworthy.** LLMs can read natural-language stories but may invent element locators or actions that do not exist on the page.
- **Opportunity.** Constrain the LLM to emit validated JSON grounded in the real DOM, convert it deterministically, and support authenticated targets through Auth Profiles.

## 3. Goals and Non-Goals

**Goals**

1. **G1.** Reduce the manual effort of authoring Selenium scripts from user stories.
2. **G2.** Produce reliable scripts by validating every LLM output against schema and DOM before conversion.
3. **G3.** Support target apps that require login (the weakness of prior work).
4. **G4.** Keep results reproducible and reviewable: same JSON, same script; every generated script is stored and re-downloadable.
5. **G5.** Keep the Tester in control with explicit review before use, and clearly flagged default values.

**Non-goals**

- Test frameworks or platforms other than Python Selenium.
- Replacing Tester decisions on inputs, URLs, authentication, or review.
- CI/CD integration or a general test-management suite.
- Multi-role administration. The only actor is the Tester.
- Executing or presenting any LLM output that failed validation.

## 4. Success Metrics

**Primary metric (from SRS 6.7):** success rate = test cases that pass ÷ total test cases evaluated, as a percentage.

| Metric | Definition | Target |
| --- | --- | --- |
| Overall success rate | Pass rate across the evaluation user-story set | Beat manual baseline and the 72% reported by Raharjana et al. (2023) **\[Proposed: ≥ 72% as the floor, final target to be set\]** |
| Auth-scenario success rate | Pass rate on scenarios behind login, reported separately | Show measurable improvement over prior work |
| Time to result | Click “Generate” to script displayed | ≤ 60 s on first-attempt success; failed above 180 s |
| Retry rate | Share of generations needing 1–3 retries (attempt count is recorded) | Tracked **\[Proposed\]** |
| Validation failure rate | Share of runs ending in failed status after retries | Tracked **\[Proposed\]** |
| Script reuse | Share of completed test cases downloaded or run | Tracked **\[Proposed\]** |

Evaluation configuration (LLM model, prompt version, retry limit) must be recorded with every result for reproducibility. The number and mix of evaluation user stories is still open.

## 5. Users and Use Cases

**Primary user: Tester.** Registers, logs in, enters user stories and acceptance criteria, configures authentication for the target app, generates scripts, reviews and runs them, and downloads the results.

**Secondary stakeholders:** the developers who build and maintain the system (they use this document as a build reference), and the project evaluators who review requirements fit.

**Core user stories (from SRS Appendix B)**

1. As a Tester, I want to register a new account so that I can log in and access all available features.
2. As a Tester, I want to authenticate with email and password so that I can start an active session.
3. As a Tester, I want to input the target web app URL and a user story so that the system can generate an automated Selenium test script.
4. As a Tester, I want to review and download the generated script so that I can inspect test details and use the file.

## 6. Scope and Prioritized Backlog

Priorities are MoSCoW. Effort uses T-shirt sizes scored S=1, M=2, L=3, XL=4, XXL=5 (total 48 points).

| # | Feature | Priority | Effort | Sprint |
| --- | --- | --- | --- | --- |
| 1 | Register | Must | S | 1 |
| 2 | Login | Must | S | 1 |
| 3 | Logout | Must | S | 1 |
| 4 | Forgot Password | Should | M | 2 |
| 5 | Landing Page | Should | M | 1 |
| 6 | Input User Story and Acceptance Criteria | Must | M | 1 |
| 7 | Auth Profile Config | Must | XL | 2 |
| 8 | Scenario URL Table | Must | M | 1 |
| 9 | DOM Extraction | Must | XL | 2 |
| 10 | LLM Extraction | Must | XL | 1 |
| 11 | JSON Validation and Retry | Must | L | 2 |
| 12 | Rule-based Selenium Script Generator | Must | XXL | 1 |
| 13 | Error Handling for Generate | Must | M | 2 |
| 14 | Collection Management | Should | M | 2 |
| 15 | Review Test Case Script | Must | M | 2 |
| 16 | Download Script | Must | S | 3 |
| 17 | Test History | Should | M | 2 |
| 18 | Profile Settings | Could | L | 2 |
| 19 | Delete Account | Could | S | 3 |
| 20 | Documentation Page | Should | XL | 3 |

**Not in the backlog but specified in the SRS:** Run Test (execute a script and record Succeed/Fail). See Section 15.

## 7. Core User Flow

1. The Tester registers or logs in and lands on the **Console**.
2. The Tester enters a scenario title, the user story, and Gherkin acceptance criteria. Base URL is optional.
3. On the **Config** tab, the Tester chooses *No Auth* or *Auth* (key-value credentials plus Login URL).
4. The Tester presses **Generate**. If any scenario lacks a URL, the **Scenario URL** table appears to fill in or confirm.
5. The system logs in to the target if needed, then extracts and cleans the DOM.
6. The system sends the user story and DOM to the LLM and receives JSON steps.
7. The system validates the JSON. If invalid, it feeds the errors back to the LLM and retries, up to 3 times.
8. The system converts valid JSON to a Selenium script, saves it, and shows it on the **Generated Script** tab.
9. The Tester reviews the script (default values are flagged), optionally runs it, and downloads it. Test history is available from Profile.

## 8. Functional Requirements

### 8.1 Accounts and Authentication

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-AUTH-1 | Register page with name, email, and password (minimum 8 characters) and a “Register” button. | Must |
| FR-AUTH-2 | Validate required fields (“Field wajib diisi”), email format, and password criteria, with an error per offending field. | Must |
| FR-AUTH-3 | Reject an already-used email with “Email sudah terdaftar”. | Must |
| FR-AUTH-4 | On success, save the account, show “Akun anda berhasil terdaftar”, and redirect to Login. | Must |
| FR-AUTH-5 | If the database is unreachable, show “Pendaftaran tidak dapat diproses saat ini” and keep the Tester on Register. | Must |
| FR-AUTH-6 | Login with email and password. Unknown account: “Kredensial salah”. Wrong password: “Email atau password salah”. Empty fields: “Field wajib diisi”. | Must |
| FR-AUTH-7 | On successful login, create an active session and redirect to the Console. | Must |
| FR-AUTH-8 | If the auth service or database is unreachable, show “Login tidak dapat diproses saat ini” and stay on Login. | Must |
| FR-AUTH-9 | Log Out from the Profile sidebar ends the session. | Must |
| FR-AUTH-10 | “Lupa Password” leads to a reset page, email-based reset, then back to Login. | Should |
| FR-AUTH-11 | Everything except Landing, Register, and Login requires an active session. | Must |

### 8.2 Generate Test Case Script (core / MVP)

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-GEN-1 | Console form: scenario title, User Story and Gherkin acceptance criteria text area, optional Base URL, Config tab, “Generate” button. | Must |
| FR-GEN-2 | If the User Story is empty, show “Field wajib diisi” and do not call the LLM. | Must |
| FR-GEN-3 | Scenario URL table: if any scenario lacks a URL, show an empty table to fill; if all have URLs, show the filled table for confirmation. | Must |
| FR-GEN-4 | Save each scenario with its confirmed URL before extraction continues. | Must |
| FR-GEN-5 | Extract DOM with Selenium (running auth first if configured), removing non-visual elements (script, style, svg, canvas). Keep the cleaned DOM as the validation reference. | Must |
| FR-GEN-6 | Send the user story and cleaned DOM to the LLM in a single prompt and request JSON output. | Must |
| FR-GEN-7 | Validate output per Section 9.2 and store the validation status. | Must |
| FR-GEN-8 | On invalid or incomplete output, retry with the validator’s error message, up to 3 retries, recording the retry count. | Must |
| FR-GEN-9 | Convert valid JSON to a Selenium script with the deterministic rule-based converter. | Must |
| FR-GEN-10 | Save the LLM output and script, set status to *completed*, show a success confirmation, and show the script on the Generated Script tab. | Must |
| FR-GEN-11 | On failure, set status to *failed* and show: “URL tidak dapat diakses” (target unreachable); “Proses generate gagal, layanan tidak tersedia” (LLM timeout or server error); or a generic generate-failure message when no matching DOM elements are found after retries. | Must |
| FR-GEN-12 | A failure must not delete prior data, lose the Tester’s input, or affect other jobs. A failed test case is never shown as a successful script. | Must |

### 8.3 Auth Profile

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-AP-1 | Config tab offers *No Auth* or *Auth*. | Must |
| FR-AP-2 | When *Auth* is selected, the Tester can add credentials as key-value rows (“+” button) and enter a Login URL. | Must |
| FR-AP-3 | Before extracting DOM from a protected page, Selenium opens the Login URL and logs in. The generated script includes the login steps before the scenario steps. | Must |
| FR-AP-4 | If login fails, stop and tell the Tester to check the credentials or Login URL. | Must |
| FR-AP-5 | Credentials never appear in logs or API responses and travel only over HTTPS. A test case has at most one auth profile. | Must |
| FR-AP-6 | Auth profiles are linked to a test case for reuse, stored encrypted. Storage policy is unresolved **\[Gap\]**. | Should |

### 8.4 Review, Run, and Download

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-REV-1 | Console sidebar “Test Scenario” lists the Tester’s collections, each expanding to its test cases. | Must |
| FR-REV-2 | Selecting a test case shows its name, status, Selenium script, and last execution result if any. | Must |
| FR-REV-3 | “Download Script” saves the script file to the Tester’s device. Content is identical to what is displayed, the filename is sanitized, and re-downloading needs no regeneration. | Must |
| FR-REV-4 | “Run Test” executes a *completed* test case in an isolated headless browser and shows Succeed or Fail with the failing step or message. Each run is stored with a timestamp without overwriting earlier runs. Timeout or browser failure shows “Eksekusi gagal” and never alters the stored script. **\[Gap: no backlog item\]** | Must |
| FR-REV-5 | Collection management: group test cases into collections owned by a single user. Create, rename, and delete behavior is not specified **\[Gap\]**. | Should |
| FR-REV-6 | Empty state: “Belum ada hasil test case”. Load failure: “Data tidak dapat dimuat saat ini”. | Must |

### 8.5 Profile, History, Documentation, Landing

| ID | Requirement | Priority |
| --- | --- | --- |
| FR-PRO-1 | Profile → Test History: test case name and Succeed/Fail status with pagination. Selecting a row opens the test case detail. | Should |
| FR-PRO-2 | Profile → Settings: account information, two notification toggles, API/Integrations settings, and permanent Delete Account. Behavior of notifications and integrations is unspecified **\[Gap\]**. | Could |
| FR-DOC-1 | Documentation page: collapsible category navigation, topic content with a before/after user-story-to-test-case illustration, code sample, three-step generate flow, “on this page” table of contents, and a tip card. | Should |
| FR-LP-1 | Landing page: hero with title, description, primary (register) and secondary (learn more) buttons, three explanatory sections, and a footer call-to-action. | Should |

## 9. LLM Output Contract and Generation Rules

### 9.1 JSON schema

| Field | Required | Notes |
| --- | --- | --- |
| scenario | Yes | Non-empty scenario name |
| steps | Yes | List with at least one step |
| stage | Yes | Given, When, or Then |
| action | Yes | One of the supported actions below |
| by | Conditional | Locator method (for example id). Required for element-targeting actions |
| target | Conditional | Element identifier. Required when *by* is set |
| value | Conditional | Value for fill, select, navigate, and assert actions |

Non-JSON output counts as one failed attempt.

### 9.2 Validation rules (run on every output)

| # | Rule | Type |
| --- | --- | --- |
| 1 | Output parses as JSON | Structure |
| 2 | All required fields present and correctly typed | Schema |
| 3 | Stage is Given, When, or Then, in the order Given → When → Then | Schema |
| 4 | Action is in the supported list | Schema |
| 5 | Every referenced target exists in the extracted DOM | Anti-hallucination |
| 6 | Action fits the element type (for example fill only on inputs) | Anti-hallucination |

If validation fails, the validator’s error is sent back to the LLM and extraction repeats, up to 3 retries. After that the run stops and reports a generate failure.

### 9.3 Supported actions and script mapping (initial set)

| Action | Selenium behavior |
| --- | --- |
| navigate | Open the URL in *value* |
| fill | Clear the target element, then enter *value* |
| click | Click the target element |
| select | Choose option *value* in the target dropdown |
| assert\_url | Verify the current URL equals *value* |
| assert\_text | Verify the text in *value* appears on the page |

Generated scripts include browser setup, waits before each interaction, and browser teardown. Scripts are treated as plain text when stored and displayed.

### 9.4 Default values for abstract user stories

- If a story gives no form values, the system picks defaults from the detected DOM element type (email, password, text, number).
- Defaults are clearly marked in the generated script so the Tester can spot them.
- The Tester can replace defaults before use. Scenarios needing valid data (for example, successful login) should use auth profile values.

## 10. UX and Screens

All screens are web pages for one actor, the Tester.

- **Landing, Register, Login:** Register and Login share a split layout with a product intro and a sample Gherkin scenario on the left and the form on the right. The header has Docs, Profile, Console links and a “Log in” button.
- **Console:** left sidebar “Test Scenario” with collections and history. The main area has two tabs, **User Story** (title, story and criteria, Base URL, Generate Test) and **Config** (No Auth / Auth, credentials table, Login URL). After generation, **Scenario URL** and **Generated Script** tabs appear.
- **Profile:** Test History and Settings tabs, with profile photo and Log Out in the sidebar.
- **Documentations:** categorized sidebar, content area, and on-page table of contents.
- **UX requirements:** loading indicator appears within 200 ms, the interface stays responsive during generation and execution, and success and error messages are clear. Final wireframes are still to be completed.

## 11. Technical Architecture

**Components**

- **Browser:** user interface.
- **Laravel (PHP):** authentication, request handling, orchestration, the only component that talks to PostgreSQL, and script storage and download.
- **Python microservice:** DOM extraction, LLM call, validation, and rule-based conversion. Laravel calls it over internal HTTP with JSON.
- **Selenium WebDriver:** headless Chrome/Chromium, used both to extract the DOM and to run scripts.
- **LLM:** external service called with a single prompt per attempt.
- **PostgreSQL:** relational store.

**Data flow:** Browser → Laravel → Python service → Selenium → target app, then → LLM → validator → converter → Laravel → PostgreSQL → Browser. HTTPS and REST with JSON are used between client and server.

**Main entities:** users, collections, auth\_profiles, test\_cases, scenarios, llm\_outputs, generated\_scripts, plus stored execution results.

**Rules:** a collection belongs to one user. A test case has at most one auth profile and one or more scenarios.

**Generate request fields:** base\_url, user\_story, acceptance\_criteria, auth\_profile (login\_url, username, password). Responses return status, message, and data.script. Errors return status “error” with a message.

## 12. Non-Functional Requirements

| Area | Requirement |
| --- | --- |
| Performance | Result in ≤ 60 s on a first-attempt success, failure declared past 180 s. LLM call timeout 30 s. Page load for DOM extraction timeout 30 s. Validation plus conversion ≤ 2 s per scenario. Collection list and test case detail shown ≤ 3 s. |
| Concurrency | Limit simultaneous Selenium executions. Excess requests queue. |
| Security | HTTPS (TLS 1.2+). Passwords hashed with bcrypt or argon2. API keys and DB credentials server-side only, never in repo, responses, or logs. Parameterized queries. Input validated on client and server. LLM text and scripts escaped on display to prevent XSS. Backend enforces per-user ownership of collections, test cases, scripts, and results. |
| Safety | Only validated, rule-converted scripts are ever executed, and only when the Tester requests it. Execution is isolated with limited privileges, can reach only the URL entered for that test case, and cannot read server files or config. Failures never corrupt stored data. |
| Privacy | Tester is told that user stories are sent to an external LLM provider and should not contain confidential data. |
| Reliability | Clear errors when the LLM or target is down, with no loss of input and no impact on other jobs. |
| Usability | Simple UI, clear messages, a Documentation page. |
| Maintainability | Modular code (boundary, control, entity). LLM extraction and rule-based conversion are separate components. |
| Portability | Any modern browser on Windows, Linux, or macOS. Generated scripts run in a Python Selenium environment. |
| Efficiency | LLM calls, target access, and Selenium runs are limited to what is necessary. |

## 13. Business Rules

1. Generate and Review are available to logged-in Testers only.
2. Scripts come only from LLM output that passes schema and DOM validation.
3. Generated scripts must be reviewed by the Tester before use or execution.
4. Execution happens only on Tester request, for test cases w