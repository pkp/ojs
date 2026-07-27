# Playwright Patterns for OJS

> Patterns and pitfalls — durable content; app behavior described here (grids,
> modals, redirects, endpoints) is live app truth. The base harness was rebuilt
> 2026-07-27; feature-level POMs/helpers named below (e.g.
> `EditorialWorkflowPage#awaitEmailTemplateLoaded`) do NOT exist yet — they
> return with the feature suites, and the named pattern is the design to follow.

Conventions already established in the existing specs, plus the rationale so you can judge edge cases.

For scenario-endpoint and Mailpit details, see `scenarios.md`.

## Locator priority

Pick the first one that works:

1. **`getByRole`** — preferred. Resilient to CSS changes, reflects accessibility, matches user intent.
   ```js
   page.getByRole('button', {name: 'Submit'})
   page.getByRole('heading', {name: 'Dashboard'})
   page.getByRole('link', {name: /New Submission/i})
   ```
2. **`getByLabel`** — for form fields with a visible label.
   ```js
   page.getByLabel('Title')
   ```
3. **Stable IDs** — when a form is built with `id`-anchored labels (the login page is the canonical example).
   ```js
   page.locator('input#username')
   page.locator('form#login button')
   ```
4. **`data-cy` hooks** — legacy Cypress markers that still ship in the DOM (e.g. `data-cy="workflow-controls-left"`). Acceptable when role/label are ambiguous on a complex Vue view.
   ```js
   page.locator('[data-cy="workflow-controls-right"]').getByRole('button', {name: 'Accept'})
   ```
5. **CSS** — last resort. Wrap with a role or data attribute when possible.

**Anti-patterns:**
- `nth-child`, `:first-child`, `:last-child` — brittle, breaks on list reorders.
- Long class-name chains (`.pkp_list__item .pkp_nav--primary a.link`) — refactor into a locator with role + name.
- `page.waitForTimeout(n)` — Playwright has auto-wait; fixed timeouts hide real races.
- `page.waitForLoadState('networkidle')` on Vue pages — Vue pages often have long-lived WebSocket/polling connections that never "idle". Wait on a visible landmark instead.

## Locator pitfalls

OJS-specific gotchas. Each of these has bitten the migration at least once.

1. **OJS tabs are `role="tab"`, not `role="button"`.** Cypress's `cy.contains('button', 'X')` matched anything labeled X; Playwright's `getByRole('button', {name: ...})` is strict. Stable hook for top-level tabs: `#{name}-button` (`#review-button`, `#setup-button`, etc.).
2. **Nested tab groups.** Top-level *Setup* vs Appearance → Setup are different tabs with different scopes. Use the outer `#setup-button` for the outer one, descend into the inner via the actual visible-tab role lookup.
3. **Headlessui menus (More Actions).** Items are `role="menuitem"`, not `button`. The menu portals to the document root, so scope to `page` not the row.
4. **Side modals scoped via `[data-cy="active-modal"]`.** When stacked (e.g., a decision modal opens a confirmation), filter by a distinctive inner element rather than `.first()` / `.last()`.
5. **The side-modal outer wrapper reports `visibility: hidden` during the open transition.** Anchor `toBeVisible()` on a distinctive inner heading or text instead of the modal root.
6. **The workflow page itself is a reka-ui dialog.** When it opens another modal on top, both are `[role="dialog"]`. Disambiguate with accessible-name scoping: `getByRole('dialog', {name: /Add Reviewer/i})`.
7. **Confirmation dialogs.** Use `[role="dialog"]:has-text("...")` or the legacy `[data-cy="dialog"]` hook. Buttons inside are sometimes labeled OK/Yes/No (varies by reka-ui vs jQuery UI).
8. **fbvElement ids are runtime-suffixed with `$FBV_uniqId`** (e.g. `#volume-12-something-hash`). Use `name=` selectors, not `#`.
9. **Legacy pkp jQuery grids:** row controls hide until `a.show_extras` is clicked; the row class flips to `hide_extras` once expanded. Row selector: `tr.gridRow#component-grid-...`.
10. **PkpButton with icon + SR-only text.** The accessible name often includes row context — the Edit button in a list of mailables is named `Edit Discussion (Production)`, not `Edit`. Use a regex scoped to the row: `getByRole('button', {name: /Edit/i})`.
11. **AJAX-loaded email templates** (decision Composer steps): wait for `.composer__loadingTemplateMask` to be gone before submitting. Otherwise the POST validates on empty body and fails server-side. The `EditorialWorkflowPage#awaitEmailTemplateLoaded` POM helper does this.
12. **File uploads on fbv plupload widgets.** The native `<input type=file>` is `opacity: 0` and overlaid by a styled button. `setInputFiles()` directly on the input works; clicking the visible button opens a real OS file dialog.
13. **`[role="status"]:has-text("Saved")`** — the canonical form-save confirmation. Wait on it after Save clicks before reloading or asserting persistence.
14. **`Repo::stageAssignment()->build()` uses `firstOr`.** Re-assigning the same user/role drops new flags (e.g., `canChangeMetadata`) silently. If a participant needs different flags from the auto-author assignment, route the submitter through a different user.

