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

### 1. UserAssignmentProcessor

**File**: `lib/pkp/classes/testing/scenario/Processor/UserAssignmentProcessor.php`
**Domain**: `users`, `user_user_groups`
**Current implementation summary**: For each `users[]` entry — looks up by username; if missing AND `password` supplied, creates the user via `Repo::user()->newDataObject([…]) + Repo::user()->add()`. Then for each `roles[]` string, resolves a UserGroup via `UserGroupLookup` and calls `Repo::userGroup()->assignUserToGroup($userId, $userGroupId)`.

**Canonical UI/REST entry points** (no single one — see below):

| Sub-operation | Entry point |
|---|---|
| User creation | `RegistrationForm::execute()` (`lib/pkp/classes/user/form/RegistrationForm.php:263`) — public registration · `UserRoleAssignmentReceiveController::finalize()` (`lib/pkp/classes/invitation/invitations/userRoleAssignment/handlers/api/UserRoleAssignmentReceiveController.php:90`) — invitation accept · `PKPInstall::createSiteAdmin()` (`lib/pkp/classes/install/PKPInstall.php:250`) — install only. There is **no site-admin "Add User" form** on a per-context basis: every context-scoped role-add routes through the invitation pipeline. |
| Role assignment | `UserRoleAssignmentReceiveController::finalize()` lines 126–142 — only canonical entry point. |

**What the production paths do** (the union — Processor should match the lowest-common-denominator):

User creation (RegistrationForm + UserRoleAssignmentReceiveController):
- `Repo::user()->newDataObject()` + setters
- Sets `dateRegistered`, password (already encrypted at controller level via Validation::encryptCredentials)
- **Sets `inlineHelp = 1`** — both production paths do this (RegistrationForm.php:299, UserRoleAssignmentReceiveController.php:115)
- RegistrationForm only: sets `disabled` + `disabledReason` if `require_validation` config is set; sets user interests; sets blocked-notification preferences; auto-assigns Reader role; fires `UserRegisteredContext` / `UserRegisteredSite` event
- `Repo::user()->add()` — fires `Hook::call('User::add', [$user])`

Role assignment (UserRoleAssignmentReceiveController only):
- `Repo::userGroup()->assignUserToGroup($userId, $userGroupId, $effectiveDateStart, $endDate, $masthead)` — Repository.php:312
- The Repository method itself only does `UserUserGroup::create()` + masthead cache invalidation; no events, no hooks
- Marks the originating Invitation row as `ACCEPTED` (`UserRoleAssignmentReceiveController.php:144`)

**What the Processor does today** (UserAssignmentProcessor.php):
- Creates user via `Repo::user()->newDataObject([…])` array form, sets userName/password/email/givenName/familyName/country/affiliation/mustChangePassword/dateRegistered. **Does NOT set `inlineHelp`**.
- Calls `Repo::user()->add()` (Hook::call fires — ✅ matches)
- Calls `Repo::userGroup()->assignUserToGroup($userId, $userGroupId)` with **only the first 2 args** — defaults `dateStart` to today, `dateEnd` to null, `masthead` to null
- Does **not** create an Invitation row

**Discrepancies**:

| # | Gap | Severity | Recommended fix |
|---|---|---|---|
| 1 | `inlineHelp` field left NULL on Processor-created users; both production paths set it to `1` | ⚠️ partial — real DB drift; tests querying `users.inline_help` would see different values | Set `'inlineHelp' => 1` in the `newDataObject()` array (UserAssignmentProcessor.php:80–101) |
| 2 | Spec doesn't expose `dateEnd` or `masthead` for `assignUserToGroup`; both default to NULL | ⚠️ partial — affects tests that need future-dated assignments or masthead presentation | Extend spec → forward optional `dateEnd` / `masthead` args from per-role entry through to `assignUserToGroup`. Schema bump. |
| 3 | No `Invitation` row created for role assignments; production always lands one (status=ACCEPTED) | ⚠️ partial — only matters for tests that hit `/api/v1/invitations` and expect to see prior assignments | Optional. Skip unless a test surfaces the gap. Recommend: document in PHPDoc rather than fix (Processor's role is "post-acceptance state", not "audit trail"). |
| 4 | RegistrationForm-specific side effects (UserRegisteredContext event, blocked notifications, user interests, auto-Reader assignment, validation-email gate) | ✅ matches by design | Processor simulates the *invitation-accept* path, not the *self-registration* path; those side effects are registration-form-only and intentionally skipped. No fix. |
| 5 | `Hook::call('User::add', [$user])` fires on both production paths and on Processor (via `Repo::user()->add()`) | ✅ matches | None. |
| 6 | `dateRegistered` set; password pre-encrypted; field accessors used either via array or setters land at the same DB row | ✅ matches | None. |

**Verdict**: ⚠️ 2 actionable gaps (#1, #2). Gap #3 is a deferred/document call.



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
