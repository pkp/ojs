# Playwright Patterns for OJS

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

1. **OJS tabs are `role="tab"`, not `role="button"`.** Cypress's `cy.contains('button', 'X')` matched anything labeled X; Playwright's `getByRole('button', {name: ...})` is strict. Stable hook for top-level tabs: `#{name}-button` (`#review-button`, `#setup-button`, etc.). See `lib/pkp/playwright/tests/multilingual.spec.js` for live usage.
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
test.use({user: 'dbarnes'});

test('editor does thing', async ({page}) => {
    // page is pre-authenticated as dbarnes
});
```
Works at `test.describe` level or file level. Storage state is cached on disk across runs — only pay login cost once per user per DB lifetime.

### Multi-actor flow → `asUser`
```js
test('editor assigns, reviewer accepts', async ({page, asUser}) => {
    // page is the default user (from test.use)
    const reviewerCtx = await asUser('jjanssen');
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

## Parallel-load lessons

The suite runs in parallel by default. The shared seed data is the unit of contention.

1. **`page.waitForURL(...)` with default `waitUntil:'load'` is fragile under parallel load.** Use `'commit'`.
2. **`/notification/fetchNotification` drains all pending notifications for a user.** Two parallel tests as the same user share + race for the toast queue. Don't assert on toasts in parallel-running specs as the same user; assert on the actual save endpoint via `waitForResponse`.
3. **`searchPhrase=` OR-joins on whitespace.** `searchPhrase: 'Published article {tag}'` matches every fixture-seeded "Published article" — falls off the `count=30` cap under load. Search by `tag` alone (single whitespace-free unique token).
4. **Mailpit inbox is shared across parallel tests in a run.** Specs that need a clean inbox call `pkpMail.clearAll()` themselves; don't auto-clear in `beforeEach`.
5. **`playwright/.auth/{user}.json` can go stale after `login-as` flows.** `PKPSessionGuard::signInAs/signOutAs` migrate the session and destroy the previous row. `ensureAuthStateFor` probes `/index/user/profile` before reusing storage state — it relogs in if the probe doesn't 200.

## Tag conventions

Tests that seed via the scenario API need a unique tag for parallel isolation. Two constraints:

- **`journals.urlPath` is varchar(32).** Long tags trigger 500s with truncation errors. Pattern: `prefix-w{parallelIndex}-{6-char-suffix}`.
- **Whitespace-free.** `searchPhrase=tag` OR-splits on whitespace, so any space in the tag broadens the search and falls off pagination.

## Tags

Filter on the CLI with `--grep @tagname`. Defined at `lib/pkp/playwright/tests/login.spec.js:10-15`:

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
Handled for you by `bootstrap.setup.js`. Seeds the `publicknowledge` journal, all 16 non-admin users, sections, categories, issues.

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
test.use({user: 'dbuskins'}); // section editor

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

test.use({user: 'dbuskins'}); // section editor is the "primary" actor

test('editor assigns reviewer, reviewer accepts', async ({page, asUser}) => {
    await page.goto('/dashboard');
    // ... editor actions ...

    const reviewerCtx = await asUser('jjanssen');
    const reviewerPage = await reviewerCtx.newPage();
    await reviewerPage.goto('/dashboard');
    await expect(reviewerPage.getByRole('link', {name: /Review/i})).toBeVisible();
    // reviewerCtx auto-closes at teardown
});
```

## Things to avoid

- **Depending on absolute database IDs.** `submissionId = 1` is wrong — use the ID returned by `ojsApi.createSubmission()` or scrape it from the page.
- **Changing seed data mid-test.** The seeded journal (`publicknowledge`) and the 17 seeded users are shared across parallel workers. Mutating them (renaming, deleting, changing roles) will break sibling tests. If a test needs a user or journal with specific attributes, create one via the API as per-test setup.
- **Running the test server manually and also via Playwright.** `webServer` in `config-factory.js:47-65` auto-starts PHP. Trying to run `npm run test:e2e:serve` in another terminal at the same time fights over port 8000. If you need a manual server for poking around, stop the Playwright run first.
- **Committing `.auth/` files.** Storage states contain session cookies. They're gitignored; if you see one staged, un-stage it.
- **Assuming `rvaca` just works.** He's flagged `mustChangePassword: true`. For "a journal manager", prefer `dbarnes` unless the test is specifically exercising the password-change flow.
