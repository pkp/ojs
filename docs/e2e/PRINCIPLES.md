# OJS e2e Test Suite — Principles

The **test-authoring** contract for the Playwright e2e suite. Every test-writing session
follows these principles. The campaign's operating loop, test budget, and per-feature
definition-of-done live in `docs/product/RUNBOOK.md`; the spec-style rules live in
`docs/product/TEMPLATE.md`; the campaign invariants live in `docs/product/CHARTER.md`
(the spec-driven build: atoms → features → specs, under `docs/product/`). Progress state lives in
`docs/product/PROGRESS.md`, never in conversation memory — re-running the same prompt must
always resume correctly.

## Why this suite exists

The legacy Cypress suite was a serial fixture chain: tests depended on state left by
earlier specs, could not run in parallel, and a failure mid-chain forced re-running
everything before it. This suite replaces that with parallel-first Playwright tests where
each test seeds its own state through test-only scenario endpoints
(`/api/v1/_test/scenarios/*`).

## Architecture principles

1. **Isolation unit is the submission.** Most tests create their own submission(s) via the
   scenario endpoint and never touch anyone else's. The shared base journal
   `publicknowledge` (seeded by `playwright/fixtures/bootstrap.js`) is **read-only**: no test
   may mutate journal-level settings, sections, categories, issues, or the 17 seeded users.
   Tests that need journal-level mutations create a **scratch journal** via
   `POST /api/v1/_test/scenarios/journal` with a unique path.
2. **Scenario endpoints must be accurate.** A seeded scenario must leave the same database
   state, fire the same hooks, and produce the same notifications as a user performing the
   equivalent steps through the UI/REST API. Any change to a Processor requires a parity
   entry in `docs/scenario-processor-audit.md` before it merges.
3. **Endpoint scope stays balanced.** Extend a Processor only when multiple tests need
   the same state. One-off or rare states are reached by driving the UI inside the test
   that needs them. The scenario spec schema should stay small enough to hold in your head;
   when in doubt, don't extend.
4. **Seed via endpoint, drive UI only for the behavior under test.** Getting *to* the state
   is the endpoint's job; exercising the state is the test's job.
5. **No hard-coded waits.** Use Playwright auto-waiting and web-first assertions. If an
   animation or debounce timer causes flake, shorten the store-side timer under test mode
   instead of sleeping.
6. **Group assertions per scenario.** One seeded scenario can support several related
   assertions in one test. Don't pay scenario-seeding cost per assertion; equally, don't
   build mega-tests that obscure failures — a test should still verify one coherent behavior.
7. **Tests are independent.** Specs run in parallel workers in arbitrary order; no test may
   depend on another test having run, nor leave state that can affect any other test. Each
   test creates what it needs (own submission, own users, scratch journal) and asserts only
   against state it created. Mutations of shared singletons (site settings) must restore
   state within the test; anything that cannot be isolated that way runs in a dedicated
   serial project with an explicit note. **NEVER enrol a shared seeded user in a new role**
   — that persists a global role other suites depend on (this bit the build once: a test
   made a seeded section editor a manager of a scratch journal and it leaked into an
   unrelated permission test). Use dedicated throwaway users for any role-mutation probe.
8. **Mailpit is shared.** Never `clearAll()` outside the dedicated serial infrastructure
   spec. Assert emails scoped by recipient + the test's unique tag; use throwaway recipient
   users whenever a test counts messages or asserts absence, and pair every negative
   assertion ("no email sent") with a positive control message that bounds the wait.
9. **Globally-scanning operations run serially.** Scheduled tasks (reviewer/editorial
   reminders), site-level plugin toggles, site-settings mutations, and cache clears affect
   state across all journals and workers; they live in a dedicated serial Playwright project
   (`playwright/tests/serial/`), never in parallel specs. The serial project depends on the
   parallel app project, so it runs alone at the end.

## Organization

- **Tests are organized by feature**, one spec file (or a small set) named after the
  feature; the feature list and per-feature test budget live in `docs/product/PROGRESS.md`.
- **Placement rule:** behavior that exists identically in OMP/OPS → `lib/pkp/playwright/tests/`;
  OJS-only behavior (issues, galleys, subscriptions, DOIs, journal front end) →
  `playwright/tests/`. Subfolders per area are allowed and encouraged as the suite grows.

## Multi-app conventions (OJS · OMP · OPS)

The three fleets run side by side (OJS 8000 / OMP 8100 / OPS 8200; Postgres test DBs
`ojs_test` / `omp_test` / `ops_test`; same scenario endpoints). What gets tested per app
is owned by `docs/product/RUNBOOK.md` "The multi-app rules"; the authoring conventions
are:

1. **Per-app suites, derived from the spec** (maintainer ruling, 2026-07-26): the
   spec lists its COMMON scenarios first, then app-specific ones. Each app's suite
   implements every common scenario in that app's own context — its roles, seeded
   users, stages and vocabulary, not a literal OJS transplant — plus that app's
   specific scenarios, written in that app's repo (`<feature>.<app>.spec.js` for
   OMP/OPS). Duplication between app suites is acceptable — the spec is the
   maintained artifact; don't build sharing machinery to keep tests dry. A test may
   name its own app's seeded users and stages directly.
