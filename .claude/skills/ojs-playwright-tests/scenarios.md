# Scenario API & Mailpit

The scenario endpoints assemble realistic submission/journal state in one POST. They live behind `TestModeGate` (key + `APPLICATION_ENV=test`) and are the canonical alternative to driving the UI for setup. Mailpit is the test-side SMTP catcher that the suite asserts against for emails sent during *test actions* (decisions, password resets, invitations) — scenario-side mail is dropped on the floor by `Mail::fake()`.

## Endpoints

Two routes, dispatched from `api/v1/_test/index.php`:

- `POST /api/v1/_test/scenarios/journal` — `JournalScenarioController` (extends `PKPContextScenarioController`)
- `POST /api/v1/_test/scenarios/submission` — `SubmissionScenarioController` (extends `PKPSubmissionScenarioController`; OJS uses the shared impl as-is)

Both share the same `_test/scenarios` path prefix; the suffix selects the controller (per-resource Laravel route registration).

## Submission scenario

Schema: `lib/pkp/classes/testing/scenario/schema/submission.json`.

Required: `tag` (string, ≤64 chars, parallel-isolation key — see [tag conventions in patterns.md](patterns.md#tag-conventions)), `journal` (urlPath), `submitter` (username), `section` (abbrev).

Optional top-level fields beyond the obvious ones:

- **`submitted: boolean`** — defaults true when the scenario has decisions or reviewRounds. Calls `Repo::submission()->submit()`, matching the wizard's final step.
- **`commentsForEditor: string`** — sets `commentsForTheEditors` on the submission. Combined with `submitted: true`, fires `SubmissionSubmitted` which creates the Stage 1 discussion automatically.
- **`author: {orcid, orcidIsVerified}`** — narrow passthrough that bypasses the REST orcid validator, useful for tests that need a pre-verified ORCID without the OAuth flow.

Per-decision optional fields:

- **`toAuthor: string`** — body of the `notifyAuthors` action.
- **`toReviewers: string`** — body of the `notifyReviewers` action. Soft-fails with a warning (no throw) if the decision type lacks `notifyReviewers`.
- **`toEditor: string`** — internal editor-only `submission_comments` row attached to the decision (`viewable=0`).

Every seeded submission also gets a real Article Text file via `Repo::submissionFile()->add()`. No spec field needed; tests asserting on file count get a real file out of the box.

## Context scenario

Schema: `lib/pkp/classes/testing/scenario/schema/context.json`.

Required: `tag`. Almost every other field is optional and seeds the corresponding context setting.

Notable passthroughs accumulated across feature ports:

- `copyrightNotice`, `enablePublicComments`, `submitWithCategories`, `publishingMode`
- DOI: `enableDois`, `doiPrefix`, `doiVersioning`, `enabledDoiTypes`, `registrationAgency`
- ISSNs: `onlineIssn`, `printIssn`
- `plugins: {pluginName: {enabled: true, settings: {...}}}` — generic plugin-config seeding.
- `issues: [...]` — OJS-only via `JournalScenarioController::afterContextCreated()`. Each issue accepts `accessStatus` (e.g., subscription-required), volume/number/year, etc.

## Available fixtures

Co-located at `playwright/fixtures/scenarios/`. Each is a function returning the spec payload, with sensible defaults plus an override surface. Use these instead of hand-rolling specs:

- **`submission-draft.js`** — stage 1, no decisions. Default participant cast: dbarnes editor + dbuskins + minoue section editors. For Discussion Manager tests.
- **`submission-in-review.js`** — stage 3 with reviewers. Defaults to one invited (phudson) + one accepted (jjanssen). Accepts `submitter`, `participants`, `reviewers` overrides.
- **`submission-in-round-2.js`** — multi-round; round 1 closed with `pendingRevisions` recommendation, round 2 has jjanssen invited. Decision chain: sendExternalReview → requestRevisions → newExternalRound.
- **`submission-published.js`** — VoR published with issue assignment. Defaults to bootstrap's published Vol 1, No 2, 2014. Accepts `journal` override (use it for E0 scratch journals).

Fixture functions throw if `tag` is missing — every override callsite needs one.

## Decision behaviour worth knowing

- **Decision constants are easy to misread.** `Decision::PENDING_REVISIONS = 4` (not 1). `ReviewRound::REVIEW_ROUND_STATUS_REVISIONS_REQUESTED = 1` (not 8). Always grep before quoting.
- **`requestRevisions` followed by `newExternalRound` overwrites round 1's `status`.** OJS resets it from `REVISIONS_REQUESTED` to `PENDING_REVIEWERS` as a side-effect of `parent::runAdditionalActions` in DecisionType. Tests that want "round 1 closed with revisions" must read it from decision history, not `review_rounds.status`.
- **`NewExternalReviewRound` has 2 wizard steps** (notifyAuthors + PromoteFiles), not 1.
- **`Repo::stageAssignment()->build()` uses `firstOr`.** Re-assigning the same user/role drops new flags (e.g., `canChangeMetadata`) silently. If a participant needs different flags from the auto-author assignment, route the submitter through a different user.

## Mailpit

`pkpMail` Playwright fixture wraps Mailpit's HTTP API at `:8025`. Defined at `lib/pkp/playwright/support/mail.js`.

Methods:

- **`clearAll()`** — DELETE /api/v1/messages. Call at the start of any spec that asserts on freshly-arrived mail.
- **`inboxFor(email, {timeout?, poll?})`** — polls until at least one message addressed to `email` arrives; throws on timeout.
- **`latestTo(email)`** — convenience for `inboxFor(...)[0]`.
- **`messageCount()`** — total messages, any recipient. Useful for asserting `Mail::fake()` actually suppressed every seeding email.
- **`fullMessage(id)`** — full body (HTML, Text, Headers).
- **`extractLink(html, linkText)`** — regex out the first `<a href>` whose visible text matches; for click-the-link flows.

Conventions:

- **Don't auto-clear in `beforeEach`.** Mailpit is shared across parallel tests; a global wipe will yank mail belonging to a sibling. Each test that needs a clean inbox calls `clearAll()` itself.
- **`Mail::fake()` in scenario controllers stays.** Seeding-side emails are discarded inside the scenario request. Only test-action mail (decisions submitted via UI, password resets, invitations) reaches Mailpit.

Local: `brew services start mailpit`. CI install scripted separately. Default URL `http://127.0.0.1:8025`; override via `MAILPIT_URL` env var.

## Scenario client (TODO)

`lib/pkp/playwright/support/scenarios.js` is still the SEAM stub — its `createSubmissionInReview` / `createPublishedIssue` methods throw. Today, specs POST directly to the scenario endpoints via `pkpApi` or a per-spec helper; the `scenarios` fixture will wrap that once the client is built. When you encounter a TODO, flag it rather than inventing an alternative.
