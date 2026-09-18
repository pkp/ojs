# Scenario API & Mailpit

> **Partially live** (rebuilt 2026-07-27): the endpoints exist again with the
> deliberately minimal **step-2 core schema** — see "What is LIVE today" below.
> Everything else in this file is the recorded pre-reset surface the API grows
> back into per-feature (PRINCIPLES §3: extend only when multiple tests need
> the same state; every builder change needs a parity entry in
> `lib/pkp/docs/e2e/scenario-processor-audit.md`). The behavior quirks noted
> throughout are live app truth regardless.

## What is LIVE today (step-2 core)

Routes (site-wide: `/index.php/index/api/v1/_test/…`), gated by `X-Test-Key`
(env `TEST_API_KEY`); schemas in `lib/pkp/classes/testing/scenario/schema/`;
unknown keys → 400 with a dotted `specKey`. Authoritative field list: stage-A
report §3 (`docs/product/.reports/step2-harness/A-php-api.md`).

- `POST/GET bootstrap` — declarative base seed (context + sections/series +
  categories + issues (OJS) + users w/ roles and `sections`/`series`
  sub-editor assignments); warm calls no-op.
- `POST scenarios/context` — scratch context: `tag*`, context object,
  `users[]` throwaways, `invitations[]` (U6, all apps — see below), declared
  setting passthroughs.
- `POST scenarios/submission` — `tag*`, `context*`, `submitter*`, `title`,
  `abstract`, `locale`, `submitted` (false = wizard-resumable draft),
  `decisions[]` (real decision names, app-resolved), `reviewRounds[].reviewers[]`
  (invited/accepted/declined), `published`. Overlays: OJS `section`/`issue`;
  OMP `series`/`seriesPosition` + per-round `stage: internal|external`;
  OPS `section` (and `reviewRounds` is REJECTED — no review stage).

Per-app controllers: `JournalScenarioController` (OJS),
`PressScenarioController` (OMP), `ServerScenarioController` (OPS), extending
the shared `PKP*ScenarioController` base. NOT yet live: everything below this
section that isn't in the list above (galleys, metrics, subscriptions, media
files, review forms, reviewer suggestions, user comments, `commentsForEditor`,
per-decision `toAuthor`/`toReviewers`/`toEditor`, ORCID passthrough,
`reviewForm` per reviewer, issue `accessStatus` semantics).

The scenario endpoints assemble realistic submission/journal state in one POST. They live behind `TestModeGate` (key + `APPLICATION_ENV=test`) and are the canonical alternative to driving the UI for setup. Mailpit is the test-side SMTP catcher that the suite asserts against for emails sent during *test actions* (decisions, password resets, invitations) — scenario-side mail is dropped on the floor by `Mail::fake()`.

## Endpoints (recorded surface — route names updated to the rebuild)

