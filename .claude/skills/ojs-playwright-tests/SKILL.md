---
name: ojs-playwright-tests
description: DESIGN RECORD + app map for the OJS/OMP/OPS Playwright e2e harness. The implementation was scorched 2026-07-26 (full reset; git history keeps it) — this skill preserves the design to rebuild from (folder split, asUser, seeded-roster design, scenario-endpoint schema, patterns/lessons) plus the still-valid application map of key screens. Use when rebuilding the harness or writing tests once it exists.
---

# OJS Playwright Tests

> **STATUS 2026-07-26: the harness implementation described below was DELETED
> in the full campaign reset** (specs, tests, POMs, fixtures, scenario API —
> all of it; git history keeps everything, but per the rebuild philosophy the
> old code is not read back). This skill is retained as the DESIGN RECORD:
> the folder split, conventions, seeded-roster design, scenario schema and
> hard-won patterns are the ideas the rebuild follows. The design invariants
> live in `lib/pkp/docs/e2e/PRINCIPLES.md` ("Scenario-endpoint design record" +
> "Multi-app conventions"); `app-map.md` (screens/URLs/components) documents
> the application itself and remains valid regardless.

You are helping the user write Playwright tests for OJS. This skill carries the moving parts that don't live in a single file: which of two folders a spec belongs in, which seeded user has which role, where a given screen lives in the Vue/PHP sources, and the conventions already established in the existing specs.

## When to use

Use this skill when the user asks to:
- Write a new Playwright spec
- Modify an existing spec under `playwright/tests/` or `lib/pkp/playwright/tests/`
- Add a Page Object Model, fixture, or helper for Playwright
- Debug a Playwright test

Skip this skill when:
- The user is working on Cypress (`cypress/` directory) — different framework, different conventions, different users setup
- The task is general OJS development unrelated to testing

## The two playwright folders

OJS has two Playwright directories. Picking the wrong one means the test lives in the wrong repo.

### `playwright/` (repo root) — OJS-specific

Put tests here when the feature is **OJS-only**: issues, galleys, subscriptions, journal-specific submission wizard details, the journal homepage, reader-facing article views.

Structure:
```
playwright/
├── tests/             # Spec files — flat, no subfolder taxonomy
├── support/           # OJS-only fixtures (ojsApi, submission factory)
├── pages/             # OJS-only POMs (EditorialWorkflowPage, SubmissionWizardPage, IssuePage)
├── fixtures/
│   ├── bootstrap.js   # Static seed data for publicknowledge
│   └── scenarios/     # Spec-builders for the scenario API (submission-draft, etc.)
├── data/              # Test data
└── .auth/             # Auto-generated storage-state cache per user (gitignored)
```

OJS specs import from `'../support/fixtures.js'` — this layers OJS fixtures (`ojsApi`, `submission`) on top of the shared base.

**Spec-folder convention is flat.** No subfolder taxonomy yet — file name is the only grouping. Clusters can emerge as a refactor PR once ~25–30 specs are written and natural groupings are obvious. Don't pre-emptively invent folders.

### `lib/pkp/playwright/` — shared across OJS/OMP/OPS

This directory is **a git submodule shared across OJS, OMP, and OPS**. Changes here propagate to the other two apps. Put tests here when the feature exists identically in all three: login, dashboard mechanics, editorial workflow skeleton, reviewer submission flow, user profile.

Be conservative. If the test relies on any OJS-specific concept (a journal, an issue, a subscription), it does not belong here.

Structure:
```
lib/pkp/playwright/
├── tests/                   # Flat — shared specs live directly here
│   ├── bootstrap.setup.js   # Serial setup — seeds DB via /api/v1/_test/bootstrap
│   └── login.spec.js        # Smoke test
├── support/
│   ├── base-test.js         # The extended `test` fixture — start here
│   ├── auth.js              # ensureAuthStateFor — storage-state cache w/ liveness probe
│   ├── api.js               # pkpApi client
│   ├── mail.js              # pkpMail — Mailpit HTTP API wrapper
│   ├── tinymce.js           # TinyMCE input helpers
│   └── scenarios.js         # Scenario API stub (TODO)
├── pages/
│   ├── BasePage.js          # POM base class
│   ├── LoginPage.js         # /login form
│   └── DashboardPage.js     # post-login landing
├── data/
│   └── users.js             # The 18 baseline users + getPassword()
└── config-factory.js        # defineConfig() used by all three apps
```

Shared specs import from `'../support/base-test.js'`.

### Rule of thumb

Ask: *does this behavior exist and mean the same thing in OMP (monographs) and OPS (preprints)?* If yes → `lib/pkp/playwright/tests/`. If the test references issues, galleys, subscriptions, or the journal homepage → `playwright/tests/`.

## The `asUser` helper

Defined at `lib/pkp/playwright/support/base-test.js`.

```js
asUser: async (username) => BrowserContext
```

Returns a browser context **already authenticated** as the named user. First call for a given username performs a real UI login and caches the storage state to `playwright/.auth/<username>.json`. Later calls in the same run (and later runs until DB reset) short-circuit and load the file — but only after a cheap HTTP probe confirms the cached cookies still authenticate; impersonation flows can invalidate them. Every opened context auto-closes at test teardown.

