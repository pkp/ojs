# Progress — live state

**Pure state, nothing else.** Detailed findings belong in each spec's Findings
register, never here. Read together with `RUNBOOK.md` (the loop); style rules in
`TEMPLATE.md`; test rules in `docs/e2e/PRINCIPLES.md`.

**FULL RESET 2026-07-26 (maintainer decision — start over).** Too much detail had
piled up to focus. Deleted from the working tree (git history keeps everything —
never read the old artifacts back; regenerating without anchoring is the point):

- all feature specs and all Playwright tests + the entire harness in all three
  apps (scenario API, processors, test tools, npm scripts, POMs, fixtures);
- the OJS-only atlas, FEATURE-MAP and UNASSIGNED (the next atlas covers all
  three apps);
- the campaign's app-code fixes were REVERTED — including real product bugs
  (empty masthead, dead sitemap URLs, OAI Postgres fatal, roles-grid missing
  actions, login 32-char truncation, webFeed scope) — so the new process can
  rediscover them on its own evidence.

**What survives**: the canon — `CHARTER.md`, `RUNBOOK.md`, `TEMPLATE.md`,
`APP-GLOSSARY.md`, `docs/e2e/PRINCIPLES.md` (including the
scenario-endpoint design record) — and `.claude/skills/ojs-playwright-tests/`
as the harness design record. Test DBs and fleet setup facts remain valid.

**Mode: REVIEW/PILOT** — nothing runs autonomously; the maintainer launches each
step and reviews its output.

## Restart sequence (maintainer launches each)

1. **Phase 0 — surface atlas across ALL THREE apps** (the previous pass was
   OJS-only): mechanical sweeps per CHARTER Method → `atlas/*.md`, then a
   FEATURE-MAP with `apps:` applicability per feature.
2. **Harness rebuild** per PRINCIPLES "Scenario-endpoint design record"
   (scenario API, bootstrap roster, config factory, fleet wiring).
3. **First feature** spec + per-app tests under the canon — maintainer picks;
   one feature per session, stop for review.

## Features

_Recreated by Phase 0._

## Model-fallback log

_Empty since the reset. One row per subagent, appended by hand (RUNBOOK Model
discipline)._
