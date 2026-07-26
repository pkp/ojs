# Scenario API & Mailpit

> **Design record** — the endpoints, schemas and fixtures below were deleted in
> the 2026-07-26 reset; nothing here is currently callable. This file is the
> accumulated schema surface + behavior quirks the rebuilt scenario API grows
> back into (design invariants in `lib/pkp/docs/e2e/PRINCIPLES.md`).

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

- **`submitted: boolean`** — defaults true when the scenario has decisions or reviewRounds. Calls `Repo::submission()->submit()`, matching the wizard's final step. An EXPLICIT `submitted: false` seeds a true wizard-resumable draft at parity (no `dateSubmitted`, `submissionProgress` set, author `canChangeMetadata`) — it appears in the author's Incomplete list and the wizard reopens on it. Omitting the key keeps the legacy Discussion-Manager shape.
- **`commentsForEditor: string`** — sets `commentsForTheEditors` on the submission. Combined with `submitted: true`, fires `SubmissionSubmitted` which creates the Stage 1 discussion automatically.
- **`author: {orcid, orcidIsVerified}`** — narrow passthrough that bypasses the REST orcid validator, useful for tests that need a pre-verified ORCID without the OAuth flow.
- **`reviewerSuggestions: [{givenName, familyName, email, affiliation?, suggestionReason?}]`** — seeds the wizard's reviewer suggestions as if the author entered them (strings, not locale maps; wrapped under the spec `locale`).
- **`userComments: [{user, text, approved?}]`** — public reader comments on the current publication (`approved` defaults true; requires the publication to be published). Mirrors the UserComment REST create + moderation-approve path.
- **`metrics: {views?, downloads?, months?}`** — OJS-only; writes compiled `metrics_submission` rows spread backwards from the current month so Stats pages render non-zero data. Synthetic-but-shaped-correctly; the log→compile pipeline stays untested.

Per-publication (inside `publications[]`):

