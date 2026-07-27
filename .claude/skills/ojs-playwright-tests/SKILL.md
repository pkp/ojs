---
name: ojs-playwright-tests
description: Live guide + app map for the OJS/OMP/OPS Playwright e2e harness (rebuilt 2026-07-27 after the full reset). Folder split, asUser/auth fixtures, seeded roster, scenario-endpoint schema (step-2 core live, richer keys return per feature), per-worker servers, patterns/lessons, and the application map of key screens. Use when writing or debugging Playwright specs, POMs, fixtures, or scenario seeds in any of the three apps.
---

# OJS Playwright Tests

> **STATUS 2026-07-27: REBUILT** (PROGRESS restart step 2). The harness exists
> again in all three apps — PHP test API, shared Playwright layer, per-app
> wiring — verified per PRINCIPLES' Rebuild-acceptance list; stage reports in
> `lib/pkp/docs/product/.reports/step2-harness/`. This skill is live truth for
> the step-2 surface. The scenario SCHEMA is deliberately minimal and grows
> per feature: `scenarios.md` marks live keys vs the recorded pre-reset
> surface it grows back into. Design invariants stay in
> `lib/pkp/docs/e2e/PRINCIPLES.md`; parity verdicts in
> `lib/pkp/docs/e2e/scenario-processor-audit.md`.

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

### `playwright/` (repo root) — the OJS feature suites

**Every OJS feature suite lives here** — the spec's common scenarios implemented
in OJS context AND the OJS-specific ones (per-app suites, PRINCIPLES multi-app
convention 1). OMP and OPS have their own equivalent `playwright/` trees in
their repos.

Structure:
```
playwright/
├── tests/             # Spec files — flat, no subfolder taxonomy (serial/ project exists, empty)
├── support/
│   ├── fixtures.js    # OJS test extension (ojsApi alias; feature fixtures land here)
│   └── app.context.js # Capability map (APP-GLOSSARY §2) + seed.actors archetype map
├── fixtures/
│   └── bootstrap.js   # Static seed data for publicknowledge (journal, 18 users, sections+editors, categories, issues)
└── .auth/             # Auto-generated storage-state cache per user (gitignored)
```
(`pages/`, `data/` and `fixtures/scenarios/` return when the first feature
specs need OJS-only POMs, app-local data, or spec-builders.)

OJS specs import from `'../support/fixtures.js'` — this layers OJS fixtures (`ojsApi`, `submission`) on top of the shared base.

**Spec-folder convention is flat.** No subfolder taxonomy yet — file name is the only grouping. Clusters can emerge as a refactor PR once ~25–30 specs are written and natural groupings are obvious. Don't pre-emptively invent folders.

### `lib/pkp/playwright/` — shared across OJS/OMP/OPS

This directory is **a git submodule shared across OJS, OMP, and OPS**. Changes here propagate to the other two apps. It keeps the app-agnostic **infrastructure layer only**: base fixtures, shared POMs, bootstrap + smoke specs (login). **Feature suites do NOT live here** — even a scenario common to all three apps is implemented per app, in each app's own `playwright/tests/` (maintainer ruling 2026-07-26; duplication between app suites is acceptable, the spec is the maintained artifact).

Be conservative about what counts as infrastructure; when in doubt it belongs in the app repo.

Structure:
```
lib/pkp/playwright/
├── tests/                   # Flat — shared specs live directly here
│   ├── bootstrap.setup.js   # Setup project — probe /api/v1/_test/bootstrap, install+seed if cold
│   └── login.spec.js        # Smoke test (personas via appContext.seed.actors)
├── support/
│   ├── base-test.js         # The extended `test` fixture — start here
│   ├── auth.js              # ensureAuthStateFor — storage-state cache w/ liveness probe
│   ├── api.js               # pkpApi — test-API client (bootstrap, createContext, createSubmission)
│   └── mail.js              # pkpMail — Mailpit HTTP API wrapper
├── pages/
│   ├── BasePage.js          # POM base class + siteUrl()/contextUrl()
│   ├── LoginPage.js         # form#login (lifts the maxlength=32 attr for long roster passwords)
│   └── DashboardPage.js     # post-login landing
├── data/
│   └── users.js             # The 18 baseline users + getPassword()
├── reset.js                 # test:e2e:reset — recreate DB + wipe .auth/
└── config-factory.js        # definePkpConfig({appName, appRoot, basePort}) used by all three apps
```
(`tinymce.js` and a typed scenario client are deliberately NOT recreated until
a spec needs them — `pkpApi.createContext/createSubmission` covers seeding.)

**Projects**: `setup → {shared, <app>} → <app>-serial` — four, not three: a
Playwright project has one `testDir`, so the shared specs are a sibling
project of the app suite. **Per-worker servers**: `php -S` is single-threaded,
so each parallel worker gets its own server at `basePort + parallelIndex`
(8000, 8001, … — launched by the config's `webServer` array; one shared DB and
files dir behind them).

Shared specs import from `'../support/base-test.js'`.

### Rule of thumb

Infrastructure (fixture, POM, bootstrap/smoke) → `lib/pkp/playwright/`.
Feature test → the app's own `playwright/tests/` — **always**, even when the
scenario is common to all three apps.

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

1. **Pick the folder.** Feature test → the app's own `playwright/tests/`; only shared infrastructure (fixtures, POMs, smoke) → `lib/pkp/playwright/`.
2. **Pick the import.**
   - Shared: `const {test, expect} = require('../support/base-test.js');`
   - OJS: `const {test, expect} = require('../support/fixtures.js');` — gives you `ojsApi` and `submission` fixtures too.
3. **Pick a user** — see `users.md` for the registry. A `test.use({user: 'username'})` at the top of the file sets the default logged-in user.
4. **Find the screen** — see `app-map.md` for URLs, Vue components, and handlers.
5. **Follow the locator + fixture conventions** — see `patterns.md`.

## Running tests

From the repo root, with `.env.playwright` in place (copy from
`.env.playwright.example`) and a local gitignored `config.test.inc.php`
(Postgres `<app>_test`, its own files dir, `[schedule] task_runner = Off`,
and `installed_locales = en,fr_CA` — the bilingual base context needs it;
the app reads it via the `PKP_CONFIG_FILE` env var, which the scripts set):

```bash
npm run test:e2e:install    # one-time, installs Chromium
npm run test:e2e:setup      # seed the test DB (cold: installs schema + seeds, ~1-3 min; warm: <1 s no-op via GET probe)
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
OPS (`ops-main`) on 8200 (each `+parallelIndex` per worker) — sibling checkouts under
`/Users/jarda/git/pkp/pkp-main/`, all on branch `e2e_ng`, test DBs
`ojs_test`/`omp_test`/`ops_test` (Postgres locally; harness code is
DB-driver-agnostic per PRINCIPLES item 8), same scenario endpoints and
`publicknowledge` context path. Each app repo has its own `playwright/` tree
(config, `support/app.context.js` with the capability map and `seed.actors`
roster, bootstrap seeds, and that app's feature suites — per-app suites derived
from each spec) plus its own `api/v1/_test/` subclasses and `tools/installTest.php`.
**One shared roster, subset-enrolled per app**: OMP splits the four reviewers
(`julia`/`paul` → External, `amara`/`adam` → Internal) and seeds series
`monographs`/`textbooks` (identified by `path`; no abbrev); OPS enrols
`sectioneditor.*` as Moderators (`ana`/`ravi` assigned to section `PRE`, `omar`
deliberately unassigned as a visibility control), `assistant.rita` as Editorial
Board Member (NO stage access), and has no editor/reviewer/copyeditor/layout/
proofreader accounts (`seed.actors` maps those archetypes to null). Cross-app
authoring conventions live in `lib/pkp/docs/e2e/PRINCIPLES.md` "Multi-app
conventions"; Mailpit is ONE shared instance across all three fleets — scope by
recipient + per-app tag.

## Companion files in this skill

- `users.md` — role constants, the 18 seeded users, password rule, login flow internals (incl. storage-state liveness probe), journal context
- `app-map.md` — screens organized by editorial journey: URL patterns, Vue components, PHP handlers, controls
- `patterns.md` — locator priority + OJS pitfalls, fixture selection, waiting strategy, parallel-load lessons, tag conventions, decision-button labels, POM hierarchy, canonical test skeleton, verify-before-trusting
- `scenarios.md` — scenario API (`/api/v1/_test/scenarios/*`) endpoints and schema, fixture builders at `playwright/fixtures/scenarios/`, decision/round-status quirks, Mailpit (`pkpMail`) usage

Load those on demand. You do not need to read them for every task.

## Permission contradictions

If a test's result contradicts the spec about who is allowed to do what,
follow the Routing line carried in your brief (`lib/pkp/docs/product/RUNBOOK.md`
"Private finding routing"). Never leave it as a skipped/`fixme` test or a
"not covered" header note.

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