## Fixture selection

### Single default user → `test.use({user: 'X'})`
```js
test.use({user: 'editor.diana'});

test('editor does thing', async ({page}) => {
    // page is pre-authenticated as editor.diana
});
```
Works at `test.describe` level or file level. Storage state is cached on disk across runs — only pay login cost once per user per DB lifetime.

### Multi-actor flow → `asUser`
```js
test('editor assigns, reviewer accepts', async ({page, asUser}) => {
    // page is the default user (from test.use)
    const reviewerCtx = await asUser('reviewer.julia');
    const reviewerPage = await reviewerCtx.newPage();
    // Both pages are live in parallel
});
```
Contexts auto-close on teardown. Do not manually `ctx.close()` unless the test explicitly needs to verify a closed-session scenario.

### Anonymous flow → omit `user`
For reader-facing tests (journal homepage, public article view). Don't set `test.use({user: ...})` and you get a fresh anonymous context per test.

## Waiting strategy

Playwright auto-waits for actionability on every interaction. Explicit waits are only needed for **arrival** (confirming a navigation or DOM transition completed).

### Wait on a landmark, not the network
Good:
```js
await page.goto('/dashboard');
await expect(page.getByRole('heading', {name: 'Dashboard'})).toBeVisible();
```

Bad:
```js
await page.goto('/dashboard');
await page.waitForLoadState('networkidle'); // Vue keeps polling — may never resolve
```

### Wait on the URL for auth-style redirects
From `auth.js`:
```js
await page.waitForURL((url) => !url.pathname.includes('/login'), {
    timeout: 15_000,
    waitUntil: 'commit',
});
```
Default `waitUntil:'load'` is fragile under parallel load — Vue dashboards fan out a lot of XHRs. `'commit'` fires when the navigation commits without waiting for them.

### Wait on a web response for API-triggered updates
```js
const response = page.waitForResponse(r => r.url().includes('/api/v1/submissions') && r.status() === 200);
await page.getByRole('button', {name: 'Save'}).click();
await response;
```

Prefer this over toast-based assertions when running in parallel — see "Parallel-load lessons" below.

### Wait on jQuery to settle for legacy AjaxModal / grid flows
Helper: `lib/pkp/playwright/support/jquery.js` → `waitForJQueryIdle(page)`.

```js
const {waitForJQueryIdle} = require('../../lib/pkp/playwright/support/jquery.js');

await form.locator('button[type="submit"]').click();
await waitForJQueryIdle(page);
await expect(form).toHaveCount(0); // modal closed by AjaxFormHandler
```

`window.jQuery.active` is jQuery's in-flight AJAX counter; `waitForJQueryIdle` polls until it reaches 0. This is the Playwright counterpart to Cypress's `cy.waitJQuery()` — call it after any interaction with legacy jQuery-driven UI:

- AjaxModal open + AjaxFormHandler save chains (subscription types/policies, sections, the broader Distribution/Settings pages still on the legacy stack)
- Smarty grid refreshes (`pkp_controllers_linkAction` row deletes / inline edits)
- Tab-handler clicks (`a[name="..."]` on PaymentsHandler-style multi-tab pages)

When NOT to use it: modern Vue surfaces don't use jQuery's AJAX, so calling it on a Vue-only flow is a no-op (jQuery is absent or stays at 0). Safe to call defensively, but prefer `waitForResponse` on Vue surfaces — it gives stronger guarantees about which call settled.

Symptom that points to this fix: a spec passes solo or at `--workers=1` but flakes at `--workers=2` with timeouts on the next assertion after a legacy form save. The save's success callback (close modal, refresh grid) is racing the assertion.

## Parallel-load lessons

The suite runs in parallel by default. The shared seed data is the unit of contention.

