# OJS Playwright e2e — app entry point

Entry point for Playwright e2e work in OJS. The knowledge lives in the
**shared docs inside the lib/pkp submodule** — read them on demand, they are
the single home (OMP and OPS carry the same per-app entry point and point at
the same files):

- `lib/pkp/docs/e2e/process/harness.md` — layout, fleets, config contract, env
  vars, running the suite, quick start. **Start here.**
- `lib/pkp/docs/e2e/process/patterns.md` — locators, waits, parallel-load
  lessons, tag conventions, POMs, probe cookbook.
- `lib/pkp/docs/e2e/process/scenarios.md` — seeding API (live + recorded
  designs), decision quirks, Mailpit.
- `lib/pkp/docs/e2e/process/users.md` — role vocabularies, the 18-user roster,
  passwords, login internals, `publicknowledge`.
- `lib/pkp/docs/e2e/process/PRINCIPLES.md` — the test-authoring contract (read when
  writing tests; briefs cite it).
- `lib/pkp/docs/e2e/specs/GLOSSARY.md` — spec vocabulary (Part I meanings,
  Part II cross-app names + capability gates).

Out of scope here: Cypress work (`cypress/` — legacy) and general OJS
development unrelated to testing.

## OJS-specific facts

- **Imports**: OJS specs `require('../support/fixtures.js')` (adds the
  `ojsApi` fixture); shared specs `require('../support/base-test.js')`.
- **Screen map**: `app-map.md` in this directory — URLs, Vue components, PHP
  handlers, and key controls organized by editorial journey.
- **Legacy jQuery surfaces**: `playwright/support/legacy.js` →
  `waitForJQueryIdle(page)` (OJS app-local; see patterns.md waiting
  strategy).
- **OJS POMs live today**: `playwright/pages/OrcidPages.js`,
  `ReviewStagePages.js`, `UserInvitationPages.js` (recorded designs listed in
  patterns.md).
- Fleet: port 8000, DB `ojs_test`, project name `ojs`
  (`npm run test:e2e:ojs` runs just the app project).
- OJS scenario overlays: `section` (abbrev), `issue` ({volume, number,
  year}); OJS-only recorded context keys include `issues[]` and
  `subscriptions[]` (scenarios.md).

## Escalations

- A test result contradicting the spec (permissions included) → the SPEC is
  wrong; report it to the feature's Findings register (RUNBOOK step 7). Never
  a skipped/`fixme` test.
- Anything security-shaped → never into public artifacts; routing in
  `lib/pkp/docs/e2e/process/RUNBOOK.md` "What goes where".
- Commit discipline (separate lib/pkp and app commits, never bump submodule
  pointers) → RUNBOOK, single home.