- **`galleys: [{label, locale?, file?, urlRemote?}]`** — seeds galleys at galley-grid parity (`file` is a basename under `lib/pkp/playwright/fixtures/files/`, defaults to the standard Article Text PDF; `urlRemote` makes a remote galley; the two are mutually exclusive). Response echoes `galleys: [{id, label, ...}]`.
- **`metadata.datePublished`** — survives `published: true` (mirrors the editor's ability to set the publication date); without it, publish stamps today.
- **`mediaFiles: [{variantType, file?, name?, genre?, group?}]`** — seeds Media-tab files (`SUBMISSION_FILE_MEDIA`, assoc'd to the publication) at `MediaFilesController::add()` parity. `variantType` is required (`'web' | 'high_resolution'`); `file` is a basename under `lib/pkp/playwright/fixtures/files/` (default `dependent-image.png`); `genre` defaults to `IMAGE` (the variant-supporting genre). Entries sharing a `group` label are linked pairwise via `VariantGroup::link()` with the FIRST entry as primary (max 2 per group). Response echoes `mediaFiles: [{id, name, variantType, group, variantGroupId}]`.

Per-reviewer (inside `reviewRounds[].reviewers[]`):

- **`reviewForm: "<title>"`** — attaches an existing *active* review form by exact title match, as the Add Reviewer form does. Seed the form first via the context scenario's `reviewForms[]`.

Per-decision optional fields:

- **`toAuthor: string`** — body of the `notifyAuthors` action.
- **`toReviewers: string`** — body of the `notifyReviewers` action. Soft-fails with a warning (no throw) if the decision type lacks `notifyReviewers`.
- **`toEditor: string`** — internal editor-only `submission_comments` row attached to the decision (`viewable=0`).

Every seeded submission also gets a real Article Text file via `Repo::submissionFile()->add()`. No spec field needed; tests asserting on file count get a real file out of the box.

## Context scenario

Schema: `lib/pkp/classes/testing/scenario/schema/context.json`.

Required: `tag`. Almost every other field is optional and seeds the corresponding context setting.

Notable passthroughs accumulated across feature ports:

- `copyrightNotice`, `enablePublicComments`, `submitWithCategories`, `publishingMode`, `enableAnnouncements`
- DOI: `enableDois`, `doiPrefix`, `doiVersioning`, `enabledDoiTypes`, `registrationAgency`, `doiCreationTime` (`'publicationCreationTime'` = auto-assign on publish)
- Submission metadata modes: `keywords`, `citations` (`0` | `'enable'` | `'request'` | `'require'`)
- Review setup: `defaultReviewMode` (1 anonymous / 2 double-anonymous / 3 open), `reviewerSuggestionEnabled`, `numWeeksPerResponse`, `numWeeksPerReview`, `numDaysBefore/AfterReviewResponseReminderDue`, `numDaysBefore/AfterReviewSubmitReminderDue`
- ISSNs: `onlineIssn`, `printIssn`
- `plugins: {pluginName: {enabled: true, settings: {...}}}` — generic plugin-config seeding. Keys are `LazyLoadPlugin::getName()`, i.e. the lowercased class name (`citationstylelanguageplugin`, not `citationStyleLanguage`).
- `reviewForms: [{title, description?, elements: [{type, question, required?, options?}]}]` — seeds active review forms at grid parity. Response echoes `reviewForms: [{id, title, elementIds}]`.
- `issues: [...]` — OJS-only via `JournalScenarioController::afterContextCreated()`. Each issue accepts `accessStatus` (e.g., subscription-required), volume/number/year, etc.
- `subscriptions: [...]` — OJS-only. `{type: {name, duration?, cost?, institutional?}, user?, institution?: {name, ipRanges}, status?: 'active'|'expired', dateStart?, dateEnd?}`. `'expired'` seeds an ACTIVE-status row with a past `date_end` — the lapsed state unreachable through the create form.

**Schema validation is enforced** (Opis, dev dependency): a spec with unknown keys gets a 400 naming the offending key. App-specific keys (`issues`, `subscriptions`, `metrics`) are declared via `schemaOverlayProperties()` overrides in the OJS controllers.

The baseline `publicknowledge` journal is seeded with enriched defaults (see `playwright/fixtures/bootstrap.js`): announcements, public comments, categories-in-wizard, keywords/citations on request, reviewer suggestions, DOIs auto-assigned on publish, CSL plugin, double-anonymous review with deadlines + reminder thresholds. Tests needing any of these OFF use a scratch journal.

## Available fixtures

Co-located at `playwright/fixtures/scenarios/`. Each is a function returning the spec payload, with sensible defaults plus an override surface. Use these instead of hand-rolling specs:

- **`submission-draft.js`** — stage 1, no decisions. Default participant cast: editor.diana editor + sectioneditor.ana + sectioneditor.ravi section editors. For Discussion Manager tests.
- **`submission-in-review.js`** — stage 3 with reviewers. Defaults to one invited (reviewer.paul) + one accepted (reviewer.julia). Accepts `submitter`, `participants`, `reviewers` overrides.
- **`submission-in-round-2.js`** — multi-round; round 1 closed with `pendingRevisions` recommendation, round 2 has reviewer.julia invited. Decision chain: sendExternalReview → requestRevisions → newExternalRound.
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

- **`find({to, contains, subject?, timeoutMs?, poll?})`** — THE canonical assertion: polls Mailpit search scoped by recipient + a unique content marker; throws on unscoped use. Use this, not inbox-wide reads.
- **`expectNone({to, contains, afterControl: {to, contains}})`** — negative assertion done right: waits for the control message to arrive (bounding the wait), then asserts zero matches for the target.
- **`inboxFor(email, {timeout?, poll?})`** — polls until at least one message addressed to `email` arrives; throws on timeout.
- **`latestTo(email)`** — convenience for `inboxFor(...)[0]`.
- **`messageCount()`** — total messages, any recipient. Useful for asserting `Mail::fake()` actually suppressed every seeding email.
- **`fullMessage(id)`** — full body (HTML, Text, Headers).
- **`extractLink(html, linkText)`** — regex out the first `<a href>` whose visible text matches; for click-the-link flows.
- **`clearAll()`** — DELETE /api/v1/messages. **Permitted ONLY in the serial test-infrastructure spec** (charter principle 8); everywhere else scope with `find`/`expectNone` and throwaway recipient users.

Conventions:

- **Never wipe the shared inbox from a parallel spec.** Mailpit is shared across parallel workers; scope every assertion by recipient + the test's unique tag, and use throwaway recipients when counting or asserting absence (charter principles 7–8).
- **`Mail::fake()` in scenario controllers stays.** Seeding-side emails are discarded inside the scenario request. Only test-action mail (decisions submitted via UI, password resets, invitations) reaches Mailpit.

Local: `brew services start mailpit`. CI install scripted separately. Default URL `http://127.0.0.1:8025`; override via `MAILPIT_URL` env var.

## Scenario client (TODO)

`lib/pkp/playwright/support/scenarios.js` is still the SEAM stub — its `createSubmissionInReview` / `createPublishedIssue` methods throw. Today, specs POST directly to the scenario endpoints via `pkpApi` or a per-spec helper; the `scenarios` fixture will wrap that once the client is built. When you encounter a TODO, flag it rather than inventing an alternative.
