# OJS Product Specification Campaign — Charter

The contract for this campaign: **why** it exists, **what** is in scope, and the
invariants every wave must hold. **How** to run an iteration lives in `RUNBOOK.md`;
**how** to write a spec lives in `TEMPLATE.md`; **how** to write tests lives in
`docs/e2e/PRINCIPLES.md`; live state lives in `PROGRESS.md`. Started 2026-07-02;
the current mode always lives in `PROGRESS.md`'s banner.

## Mission

Document every OJS feature at the **business-logic level** — actors, fields, rules,
state, permissions, side effects — precisely enough that the feature could be
reimplemented from the spec alone, in language a product owner or QA person reads
without a developer. Audience: QA, developers, AI agents, and the test suite built
from each spec's canonical scenarios.

## Scope

**All three apps, OJS-anchored** (multi-app adopted 2026-07-26 after the two-pilot
trial; the operative rules live in `RUNBOOK.md` "The multi-app rules" and
`TEMPLATE.md` — the trial's plan, log and learnings report are in git history,
deleted 2026-07-26 after folding). In
scope: any feature reachable in OJS — one spec covers its behavior in OJS, OMP and
OPS, and tests are written for each app. Still out of scope, dropped not parked:
surfaces OJS never exposes (monographs, chapters, publication formats, the catalog,
`NOTIFICATION_TYPE_BOOK_*`, `*_INTERNAL` review-stage decisions, OMP/OPS-only Vue
managers unwired in `WorkflowPageOJS`) — extending to those is a separate maintainer
decision.

**Format: Markdown, not HTML.** Specs are reviewed raw and in diffs; inline HTML only
where a structure needs it (`<br>` in cells, `<sup>` footnotes, rare `<details>`).

## Method

Enumerate mechanically first, then document, then map coverage — "did we miss a
feature?" must be a grep, not a judgment call (round 1's single exploration pass
demonstrably missed features):

1. **Phase 0 — surface atlas** (`atlas/*.md`; RESET 2026-07-26 — the first
   pass was OJS-only, the re-run covers ALL THREE apps): mechanical sweeps,
   one per modality, emitting **atoms** (stable ID + code pointer + one line).
   Completeness over depth; no analysis in sweeps.
2. **Phase 1 — feature specs** (`specs/*.md`): written per `TEMPLATE.md` to the
   RUNBOOK loop, adversarially verified, claiming their atoms.
3. **Phase 2 — coverage crosswalk**: every spec scenario mapped against the test
   suite → covered / gap / unit-test-territory / accept-untested.

## Invariants

- **Atom claim invariant**: every atlas atom ends up claimed by exactly one spec, OR
  parked in `UNASSIGNED.md`, OR marked out-of-scope in its sweep file with a reason.
  The unclaimed count is the campaign's completeness metric.