Use `asUser` for **multi-actor flows** — an author submits, an editor reviews, a reviewer rates. For single-actor specs, prefer `test.use({user: 'editor.diana'})`: it wires storage state via the `storageState` fixture override and you get an authenticated `page` directly.

Example of both, together:

```js
const {test, expect} = require('../support/base-test.js');

test.use({user: 'sectioneditor.ana'}); // default context: section editor

test('section editor assigns reviewer, reviewer sees assignment', async ({page, asUser}) => {
    // page is already logged in as sectioneditor.ana (section editor)
    await page.goto('/dashboard');
    // ... editor actions ...

    // Open a second context as the reviewer, in parallel
    const reviewerCtx = await asUser('reviewer.julia');
    const reviewerPage = await reviewerCtx.newPage();
    await reviewerPage.goto('/dashboard');
    await expect(reviewerPage.getByRole('link', {name: /Review Assignment/i})).toBeVisible();
    // reviewerCtx auto-closes at teardown
});
```

## Quick start: writing a new test

1. **Pick the folder.** Shared behavior → `lib/pkp/playwright/tests/`. OJS-only → `playwright/tests/`.
2. **Pick the import.**
   - Shared: `const {test, expect} = require('../support/base-test.js');`
   - OJS: `const {test, expect} = require('../support/fixtures.js');` — gives you `ojsApi` and `submission` fixtures too.
3. **Pick a user** — see `users.md` for the registry. A `test.use({user: 'username'})` at the top of the file sets the default logged-in user.
4. **Find the screen** — see `app-map.md` for URLs, Vue components, and handlers.
5. **Follow the locator + fixture conventions** — see `patterns.md`.

## Running tests

(Design record — these npm scripts were deleted in the reset; this is the
script surface the rebuild recreates.) From the repo root, with
`.env.playwright` in place (copy from `.env.playwright.example`):

```bash
npm run test:e2e:install    # one-time, installs Chromium
npm run test:e2e:setup      # seed the test DB (~5 min cold, <1 s warm)
npm run test:e2e            # full run
npm run test:e2e:ojs        # only the ojs project (skips bootstrap if cached)
npm run test:e2e:ui         # Playwright UI mode — best for iterating
npm run test:e2e:debug      # PWDEBUG=1 step-through
npm run test:e2e:reset      # nuke the test DB (forces cold bootstrap next run)
npm run test:e2e:serve      # manual PHP server on :8000 for custom runs
```

Env vars the tests depend on (all in `.env.playwright.example`):
- `PLAYWRIGHT_BASE_URL` — default `http://127.0.0.1:8000`
- `OJS_DB_*` — the test database; must exist and be empty
- `OJS_FILES_DIR` — writable files dir, kept separate from Cypress
- `TEST_API_KEY` — gates `/api/v1/_test/*` bootstrap endpoints

## The other two apps (OMP / OPS)

The campaign runs three fleets side by side: OJS on 8000, OMP (`omp-main`) on 8100,
OPS (`ops-main`) on 8200 — sibling checkouts under `/Users/jarda/git/pkp/pkp-main/`,
all on branch `e2e_ng`, test DBs `ojs_test`/`omp_test`/`ops_test`
(Postgres), same scenario endpoints and `publicknowledge` context path. Each app repo
has its own `playwright/` tree (config, `support/app.context.js` with the
capability map and `seed.actors` roster, bootstrap seeds, and that app's full
feature suites — per-app suites derived from each spec). Cross-app authoring
conventions live in `lib/pkp/docs/e2e/PRINCIPLES.md` "Multi-app conventions";
Mailpit is ONE shared instance across all three fleets — tag per app.

## Companion files in this skill

- `users.md` — role constants, the 18 seeded users, password rule, login flow internals (incl. storage-state liveness probe), journal context
- `app-map.md` — screens organized by editorial journey: URL patterns, Vue components, PHP handlers, controls
- `patterns.md` — locator priority + OJS pitfalls, fixture selection, waiting strategy, parallel-load lessons, tag conventions, decision-button labels, POM hierarchy, canonical test skeleton, verify-before-trusting
- `scenarios.md` — scenario API (`/api/v1/_test/scenarios/*`) endpoints and schema, fixture builders at `playwright/fixtures/scenarios/`, decision/round-status quirks, Mailpit (`pkpMail`) usage

Load those on demand. You do not need to read them for every task.

## Commit discipline

Owned by `lib/pkp/docs/product/RUNBOOK.md` (per-feature loop, Commit step —
single home). Short form: `lib/pkp` and app root commit separately; never bump
submodule pointers from ojs-main root (`git restore --staged lib/pkp` first);
specs + campaign docs + shared harness changes commit inside `lib/pkp`,
app-only tests in each app's root. Both test folders stay flat.

## Verify before trusting this skill

The file paths and line numbers cited here are a snapshot. UIs drift faster than docs. Before finalizing a test:

- If the skill names a Vue component, open it and confirm the controls/roles are still where the skill says they are.
- If a test will run, run it with `npm run test:e2e:ui` before claiming it works — Playwright auto-wait is forgiving but not infallible, and storageState caches can mask auth breaks.
- If a line number looks off, re-grep for the symbol rather than trusting the cached number.

When in doubt, treat this skill as a map, not a GPS.