2. **The shared `lib/pkp/playwright/` tree** keeps the genuinely app-agnostic layer:
   base fixtures, POMs, bootstrap/smoke specs. Code there gates on capabilities from
   `support/app.context.js` (`appContext.hasReviewStage`), never app names, and
   resolves personas through `appContext.seed.actors` (archetype →
   seeded-username-or-null) — an archetype can be null on an app. Capability names are
   canonical in `APP-GLOSSARY.md`: glossary row first, then the same key in all three
   `app.context.js` files.
3. **Never write a test asserting a 🐞 register finding** — that freezes the defect as
   contract; the register entry is its record. A claim parked on an open ❓ is not a
   coverage gap; each app suite's file header declares what it deliberately does not
   cover.
4. **An absence test** asserts the surface is not offered AND pairs every negative with
   a positive control taken the same way; an absence assertion against an async-filtered
   list must be bounded by that filter's own response (the list analogue of the
   negative-mail rule).
5. **Concurrent fleets share Mailpit** — scope by recipient + a per-app tag; attribute
   failures by seed tag, never by row id (parallel writers make ids unstable).

## Scenario-endpoint design record

The concrete implementation may be rebuilt from scratch; these are the design
decisions that must survive any rebuild (each was earned the hard way):

1. **A test-only API namespace** (`/api/v1/_test/*`), gated by a shared-secret
   header key from the environment — never a config default, never present in a
   production install. The key must actually reach PHP's environment under the
   server manager used (this silently failed once and broke all seeding).
2. **Declarative, end-state scenario specs.** A seed request describes the state
   the test needs (a context; users with roles; a submission at stage N with
   review rounds, decisions, files, discussions, publications) and the builder
   walks the application to that state. Tests never script the journey to their
   starting point.
3. **The builder invokes REAL application services** — the same repositories,
   hooks, mails and notifications the UI path uses — and never hand-mirrors
   their side effects. A re-implementation that mirrors production behavior
   hides exactly what tests exist to find: a mirrored publish() once normalized
   away a real cross-app permission difference, and scenario-seeded submissions
   lacked notification rows real submissions create. Any deliberate deviation
   gets a parity entry reviewed before merge.
4. **Failure hygiene**: a failed scenario build must not leave half-created
   state behind (clean up or tag as orphan); an unsupported spec key must THROW,
   never be silently dropped (a silently-ignored reviewer block cost a real
   investigation).
5. **Cross-app schema**: an app-neutral core (`context`, not `journal`) with
   app-specific concepts as declared OVERLAY properties (sections/issues/galleys
   OJS-side; series/publication formats OMP-side; internal/external key on
   review rounds). Shared builder code may touch an app-only service ONLY when
   gated on that app's overlay key, and must never hard-code a workflow stage id
   (a hard-coded initial stage once made every seeded OPS submission invisible).
   Per-app subclasses own the app specifics.
6. **Identity roster**: role-keyed usernames (`manager.maya`, `editor.diana`,
   `sectioneditor.ana`, …), deterministic password rule (username doubled —
   mind client-side maxlength on login), one shared archetype map per app
   resolving archetype → username-or-null where a role doesn't exist.
7. **Harness facts worth keeping**: per-user storage-state auth cache with a
   liveness probe; an `asUser()` fixture for multi-actor tests; a config
   factory parameterized by base port so app fleets run side by side
   (8000/8100/8200) with per-worker port offsets; Postgres test DBs (Postgres
   strictness reproduces real defects MySQL hides); a reset tool that forces a
   cold bootstrap.

## Bootstrap data policy

- Base seed lives in `playwright/fixtures/bootstrap.js` (journal `publicknowledge`,
  17 users, sections, categories, issues). Seeded users and roles are documented in the
  `ojs-playwright-tests` skill.
- **Richer defaults are encouraged**: enable features and metadata most real journals use
  (e.g. additional submission-wizard metadata fields, categories, DOIs where it doesn't
  force per-test cleanup), so tests exercise representative configuration.
- A bootstrap change requires checking every implemented spec against the new defaults —
  do it deliberately, not casually.

## Commit discipline

Owned by `docs/product/RUNBOOK.md` (per-feature loop, Commit step): `lib/pkp` and root
commit separately, never bump submodule pointers.

## App-code change ledger

Any work that (a) changes non-test code (anything outside `playwright/`, `docs/`,
`.claude/`) or (b) diagnoses an app-side bug or flakiness source — even without fixing
it — appends a row to `docs/e2e/app-changes.md`. That file is the end-of-round review list
for production-relevant findings; scenario-endpoint parity notes stay in
`docs/scenario-processor-audit.md` instead.

## Budget & definition of done

The full-suite budget (**≤700 tests, ≤25 min on a fresh DB**) and the per-feature
definition-of-done are owned by `docs/product/RUNBOOK.md` — see its **Budget & ceilings**
and **Definition of done** sections. (An earlier round-1 target of ~500 tests / ~20 min is
superseded.)

## Related documents

- `.claude/skills/ojs-playwright-tests/` — developer guide: users, app map, patterns, scenarios
- `docs/scenario-processor-audit.md` — Processor parity audit ledger
- `docs/product/RUNBOOK.md` · `docs/product/CHARTER.md` — the spec-driven build loop + charter
- `.env.playwright.example` — local environment setup