- **Never force-fit**: a wrong grouping is worse than a deferred one — push poor fits
  onto `UNASSIGNED.md`. Group by **user intent** ("what would a journal manager call
  this?"), never by code-module boundaries. Litmus: would a QA person test these rules
  together?
- **As-built AND intent — the spec is the source of truth**: specs document what
  the code actually does. Behaviour that is internally inconsistent, loses data,
  contradicts a UI affordance, or would genuinely surprise a product owner gets a
  ⚠ + an entry in the spec's **Findings register** (TEMPLATE) with the author's
  bug-vs-intended call — non-blocking; the team settles calls on spec review. A
  rule that is merely strict is usually intended: write it plain plus a ❓ register
  entry with a stated lean, never assert a suspected intent the code doesn't
  prove. A spec that silently transcribes bugs as requirements is poison for QA.
  There is no separate bug ledger: any all-bugs view is computed from the
  registers.
- **Verified, not just written**: every spec passes an adversarial verification pass
  (refute the permission and state rules; attack liveness) and a readability pass
  (RUNBOOK). Ambiguous rules are probed **live** on the test environment, never
  guessed from code.
- **Liveness before documentation**: code existing is not evidence the feature exists
  — OJS carries superseded, unreachable surfaces. Establish a surface is reachable in
  the current UI before documenting it; record unreachable atoms as dead-code
  candidates in `UNASSIGNED.md` (a campaign deliverable). Where a legacy path and a
  Vue path are both live for the same job, document both and say which is primary.
- **Frontend-first, backend-verified**: the reader interacts with the UI, so lead
  with what each role sees and can do on screen; use backend policies/validation as
  the source of truth for rules and cross-check the two. Where they diverge, the UI
  reality is the headline and the divergence is a ⚠. An ability reachable only by
  hand-crafting an API call is "API-only", never a normal user capability.
- **Business language, one statement per fact**: the spec body reads as a functional
  spec for PO/QA; every code symbol, probe result and seeded account lives in `<sup>`
  footnotes and the Reference blocks. The non-negotiable style rules, the anchor
  format (stable symbols, never line numbers) and the mechanical lint gate are defined
  in `TEMPLATE.md` — the single home for spec style.

## Standing maintainer rulings (2026-07-25)

Committed home for the rulings from the Opus 5 eval review. They are folded
into TEMPLATE as specs are rebuilt after the 2026-07-26 reset; until then they
bind as written. Once that session lands,
this section shrinks to the invariant statements plus pointers into TEMPLATE —
style detail must not stay double-homed here:

- **Variance-based ownership**: behavior that is invariant across contexts is
  specified ONCE, in the mechanism's home feature; context features own the
  deltas — presence, configuration, permissions, consequences — and point to the
  home for mechanics (both directions verifiable). Litmus per sentence: "if I
  changed stage/role/surface, would this still be true?" Named special cases:
  - **Manager components**: reusable managers (file manager, participant
    manager, tasks & discussions, …) get their mechanics specified once in the
    manager's own feature; stage features own each instantiation — which panels
    mount, which actions/columns appear, and the role × state gates on that
    stage. Affordance-atom attribution follows the same split.
  - **Test budget corollary**: mechanics are deep-tested once in the home
    feature; context features test only gates/instantiation (duplicate
    mechanism coverage is a reviewable defect).
- **One shared workflow screen, author included** (refined 2026-07-25 — this
  wording supersedes any surviving "dual-dressing" phrasing): the dashboards
  are separate features (the editorial dashboard and My Submissions each own
  their list), but the workflow screen both open is ONE shared surface.
  Workflow-page specs cover EVERY role on it — the Author included — in the
  same permission rows: role determines what is available; it never creates a
  separate surface. Never split a stage into editor-view and author-view
  features, and never frame the author's access as its own reduced screen.
  The author's entry route (View on My Submissions) belongs to
  `author-dashboard`; everything after it belongs to the workflow features.
- **Glossary**: a living `docs/product/GLOSSARY.md` (seeded by the queued
  encoding session — PROGRESS banner) keeps PO/QA language
  consistent — on-screen names always win; a term may be coined only when the
  screen offers none; every coined term has ONE definition home (the glossary)
  and first use per spec carries a gloss or pointer. Applies to test naming too.

## Definition of done

- **Per spec**: the operational checklist is `RUNBOOK.md`'s Definition of done
  (single home).
- **Campaign** (single home — RUNBOOK points here): unclaimed atom count = 0
  (claimed / parked-with-reason / out-of-scope-with-reason); every PROGRESS row
  `done` or `parked` (row states defined in `PROGRESS.md`'s header); full suite
  within RUNBOOK's Budget & ceilings (≤ 700 tests, ≤ 25 min on a fresh DB);
  parked list + accumulated ledger findings reported to the maintainer.
- "Recreatable from the spec" sets the altitude; the lines above are the
  checkable bar.

## Operating rules

- State lives in these files, not in conversation — any session resumes from disk.
- No application-code changes unless a defect blocks tests from running green
  (race conditions and the like): those changes — and only those — are recorded
  in `docs/e2e/app-changes.md`. Product bug findings never go there; they live
  in the specs' Findings registers.
- **Live-probe etiquette** (invariant): probes never mutate shared seeded state.
  The operational rules live in RUNBOOK's "Ops & environment safeguards" and the
  `ojs-playwright-tests` skill.