Dispatched from `api/v1/_test/index.php` (which 404s before registering
anything when `TEST_API_KEY` is absent from the server's environment):

- `POST /api/v1/_test/scenarios/context` — the app's context scenario controller (extends `PKPContextScenarioController`); named `context`, not `journal`, per the app-neutral schema rule
- `POST /api/v1/_test/scenarios/submission` — the app's submission scenario controller (extends `PKPSubmissionScenarioController`)
- `POST/GET /api/v1/_test/bootstrap` — base seed (a trait mixed into each app's bootstrap controller so it inherits the app's context overlay)

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
- `invitations: [...]` — **LIVE (added for U6, 2026-07-28)**, all three apps. User-role invitations already sent in this context. See the section below.
- `users[].roles` accepts **`siteAdmin`** — **LIVE (added for U53, 2026-07-29)**, all three apps, and on `bootstrap` too. See the section below.

## Context scenario · `users[].roles: ['siteAdmin']`

```js
users: [
    {username: 'u53top.admin.ojs', roles: ['siteAdmin']},            // administrator only
    {username: 'u53top.both.ojs',  roles: ['siteAdmin', 'manager']}, // and a context role
]
```

A throwaway **site administrator**. Not a new spec key — a role key, obeying every
user-spec convention: password is the username doubled, the response echoes it in
the usual `users: {username: id}` map, a later failure rolls the account back.
Available on `POST scenarios/context` and on `POST bootstrap`.

**Why it has to exist:** no screen in any of the three apps grants this role.
`UserForm::saveUserGroupAssignments()` intersects the posted group ids with
`UserGroup::withContextIds([$contextId])` ("secure that user-specified user group
IDs are from the right context") and the site administrator group belongs to no
context, so Users & Roles cannot offer it. Without this key the installer's
`admin` is the only administrator on the install — and it is read-only for the
suite, because disabling or merging it would break every later feature. Anything
about administrator behaviour (self-disable, merge, one admin acting on another)
seeds its own.

Implementation notes worth knowing:

- The group is site-wide (`contextId` null) and has **no `nameLocaleKey`** —
  `PKPInstall::createData()` writes the translated names directly — so the
  context-scoped name-key lookup every other role uses can never find it.
  `PKPTestApiController::resolveRoleGroup()` special-cases this one key and
  resolves by `Role::ROLE_ID_SITE_ADMIN`.
- Enrolment is `Repo::userGroup()->assignUserToGroup()`: the installer's own call,
  and the one the seeder already makes for every other role. Nothing is written by
  hand. Parity entry: `lib/pkp/docs/e2e/scenario-processor-audit.md`, 2026-07-29.
- **`invitations[].roles` rejects `siteAdmin`** deliberately (400, "No default user
  group for role 'siteAdmin'"): no screen invites anyone to it.
- The seeded administrator needs no context membership to work. Verified live on
  all three fleets by signing in through the sign-in screen and reaching
  Administration → Hosted Journals / Presses / Servers.

## Context scenario · `invitations`

```js
invitations: [
    {email: 'newcomer@example.org', roles: ['sectionEditor'],      // a person with no account
     givenName: 'Nadia', familyName: 'Newcomer', country: 'CA'},
    {user: 'u6h.existing', roles: ['externalReviewer']},           // an existing account
    {email: 'lapsed@example.org', roles: ['reader'], status: 'expired'},
]
```

Exactly one of **`email`** (a newcomer) or **`user`** (an existing username) names
the recipient — sending both, or neither, throws. `roles` is required and uses the
app's scenario role keys ([per-app list](users.md#scenario-role-keys-per-app)).
Optional: `givenName` / `familyName` / `affiliation` / `country` (newcomer only —
for an existing account the app prohibits them, and they are skipped), `masthead`
(default `true`), `inviter` (username; defaults to the site admin), and
`status: 'pending' | 'expired'` (default `'pending'`).

The builder walks the Invite-a-user wizard's own three API calls
(add → populate → invite) through the invitation type's controller, so the seeded
invitation is the real thing: key, expiry date, PENDING status, `email_log` row.
A step the app refuses throws a 400 carrying the app's own validation errors.

The response echoes one entry per invitation:

```json
{"id": 312, "status": "PENDING", "email": "…", "userId": null,
 "roles": ["sectionEditor"], "invitedAt": "…", "expiryDate": "…",
 "key": "KtqrSg",
 "acceptUrl": "…/u6harness-ojs-a/invitation/accept?id=312&key=KtqrSg",
 "declineUrl": "…/u6harness-ojs-a/invitation/decline?id=312&key=KtqrSg"}
```

`key` is the one-time invitation key in plaintext — it exists only for the length
of the seeding request and is a bcrypt hash afterwards, so this response is the
only place a test can get it without opening the email. Use the URLs to drive the
recipient's journey; keep Mailpit for the scenarios where the delivered **email**
is the thing under test (seeding mail is dropped by `Mail::fake()` as always).

Behaviour worth knowing, all verified live on 8000/8100/8200:

- **Expiry is a date, not a status.** An `'expired'` invitation still echoes
  `status: "PENDING"`; what makes it expired is `expiryDate` in the past. It is
  placed exactly one day past whatever `[invitations] expiration_days` the fleet
  is configured for, with `invitedAt` slid back to match.
- **The manager's Invitations table only lists still-active invitations** — an
  expired one is absent from `Users & Roles → Invitations` (and from its count),
  because `GET /invitations/{type}` filters on `stillActive()`. Assert its absence
  there, and its state through the link.
- **Both recipient links land somewhere assertable.** Pending + newcomer → the
  3-step "Create <APP> account" wizard with the seeded name/country pre-filled;
  pending + existing account → the 1-step "Review & create account" page listing
  the roles; expired → the "Invitation Unavailable … accepted, declined, or
  expired" page (accept AND decline both). Decline on a pending invitation shows
  the POST-confirm page, not an immediate decline.
- Role labels are per app: `sectionEditor` reads "Section editor" (OJS), "Series
  editor" (OMP), "Moderator" (OPS).
- **You cannot invite an existing user to a role they already hold** — the app's
  `AddUserGroupRule` refuses it ("The user group is assigned to the invited
  user") and the seed throws at the `populate` step. Give the throwaway invitee a
  different role from the one being invited to.

**Schema validation is enforced** (Opis, dev dependency): a spec with unknown keys gets a 400 naming the offending key. App-specific keys (`issues`, `subscriptions`, `metrics`) are declared via `schemaOverlayProperties()` overrides in the OJS controllers.

The baseline `publicknowledge` journal is seeded with enriched defaults (see `playwright/fixtures/bootstrap.js`): announcements, public comments, categories-in-wizard, keywords/citations on request, reviewer suggestions, DOIs auto-assigned on publish, CSL plugin, double-anonymous review with deadlines + reminder thresholds. Tests needing any of these OFF use a scratch journal.

## Available fixtures (recorded design — NOT yet recreated)

Spec-builders will return to `playwright/fixtures/scenarios/` when feature
specs need them; until then, specs POST via `pkpApi.createContext()` /
`createSubmission()`. The recorded builders and their defaults, as the shapes
to grow back into:

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

> **There are no Mailpit tags on this install** (verified 2026-07-29:
> `GET /api/v1/tags` → `[]`, every message carries `Tags: []`; nothing in the apps
> sets `X-Tags`). Wherever older wording says "scope by recipient **and** the
> per-app tag", the executable instruction is: **scope by a unique throwaway
> recipient address** — put the app and the probe/test in the address itself
> (`u53top-omp@mail.test`) — and add a positive control whenever the claim is
> silence. `find()`'s `contains` is a *content marker* (a substring searched in
> subject/body), not a Mailpit tag: useful when the test controls some text in the
> message, never a substitute for the recipient scope.

- **`find({to, contains, subject?, timeoutMs?, poll?})`** — THE canonical assertion: polls Mailpit search scoped by recipient + a unique content marker; throws on unscoped use. Use this, not inbox-wide reads.
- **`expectNone({to, contains, afterControl: {to, contains}})`** — negative assertion done right: waits for the control message to arrive (bounding the wait), then asserts zero matches for the target.
- **`inboxFor(email, {timeout?, poll?})`** — polls until at least one message addressed to `email` arrives; throws on timeout.
- **`latestTo(email)`** — convenience for `inboxFor(...)[0]`.
- **`messageCount()`** — total messages, any recipient. Useful for asserting `Mail::fake()` actually suppressed every seeding email.
- **`fullMessage(id)`** — full body (HTML, Text, Headers).
- **`extractLink(html, linkText)`** — regex out the first `<a href>` whose visible text matches; for click-the-link flows.
- **`clearAll()`** — DELETE /api/v1/messages. **Permitted ONLY in the serial test-infrastructure spec** (charter principle 8); everywhere else scope with `find`/`expectNone` and throwaway recipient users.

Conventions:

- **Never wipe the shared inbox from a parallel spec.** Mailpit is shared across parallel workers *and* across the three fleets; scope every assertion by a unique throwaway recipient address (see the box above — there are no tags), optionally narrowed further by a content marker, and pair every absence claim with a positive control (charter principles 7–8).
- **`Mail::fake()` in scenario controllers stays.** Seeding-side emails are discarded inside the scenario request. Only test-action mail (decisions submitted via UI, password resets, invitations) reaches Mailpit.

Local: `brew services start mailpit`. CI install scripted separately. Default URL `http://127.0.0.1:8025`; override via `MAILPIT_URL` env var.

## Scenario client

There is deliberately no typed scenario client (the pre-reset `scenarios.js`
stub was not recreated): specs POST via `pkpApi.createContext()` /
`createSubmission()`, which validate server-side. Build a client only when a
feature suite demonstrates the need.