1. **`page.waitForURL(...)` with default `waitUntil:'load'` is fragile under parallel load.** Use `'commit'`.
2. **`/notification/fetchNotification` drains all pending notifications for a user.** Two parallel tests as the same user share + race for the toast queue. Don't assert on toasts in parallel-running specs as the same user; assert on the actual save endpoint via `waitForResponse`.
3. **`searchPhrase=` OR-joins on whitespace.** `searchPhrase: 'Published article {tag}'` matches every fixture-seeded "Published article" — falls off the `count=30` cap under load. Search by `tag` alone (single whitespace-free unique token).
4. **Mailpit inbox is shared across parallel tests in a run.** Never `clearAll()` outside the dedicated serial infrastructure spec (charter principle 8); scope every assertion with `pkpMail.find({to, contains: tag})` / `expectNone`, using throwaway recipients for counting/absence checks.
5. **`playwright/.auth/{user}.json` can go stale after `login-as` flows.** `PKPSessionGuard::signInAs/signOutAs` migrate the session and destroy the previous row. `ensureAuthStateFor` probes `/index/user/profile` before reusing storage state — it relogs in if the probe doesn't 200.
6. **All server-side outbound HTTP is firewalled in test runs.** `config.test.inc.php` points `[proxy]` at a dead local port, which PKP wires into Guzzle, PHP streams AND libxml — so a test must never depend on the app reaching an external service (a hung egress call once killed worker PHP servers mid-suite). Remote DTDs the app validates against are mirrored at `lib/pkp/playwright/fixtures/dtd/` and resolved via `XML_CATALOG_FILES`.
7. **Scheduled tasks never run on their own** (`[schedule] task_runner = Off` in the test config). A spec that needs a scheduled task (reminders etc.) belongs in the serial project and invokes `php lib/pkp/tools/scheduler.php run` explicitly. Queued jobs still process at end of request (`[queues] job_runner = On`) — and that end-of-request runner makes the serial isolation two-way (wave-12 verified): a scenario-seeding request anywhere on the shared DB can pop your queued mail job inside its `Mail::fake()` context, committing side effects while silently swallowing the message. Never run serial scheduled-task specs while parallel agents are seeding; the canonical project chain (serial depends on the parallel projects) guarantees this in normal runs.
8. **"Anonymous" contexts aren't anonymous under `test.use({user})`.** `browser.newContext()` inherits the file's `storageState` context option, so a fresh context carries the logged-in session — and editors can *preview* unpublished articles, turning expected 404s into 200s. Every anonymous-reader check must pass an explicit empty state: `browser.newContext({storageState: {cookies: [], origins: []}})`. (Bit two wave-6 agents independently.)
9. **Bare front-end URLs 302 to the locale-prefixed form** (`/index.php/<journal>/article/...` → `/index.php/<journal>/en/article/...`). `page.goto` hides this by following redirects, but any `request.get(..., {maxRedirects: 0})` probe must use the locale-prefixed URL. **INVERTED on single-locale journals** (most scratch journals): there the bare URL serves directly and the `/en/`-prefixed form 302s BACK to the bare one — probe scratch journals with bare URLs, publicknowledge with prefixed ones.
10. **Tags that back COUNT assertions need a per-run random component.** A tag built only from workerIndex + test-title slug repeats across runs, so counting tag matches on a shared surface (issue TOC, archive listing) picks up leftovers from previous runs on a long-lived DB. Existence assertions tolerate this; `toHaveCount(n)` does not. **And tags that back SEARCH assertions must be single hyphenless alphanumeric tokens**: Postgres splits `edd7-w0-x` into `edd7`/`w0`/`x` and the dashboard search OR-matches tokens — a `-w0-` tag search matches every other worker-0 submission still active (deterministic on long-lived DBs with accumulated incomplete drafts; reset the DB before full-suite runs).
11. **Component-router URLs are kebab-cased** (`saveSequence` → `…/save-sequence`, `deleteContext` → `…/delete-context`) — `waitForResponse` predicates written against the camelCase op name never match. Bit three specs across waves 7–9.
12. **Mailpit reads must go through `pkpMail.find({to, contains})`** — `inboxFor`/`latestTo` are now search-scoped (Mailpit's `/api/v1/messages?query=` ignores the query; only `/api/v1/search` filters), but `find` with a unique tag remains the only parallel-safe shape; an inbox-wide `latestTo` can still race two mails to the SAME recipient.
13. **Scenario scratch journals auto-enroll `admin` as Journal manager** (`PKPContextService::add()` parity) — every Users-list/participant count on a scratch journal is +1 over the seeded `users[]`.
14. **Legacy forms that embed a sub-grid contain that grid's own `<form>` and submit buttons** — e.g. the individual/institutional subscription forms embed SubscriberSelect, whose nested `form#userSearchForm` puts a "Search" submit FIRST in DOM order. `form.locator('button[type=submit]').first()` clicks Search, refreshing the grid and silently clearing your picked radio. Click Save by accessible name.

## Tag conventions

Tests that seed via the scenario API need a unique tag for parallel isolation. Two constraints:

- **`journals.urlPath` is varchar(32).** Long tags trigger 500s with truncation errors.
- **Single hyphenless alphanumeric token.** `searchPhrase=` OR-splits on whitespace AND Postgres tokenizes on hyphens (`edd7-w0-x` → `edd7`/`w0`/`x`, so a `-w0-` tag matches every other worker-0 submission — parallel lesson 10). Pattern: `{prefix}w{parallelIndex}{suffix}`, e.g. `subw0k3f9qa`; give the suffix a per-run random component whenever the tag backs a COUNT assertion.

## Tags

Filter on the CLI with `--grep @tagname`. The tag set (recreate with the harness):

- `@smoke` — minimal must-pass coverage; runs on every PR
- `@regression` — broader coverage; scheduled/nightly
- `@slow` — opt-out for fast local loops
- `@flaky` — quarantined; excluded from default runs until fixed

Apply with:
```js
test('name', {tag: '@smoke'}, async ({page}) => { ... });
test('name', {tag: ['@smoke', '@regression']}, async ({page}) => { ... });
```

## Page Object Model

Inherit from `BasePage` (`lib/pkp/playwright/pages/BasePage.js:11`). POMs hold the Playwright `page` reference and locators as instance properties.

```js
// lib/pkp/playwright/pages/SomePage.js (shared) or playwright/pages/SomePage.js (OJS-only)
const {BasePage} = require('./BasePage.js');

exports.SomePage = class SomePage extends BasePage {
    constructor(page) {
        super(page);
        this.heading = page.getByRole('heading', {name: 'Some Page'});
        this.saveButton = page.getByRole('button', {name: 'Save'});
    }

    async goto() {
        await this.page.goto('/some-path');
    }

    async save() {
        await this.saveButton.click();
    }
};
```

### Where to put a new POM

- Shared across OJS/OMP/OPS (login, dashboard, workflow mechanics) → `lib/pkp/playwright/pages/`
- OJS-only (issues, galleys, OJS submission wizard, editorial workflow) → `playwright/pages/`

OJS-side POMs of note:

- `playwright/pages/EditorialWorkflowPage.js` — drives the per-submission workflow page including primary decisions, the Publication side-nav, publish/unpublish flows, and galley add/delete. Significant helpers: `clickDecision`, `clickRequestRevisions` (handles the WorkflowSelectRevisionFormModal entry), `awaitEmailTemplateLoaded`, `recordDecision`, `publishCurrentPanel`, `addGalley`, `deleteGalley`.
- `playwright/pages/SubmissionWizardPage.js`, `playwright/pages/IssuePage.js` — narrower POMs, see source.

## Decision flow

Decision button labels matter — they don't always match what the legacy Cypress source called them.

| Decision | Button label |
|---|---|
| sendExternalReview | `Send for Review` |
| acceptFromReview | `Accept Submission` (review-stage accept) |
| acceptInitial | `Accept and Skip Review` (stage 1 accept-without-review) |
| sendToProduction | `Send To Production` |
| requestRevisions | `Request Revisions` |
| decline | `Decline` |

`Request Revisions` is the only primary decision whose entry button does NOT navigate directly to `decision/record/{id}`. It first opens a side modal (`WorkflowSelectRevisionFormModal`) with a radio choice between PENDING_REVISIONS (no new round; default) and RESUBMIT (new round). Only after picking and clicking Next does the page navigate. Use `EditorialWorkflowPage#clickRequestRevisions({newRound})` rather than rolling it inline.

For decision-constant gotchas, scenario fixtures and round-status quirks, see `scenarios.md`.

## Data seeding

Prefer the API over UI for setup. Drive the UI only for what the test is actually exercising.

### Bootstrap (runs once per DB lifetime)
Handled for you by `bootstrap.setup.js`. Seeds the `publicknowledge` journal, all 17 non-admin users, sections, categories, issues.

### Scenario API (preferred)
For composite state (a submission in review, a published article with an issue assignment), POST to `/api/v1/_test/scenarios/submission` or `/scenarios/journal`. Use the fixture functions at `playwright/fixtures/scenarios/` rather than hand-rolling specs. See `scenarios.md` for the full surface.

### Per-test data via REST
For one-off mutations, use `ojsApi` (OJS specs) or `pkpApi` (shared specs):
```js
test('...', async ({ojsApi, page}) => {
    const submission = await ojsApi.createSubmission({section: 'ART', title: 'Test article'});
    // ... drive UI with submission.id ...
});
```

The `submission` fixture at `playwright/support/fixtures.js` is a wired-but-stub for end-of-test cleanup; its methods are TODO. When you encounter a TODO, flag it rather than inventing an alternative.

## Verify before trusting

When a plan, instruction, or older spec names a `Repo::*` method, schema field, or `Class::CONST`, **read the live source** and confirm before using. Setting keys, method signatures, and constant values are the most common mismatches. Six identifier mismatches got caught during the migration purely by comparing plan text to current code. The fastest checks:

- `Repo::*` — open the relevant Repository class and confirm the method exists with the named signature.
- `*::FOO_*` constants — grep the constant; PHP `const` lines beat any second-hand reference.
- Vue component selector hooks (`data-cy`, role, accessible name) — open the `.vue` file and read the template.

When in doubt, treat plans as a map, not a GPS.

## Canonical test skeleton

### OJS-only spec
```js
// @ts-check
const {test, expect} = require('../support/fixtures.js');

// Default logged-in user for this file
test.use({user: 'sectioneditor.ana'}); // section editor

test.describe('feature area', () => {
    test('happy path', {tag: '@smoke'}, async ({page, ojsApi}) => {
        await page.goto('/dashboard');
        await expect(page.getByRole('heading', {name: 'Dashboard'})).toBeVisible();
        // ...
    });
});
```

### Shared pkp-lib spec
```js
// @ts-check
const {test, expect} = require('../support/base-test.js');

test.use({user: 'admin'});

test('shared behavior', {tag: '@smoke'}, async ({page}) => {
    await page.goto('/');
    await expect(page).not.toHaveURL(/\/login/);
});
```

### Multi-actor
```js
// @ts-check
const {test, expect} = require('../support/fixtures.js');

test.use({user: 'sectioneditor.ana'}); // section editor is the "primary" actor

test('editor assigns reviewer, reviewer accepts', async ({page, asUser}) => {
    await page.goto('/dashboard');
    // ... editor actions ...

    const reviewerCtx = await asUser('reviewer.julia');
    const reviewerPage = await reviewerCtx.newPage();
    await reviewerPage.goto('/dashboard');
    await expect(reviewerPage.getByRole('link', {name: /Review/i})).toBeVisible();
    // reviewerCtx auto-closes at teardown
});
```

## Things to avoid

- **Depending on absolute database IDs.** `submissionId = 1` is wrong — use the ID returned by `ojsApi.createSubmission()` or scrape it from the page.
- **Changing seed data mid-test.** The seeded journal (`publicknowledge`) and the 18 seeded users are shared across parallel workers. Mutating them (renaming, deleting, changing roles) will break sibling tests. If a test needs a user or journal with specific attributes, create one via the API as per-test setup.
- **Running the test server manually and also via Playwright.** `webServer` in `config-factory.js:47-65` auto-starts PHP. Trying to run `npm run test:e2e:serve` in another terminal at the same time fights over port 8000. If you need a manual server for poking around, stop the Playwright run first.
- **Committing `.auth/` files.** Storage states contain session cookies. They're gitignored; if you see one staged, un-stage it.
- **Mutating a seeded account's flags.** No baseline account is `mustChangePassword`-flagged anymore — `manager.maya` logs straight in when you need "a journal manager". If a test needs an account with unusual flags (e.g. a forced password reset), create a throwaway user in a scratch journal instead of touching the roster.

## UI realities learned the hard way

- **Dashboard search reacts to `keyup` only.** `fill()` sets the value without firing it — the list never filters. Use `pressSequentially()`.
- **Paginated lists accumulate state across runs.** The test DB is long-lived locally; shared users like `author.alex` own hundreds of submissions. Never assert presence on an unscoped first page — search by the test's unique tag first. Extra trap: seeded drafts carry no `dateSubmitted` (real-draft parity) so they sort LAST in date-ordered lists.
- **Server-rendered TinyMCE values never reach the backing textarea.** Assert via `getTinyMceContent()` (support/tinymce.js), not the textarea value.
- **The wizard Steps rail collapses when it overflows** (non-current pills get `-screenReader`, 1px-clipped); a `force: true` click on a clipped pill is a silent no-op. Use `SubmissionWizardPage.gotoStep()`/`expectStep()` — they handle expansion, end-anchored name matching ('Review' vs 'Reviewer Suggestions'), and re-render-swallowed clicks.
- **Side-modal wrappers report `visibility: hidden` permanently** — anchor visibility assertions on inner content, not the wrapper.
- **`useFetch` tunnels DELETE *and PUT* via POST + `X-Http-Method-Override`; unauthorized API calls return 401** (not 403) — `waitForResponse` predicates on `request().method()` must accept POST for both (`useFetch.js`); match status assertions accordingly.
- **`getByRole` name strings are substring matches** — `{name: 'View'}` happily matches "Assign Re**view**ers". Use `exact: true` (or an anchored regex) for short, common words.
- **The reviewer dashboard endpoint (`_submissions/reviewerAssignments`) ignores `searchPhrase` AND pagination** — it returns the full list. Reviewer-side list assertions need scratch-journal scoping (or a bounded full-list assertion), not search.
- **The submission GET's `reviewAssignments` is a hand-rolled summary**: `statusId` + Y-m-d dates only — no `cancelled`/`declined`/`dateReminded`/`dateAcknowledged` fields. Assert state via `statusId` constants or the row's History modal, not via fields that aren't there.
- **Review files are grant-based**: seeded in-review submissions carry no review-round files; production promotes them via a client-side REST `files/{id}/copy?stageId=1` follow-up and the Add Reviewer modal's file selection writes the `review_files` grant. Mirror that flow; don't expect seeded files to be reviewer-visible.
- **A real wizard submit fires `AssignEditors`** (auto-assigns the journal's section editors), so `participants` on submitted scenarios is additive; seeding `participants: []` WITHOUT `submitted` is what produces a genuine needs-editor state.

## Live-probe cookbook (spec verification — learned 2026-07-10, calibration f1)

Throwaway probes that verify spec claims against the running app. These idioms cost
half a session to rediscover; use them as-is.

- **Authenticate with Playwright request contexts, never bare curl.** Log in via the
  real UI form in a `chromium` page, then fire probes through `context.request` —
  the context carries session cookies automatically, and `page.evaluate(() =>
  window.pkp?.currentUser?.csrfToken)` supplies the CSRF header for mutating calls.
  curl-based login is a trap: multilingual journals 302 `/login` → `/en/login`, so a
  naive `curl $ctx/login | grep csrfToken` reads an EMPTY page and every later
  request runs anonymous — see next bullet for why that's poisonous.
- **An anonymous XHR to a legacy grid op returns a plausible JSON denial** ("You
  don't currently have access to that stage…"), indistinguishable at a glance from a
  real role denial. NEVER trust a DENIED verdict without (a) proving the session is
  live (an API GET that returns 200) and (b) a positive control — a plainly-entitled
  actor (e.g. a pure section editor) running the SAME op and getting ALLOWED. A
  denial without a passing control is evidence of nothing.
- **Legacy grid-op URLs**: `.../$$$call$$$/grid/<path>/<op-name>` with the op
  HYPHENATED (`read-review`, not `readReview`) and the header `X-Requested-With:
  XMLHttpRequest`. camelCase op names or a missing XHR header → opaque 500s.
- **REST verbs vary per route** — `confirmReview` is PUT, not POST (a wrong verb
  can surface as a 500, not a 405). Check the `Route::` registration in the
  controller before concluding anything from an error status.
- **Scenario seeding**: users can be minted ONLY by the journal scenario's `users:
  [{username, roles, password?, …}]` (explicit `password` is honored; otherwise
  `username+username`); the submission scenario resolves usernames but never creates
  them. Multilingual fields (`name`, section `title`/`abbrev`, publication `title`)
  must be locale maps (`{"en": …}`) — a bare string 400s or worse. Per-reviewer
  `status` / `method` / `responseDueDate` / `reviewDueDate` make day-boundary and
  anonymity states seedable in one POST.
- **Dual-role traps**: an author-editor probe needs a user genuinely enrolled in
  BOTH groups who is the submitter — a bare stage assignment without the global
  author role does not trip the author checks (false negative). Conversely a
  same-user second `participants` entry rides on `build()`'s firstOr semantics
  (see the scenario notes above).
