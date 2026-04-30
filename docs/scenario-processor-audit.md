# Scenario Processor parity audit

**Branch**: `e2e_revamp_2` (parent: `e2e_revamp`).
**Started**: 2026-04-30.
**Goal**: every Processor in `lib/pkp/classes/testing/scenario/Processor/` should produce the same database result as the equivalent UI / REST flow it represents — same rows written, same hooks fired, same notifications dispatched, same side effects. This doc is the per-Processor audit ledger and post-fix verification log.

## §0 · Performance baseline

Captured at the head of `e2e_revamp_2` *before any audit changes*, so the post-remediation timing has a clean baseline to diff against.

**Method**: `lib/pkp/playwright/support/api.js` writes one JSON line per `createSubmission` / `createJournal` call to `.scenario-timing.log` when `PKP_SCENARIO_TIMING=1`. The full Playwright suite is run once, then results are aggregated by `endpoint × spec keys` into median / max / count.

**Run command**:
```
PKP_SCENARIO_TIMING=1 npm run test:e2e:ojs
node scripts/aggregate-scenario-timing.js .scenario-timing.log
```

**Baseline numbers**: *(populated below after the first capture run)*

| Endpoint | Fixture shape (top-level keys) | Calls | Median (ms) | Max (ms) | Total (ms) |
|---|---|---|---|---|---|
| _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ |

## §1 · Audit findings

Each Processor gets one section below as the audit proceeds. Template:

```markdown
### N. {ProcessorName}

**Domain**: …
**Current implementation summary**: 1–2 lines.

**Canonical UI entry point**:
- Form / page: …
- REST endpoint: …
- Controller method: …

**What the production path does** (trace from the controller):
- …

**What the Processor does today**:
- …

**Discrepancies**:
| # | Gap | Severity | Recommended fix |
|---|---|---|---|
| 1 | … | ✅ matches / ⚠️ partial / ❌ skips side effect | … |

**Verdict**: ✅ parity / ⚠️ N gaps / ❌ N gaps requiring source change
```

*(sections appended below as each Processor is audited)*

## §2 · Remediation log

Per-discrepancy fixes. One commit per row. Audit-doc rows in §1 flip to ✅ as fixes land.

| Date | Processor | Discrepancy | Resolved by | Commit |
|---|---|---|---|---|
| _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ |

## §3 · Post-fix performance comparison

Re-run the same suite with `PKP_SCENARIO_TIMING=1` after Phase 2 closes. Compare median per fixture against §0.

| Endpoint | Fixture shape | Baseline median (ms) | Post-fix median (ms) | Δ (%) | Note |
|---|---|---|---|---|---|
| _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ |
