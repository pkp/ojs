---
name: ojs-playwright-tests
description: Live harness guide + app map for the OJS/OMP/OPS Playwright e2e suite (rebuilt clean-room 2026-07-31, step-1 acceptance green on all three fleets). Folder split, asUser/auth fixtures, seeded roster, scenario-endpoint schema, per-worker servers, patterns/lessons, and the application map of key screens. Use when writing or debugging Playwright specs, POMs, fixtures, or scenario seeds in any of the three apps.
---

# OJS Playwright Tests

> **STATUS 2026-07-31: LIVE** — rebuilt clean-room from the design record
> (FULL RESET #2; the previous implementation on branch `e2e_ng` was not read
> back) and PRINCIPLES' Rebuild-acceptance list passed on all three fleets
> (cold bootstrap, login smoke, scenario seeds, reset tool, Mail::fake
> suppression). Design invariants stay in `lib/pkp/docs/e2e/PRINCIPLES.md`;
> parity verdicts in `lib/pkp/docs/e2e/scenario-processor-audit.md`.

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
├── tests/             # Spec files — flat, no subfolder taxonomy
│   └── serial/        # The ojs-serial project — globally-scanning specs (queue drains etc.)
├── support/
│   ├── fixtures.js    # OJS test extension (ojsApi alias; feature fixtures land here)
│   ├── legacy.js      # waitForJQueryIdle — legacy jQuery surfaces (app-local, not shared)
│   └── app.context.js # Capability map (APP-GLOSSARY §2) + seed.actors archetype map
├── pages/             # OJS-only POMs (OrcidPages.js, ReviewStagePages.js, UserInvitationPages.js)
├── fixtures/
│   ├── bootstrap.js   # Static seed data for publicknowledge (journal, 18 users, sections+editors, categories, issues)
│   └── files/         # Upload fixtures (article.pdf)
└── .auth/             # Auto-generated storage-state cache per user (gitignored)
```
(`data/` and `fixtures/scenarios/` return when the first feature specs need
app-local data or spec-builders.)

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
│   ├── mail.js              # pkpMail — Mailpit HTTP API wrapper
│   ├── jobs.js              # runJobs — drain the fleet's queued jobs (serial-project only)
│   └── env.js               # loadEnv(appRoot) — .env.playwright parser (shell wins)
├── pages/
│   ├── BasePage.js          # POM base class + siteUrl()/contextUrl()
│   ├── LoginPage.js         # form#login (lifts the maxlength=32 attr for long roster passwords)
│   └── DashboardPage.js     # post-login landing
├── data/
│   └── users.js             # The 18 baseline identities + getPassword()/getEmail()
├── reset.js                 # test:e2e:reset — drop+recreate DB, wipe files dir + .auth/
├── serve.js                 # test:e2e:serve — manual PHP server on the fleet's base port
└── config-factory.js        # definePkpConfig({appName, appRoot, basePort}) used by all three apps
```

The PHP side lives in `lib/pkp/classes/testing/` (Spec reader,
UserSeeder, ContextFactory, PKPBootstrapSeeder, scenario builders) +
`lib/pkp/api/v1/_test/PKPTestController.php`; each app adds
`api/v1/_test/{index.php,TestController.php}`, `classes/testing/*` subclasses
and `tools/installTest.php` (see `scenarios.md`).
(`tinymce.js` and a typed scenario client are deliberately NOT recreated until
a spec needs them — `pkpApi.createContext/createSubmission` covers seeding.)

**Projects**: `setup → {shared, <app>} → <app>-serial` — four, not three: a
Playwright project has one `testDir`, so the shared specs are a sibling
project of the app suite. **Per-worker servers**: `php -S` is single-threaded,
so each parallel worker gets its own server at `basePort + parallelIndex`
(8000, 8001, … — launched by the config's `webServer` array; one shared DB and
files dir behind them). Server output (request log, PHP warnings) is redirected
to `playwright/.server-logs/server-<port>.log` in the app root, so the terminal
shows only the reporter — check those files when debugging server-side errors.
A server adopted via `reuseExistingServer` (e.g. left over from `test:e2e:serve`)
keeps logging wherever it was started.

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
   - OJS: `const {test, expect} = require('../support/fixtures.js');` — gives you the `ojsApi` fixture too.
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
- `PKP_CONFIG_FILE` — absolute path to `config.test.inc.php`; the whole switch between the dev and test installs (the test DB and files dir are named in there)

**Always drive the fleets via `127.0.0.1`, never `localhost`.** All three
`config.test.inc.php` files pin `allowed_hosts` to `127.0.0.1` (+ the fleet's
port), so any page request carrying `Host: localhost` ends in a bare **400** —
after a 302 to the locale-prefixed URL, so the first response still looks fine
and only the followed redirect fails. The `_test` API answers on either host,
which makes the mistake worse: seeding succeeds, the browser step dies. Three
separate probe sessions lost time to this (2026-07-28).

- `PLAYWRIGHT_BASE_PORT` / `PLAYWRIGHT_WORKERS` — worker 0's port (OJS 8000 · OMP 8100 · OPS 8200) and how many per-worker PHP servers to start (unset = auto-detect: CPU cores − 2, minimum 2 — the workload is wait-bound, measured knee 2026-08-21)
- `TEST_API_KEY` — enables and gates `/api/v1/_test/*` (namespace answers 404 unless the var is in the server's environment, 403 unless the request's `X-Test-Key` header matches)
- `MAILPIT_URL` — Mailpit HTTP API, shared by every worker and all three fleets (default `http://127.0.0.1:8025`)

## The other two apps (OMP / OPS)

The campaign runs three fleets side by side: OJS on 8000, OMP (`omp-main`) on 8100,
OPS (`ops-main`) on 8200 (each `+parallelIndex` per worker) — sibling checkouts under
`/Users/jarda/git/pkp/pkp-main/`, all on the campaign branch (`e2e_ng_2` since
2026-07-31), test DBs
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
conventions"; Mailpit is ONE shared instance across all three fleets — scope
every assertion by a **unique throwaway recipient address** that names the app
and the test (`u53top-omp@mail.test`), and add a positive control whenever the
claim is silence. **This install has no Mailpit tags** (verified 2026-07-29:
`GET /api/v1/tags` → `[]`, every message `Tags: []`, nothing sets `X-Tags`), so
"scope by the per-app tag" is not something you can do — `find()`'s `contains`
is a subject/body content marker, a supplement to the recipient scope only.

## Companion files in this skill

- `users.md` — role constants, the 18 seeded users, password rule, login flow internals (incl. storage-state liveness probe), journal context
- `app-map.md` — screens organized by editorial journey: URL patterns, Vue components, PHP handlers, controls
- `patterns.md` — locator priority + OJS pitfalls, fixture selection, waiting strategy, parallel-load lessons, tag conventions, decision-button labels, POM hierarchy, canonical test skeleton, verify-before-trusting
- `scenarios.md` — scenario API (`/api/v1/_test/scenarios/*`) endpoints and the live step-2 core schema, recorded spec-builder designs (`playwright/fixtures/scenarios/` returns with the feature suites), decision/round-status quirks, Mailpit (`pkpMail`) usage

Load those on demand. You do not need to read them for every task.

## Permission contradictions

If a test's result contradicts the spec about who is allowed to do what, the
SPEC is wrong: report it so the finding reaches the spec's Findings register
(`lib/pkp/docs/product/RUNBOOK.md` step 7). Never leave it as a skipped/`fixme`
test or a "not covered" header note.

## Potential security concerns

If test or probe work surfaces something that could plausibly be a security
weakness — a role reaching data or actions beyond its entitlement, a guard
that does not hold, data exposed to the wrong audience — do NOT put it in a
spec, test, report, PROGRESS note, or commit message: these repos are public.
Append it to the maintainer's private file `../e2e_ng/security.md` (outside
every repo, next to `server-questions.md`) and keep the public artifacts
silent about it until the fix ships. Single home for the rule:
`lib/pkp/docs/product/RUNBOOK.md` "What goes where".

## Commit discipline

Owned by `lib/pkp/docs/product/RUNBOOK.md` (per-feature loop, Commit step —
single home). Short form: `lib/pkp` and app root commit separately; never bump
submodule pointers in ANY app repo — ojs, omp, or ops (`git restore --staged
lib/pkp` first; no re-pin commits, maintainer ruling 2026-07-28 — sync omp/ops
by checking out the same campaign branch (`e2e_ng_2`) in their `lib/pkp` and leave the
resulting `M lib/pkp` uncommitted); specs + campaign docs + shared harness
changes commit inside `lib/pkp`, app-only tests in each app's root. Both test
folders stay flat.

## Verify before trusting this skill

The file paths and line numbers cited here are a snapshot. UIs drift faster than docs. Before finalizing a test:

- If the skill names a Vue component, open it and confirm the controls/roles are still where the skill says they are.
- If a test will run, run it with `npm run test:e2e:ui` before claiming it works — Playwright auto-wait is forgiving but not infallible, and storageState caches can mask auth breaks.
- If a line number looks off, re-grep for the symbol rather than trusting the cached number.

When in doubt, treat this skill as a map, not a GPS.
