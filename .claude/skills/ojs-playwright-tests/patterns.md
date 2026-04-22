# Playwright Patterns for OJS

Conventions already established in the existing specs, plus the rationale so you can judge edge cases.

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
From `auth.js:54-56`:
```js
await page.waitForURL((url) => !url.pathname.endsWith('/login'), {timeout: 10_000});
```

### Wait on a web response for API-triggered updates
```js
const response = page.waitForResponse(r => r.url().includes('/api/v1/submissions') && r.status() === 200);
await page.getByRole('button', {name: 'Save'}).click();
await response;
```

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
- OJS-only (issues, galleys, OJS submission wizard) → `playwright/pages/`

## Data seeding

Prefer the API over UI for setup. Drive the UI only for what the test is actually exercising.

### Bootstrap (runs once per DB lifetime)
Handled for you by `bootstrap.setup.js`. Seeds the `publicknowledge` journal, all 15 non-admin users, sections, categories, issues.

### Per-test data
Use `ojsApi` (OJS specs) or `pkpApi` (shared specs) to POST/PUT/DELETE through the REST API. Example scaffolding:
```js
test('...', async ({ojsApi, page}) => {
    const submission = await ojsApi.createSubmission({section: 'ART', title: 'Test article'});
    // ... drive UI with submission.id ...
    // (cleanup happens via the `submission` fixture once ojsApi is implemented)
});
```

The `submission` fixture at `playwright/support/fixtures.js:27-33` is wired for this pattern but returns `null` today — its methods are TODO. When you encounter a TODO, flag it rather than inventing an alternative; the team is deliberately writing stubs ahead of the API.

The `scenarios` fixture at `lib/pkp/playwright/support/scenarios.js` is similarly a placeholder for composite scenarios (`createSubmissionInReview`, `createPublishedIssue`). All methods currently throw. Treat as future work.

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
- **Changing seed data mid-test.** The seeded journal (`publicknowledge`) and the 15 seeded users are shared across parallel workers. Mutating them (renaming, deleting, changing roles) will break sibling tests. If a test needs a user or journal with specific attributes, create one via the API as per-test setup.
- **Running the test server manually and also via Playwright.** `webServer` in `config-factory.js:47-65` auto-starts PHP. Trying to run `npm run test:e2e:serve` in another terminal at the same time fights over port 8000. If you need a manual server for poking around, stop the Playwright run first.
- **Committing `.auth/` files.** Storage states contain session cookies. They're gitignored; if you see one staged, un-stage it.
- **Assuming `rvaca` just works.** He's flagged `mustChangePassword: true`. For "a journal manager", prefer `dbarnes` unless the test is specifically exercising the password-change flow.
