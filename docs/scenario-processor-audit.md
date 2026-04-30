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
| 1 | `inlineHelp` field left NULL on Processor-created users; both production paths set it to `1` | ✅ resolved | Set `'inlineHelp' => 1` in the `newDataObject()` array. **Resolved** in `e2e_revamp_2` lib/pkp commit (UserAssignmentProcessor: set inlineHelp=1). |
| 2 | Spec doesn't expose `dateEnd` or `masthead` for `assignUserToGroup`; both default to NULL | ⚠️ deferred — no current consumer | Extend spec → forward optional `dateEnd` / `masthead` args from per-role entry through to `assignUserToGroup`. Schema bump. **Deferred** until a test surfaces the need; YAGNI. |
| 3 | No `Invitation` row created for role assignments; production always lands one (status=ACCEPTED) | ⚠️ partial — only matters for tests that hit `/api/v1/invitations` and expect to see prior assignments | Optional. Skip unless a test surfaces the gap. Recommend: document in PHPDoc rather than fix (Processor's role is "post-acceptance state", not "audit trail"). |
| 4 | RegistrationForm-specific side effects (UserRegisteredContext event, blocked notifications, user interests, auto-Reader assignment, validation-email gate) | ✅ matches by design | Processor simulates the *invitation-accept* path, not the *self-registration* path; those side effects are registration-form-only and intentionally skipped. No fix. |
| 5 | `Hook::call('User::add', [$user])` fires on both production paths and on Processor (via `Repo::user()->add()`) | ✅ matches | None. |
| 6 | `dateRegistered` set; password pre-encrypted; field accessors used either via array or setters land at the same DB row | ✅ matches | None. |

**Verdict (initial)**: ⚠️ 2 actionable gaps (#1, #2). Gap #3 is a deferred/document call.
**Verdict (post-fix)**: ✅ #1 resolved; #2 deferred (YAGNI); #3 documented. No further action.

### 2. ContextBuilderProcessor

**File**: `lib/pkp/classes/testing/scenario/Processor/ContextBuilderProcessor.php`
**Domain**: `journals` (or `presses` / `servers`), `plugin_settings`, plus everything `PKPContextService::add()` installs (default user groups, email templates, genres, nav menus, contributor roles)
**Current implementation summary**: Builds a `Journal` data object, hydrates from spec, calls `app('context')->add()` (same service the UI calls). Optionally writes plugin settings through `PluginSettingsDAO::updateSetting`.

**Canonical UI/REST entry point**:
- Form / page: site-admin "Add Journal" form
- REST endpoint: `POST /api/v1/contexts`
- Controller method: `PKPContextController::add()` (`lib/pkp/api/v1/contexts/PKPContextController.php:317–360`)

**What the production path does**:
- `convertStringsToSchema(SCHEMA_CONTEXT, …)` to coerce types
- `$contextService->validate(VALIDATE_ACTION_ADD, …)` — schema validation, returns errors
- `$contextDao->newDataObject() + setAllData($params)`
- `$contextService->add($context, $request)` — the heavy lifting (PKPContextService.php:465–608):
  - `Hook::call('Context::defaults::localeParams', …)`
  - `app('schema')->setDefaults(SCHEMA_CONTEXT, …)` — fills in schema defaults
  - Auto-defaults `supportedFormLocales` / `supportedDefaultSubmissionLocale` / `supportedAddedSubmissionLocales` / `supportedSubmissionLocales` / `supportedSubmissionMetadataLocales` to `[primaryLocale]` *only* if not set
  - `$contextDao->insertObject() + resequence()`
  - Saves uploaded files (favicon, homepageImage, pageHeaderLogoImage)
  - `GenreDAO::installDefaults()` — default genres
  - `UserGroupRepository::installSettings()` — default user groups
  - Auto-assigns currentUser to default Manager group via `UserUserGroup::create()`
  - Creates context file dirs
  - `NavigationMenuDAO::installSettings()`
  - `Repo::emailTemplate()->dao->installAlternateEmailTemplates()` + `setTemplateDefaultUnrestirctedSetting()`
  - Adds default ContributorRoles (Author, Translator)
  - `PluginRegistry::loadAllPlugins()`
  - `Hook::call('Context::add', [&$context, $request])`

**What the Processor does today**:
- Builds `$data` array from spec defaults + optional fields (copyrightNotice, submitWithCategories, enableDois, doiPrefix, doiVersioning, registrationAgency, onlineIssn, printIssn, enablePublicComments, enableAnnouncements, publishingMode, enabledDoiTypes)
- **Skips** `validate()` — goes straight to insert
- **Mirrors `supportedLocales` to `supportedFormLocales` / `supportedSubmissionLocales` / `supportedSubmissionMetadataLocales` / `supportedAddedSubmissionLocales`** rather than letting the service default to `[primaryLocale]` (deliberate — see gap #3 below)
- Sets `enabled = 1` explicitly
- Stuffs admin into `Registry::get('user')` so `$contextService->add()` picks them up as the new context's first manager
- Calls `app('context')->add()` — same path as production
- Optionally writes plugin settings via direct `PluginSettingsDAO::updateSetting()`

**Discrepancies**:

| # | Gap | Severity | Recommended fix |
|---|---|---|---|
| 1 | Schema validation (`$contextService->validate()`) is skipped | ✅ acceptable | Tests own the spec; validation would just fail-fast on malformed specs we control. Document only. |
| 2 | `enabled = 1` set explicitly | ✅ matches | Same DB row state — context schema's default is enabled. No fix. |
| 3 | `supportedFormLocales` / `supportedSubmissionLocales` / `supportedSubmissionMetadataLocales` / `supportedAddedSubmissionLocales` mirrored from `supportedLocales` rather than defaulting to `[primaryLocale]` | ⚠️ deliberate divergence | This is *intentionally* off-default: a multilingual scratch journal needs all four arrays seeded so the publication validator + wizard locale panels accept multi-locale data. A real user adding a journal would later toggle these via Languages settings; the Processor seeds the post-configuration state up front. **Document, don't fix.** |
| 4 | Plugin settings written via direct `PluginSettingsDAO::updateSetting()` | ⚠️ partial | The UI path is each plugin's own `SettingsForm::execute()`, which typically just validates + writes the same DAO row. For most plugins the DB result is identical. For Crossref / DOI plugins specifically the form's `execute()` may fire a hook plugins listen to. **Defer**: only fix if a specific plugin's tests surface a regression. |
| 5 | `Hook::call('Context::add')` fires on both paths (via shared service `add()`) | ✅ matches | None. |
| 6 | Default genres / user groups / nav menus / email templates / contributor roles installed; currentUser auto-assigned as manager | ✅ matches | All inside service `add()`; identical regardless of caller. |
| 7 | `Registry::set('user', $admin)` trick to satisfy `$request->getUser()` inside service `add()` without rotating the browser session | ✅ matches | Manager assignment lands on admin user identically to production where the logged-in admin would be `$currentUser`. |

**Verdict**: ✅ parity (with one deliberate divergence on multilingual locales — by design — and one deferred concern on plugin-settings hooks that has no known impact today).

### 3. ParticipantProcessor

**File**: `lib/pkp/classes/testing/scenario/Processor/ParticipantProcessor.php`
**Domain**: `stage_assignments`
**Current implementation summary**: For each `participants[]` entry — resolves UserGroup via `UserGroupLookup`, calls `Repo::stageAssignment()->build()`, optionally `update()`s `recommendOnly` / `canChangeMetadata` flags if the spec specified them and the existing row's defaults don't match.

**Canonical UI/REST entry point**:
- Form / page: workflow page → "Stage Participants" panel → "Add Participant" sidemodal
- Controller: `StageParticipantGridHandler::saveParticipant()` (`lib/pkp/controllers/grid/users/stageParticipant/StageParticipantGridHandler.php:329–405`)
- Form: `AddParticipantForm::execute()` (`lib/pkp/controllers/grid/users/stageParticipant/form/AddParticipantForm.php:262–302`) — the same `Repo::stageAssignment()->build()` call

**What the production path does** (saveParticipant):
1. `AddParticipantForm::execute()` → `Repo::stageAssignment()->build($submissionId, $userGroupId, $userId, $recommendOnly, $canChangeMetadata)` — Eloquent firstOr-create on `stage_assignments`. **No hooks, no events fire** in `build()` itself (`lib/pkp/classes/stageAssignment/Repository.php:30–47`).
2. If the assigned UserGroup is the Manager role: `notificationMgr->updateNotification($request, getDecisionStageNotifications(), null, ASSOC_TYPE_SUBMISSION, $submissionId)` — recomputes pending decision-stage notifications.
3. **Removes `NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_REQUIRED` notifications** across all stages where the submission now has at least one assigned manager/sub-editor (lines 360–374).
4. Creates a "trivial" success notification for the *actor* ("Stage participant added") — UI feedback only.
5. Writes an `EventLog` row of type `SUBMISSION_LOG_ADD_PARTICIPANT` capturing `userFullName`, `username`, `userGroupName`.

**What the Processor does today**:
1. Calls `Repo::stageAssignment()->build()` — same Eloquent firstOr-create.
2. If existing row's flag values don't match spec: `$stageAssignment->update($flagUpdates)`. ✅ matches form's behaviour for the form's own `_assignmentId`-edit branch, although the trigger is different (Processor uses spec presence; form uses an existing assignment ID).
3. **Skips all post-form notification + event-log work** (steps 2–5 above).

**Discrepancies**:

| # | Gap | Severity | Recommended fix |
|---|---|---|---|
| 1 | `Repo::stageAssignment()->build()` is the shared write — no hooks, no events fire on either path | ✅ matches | None. |
| 2 | `EDITOR_ASSIGNMENT_REQUIRED` notification cleanup — production removes any pending instances when a manager/sub-editor lands; Processor doesn't | ✅ resolved | After all participants are processed, if any was a Manager/Sub-editor and the submission now has at least one such assignment, delete pending `EDITOR_ASSIGNMENT_REQUIRED` notifications (idempotent). **Resolved** in `e2e_revamp_2` lib/pkp commit (ParticipantProcessor: log + EDITOR_ASSIGNMENT_REQUIRED cleanup). |
| 3 | Decision-stage notification recompute on manager assignment | ⚠️ deferred | Touches `notificationMgr->updateNotification()` which has wide surface area. No current spec inspects decision-stage notifications; defer until a test surfaces the gap. |
| 4 | `EventLog` row of type `SUBMISSION_LOG_ADD_PARTICIPANT` not written | ✅ resolved | Append the same `Repo::eventLog()->newDataObject([...]) + add()` after each Processor `build()`, attributing to the admin user. **Resolved** in `e2e_revamp_2` lib/pkp commit (ParticipantProcessor: log + EDITOR_ASSIGNMENT_REQUIRED cleanup). |
| 5 | "Trivial" success notification for the actor | ✅ skip | UI-only feedback for the clicker; not relevant to seeded state. |
| 6 | Spec doesn't expose stage scoping per assignment (uses UserGroup's implicit stage) | ✅ matches | The form does the same — UserGroup membership in a stage is the gate (`UserGroupStage::withStageId()->withUserGroupId()`). |

**Verdict (initial)**: ⚠️ 2–3 actionable gaps (#2, #3, #4).
**Verdict (post-fix)**: ✅ #2, #4 resolved; #3 deferred (no current consumer).



## §2 · Remediation log

Per-discrepancy fixes. One commit per row. Audit-doc rows in §1 flip to ✅ as fixes land.

| Date | Processor | Discrepancy | Resolved by | Commit |
|---|---|---|---|---|
| 2026-04-30 | UserAssignment | `inlineHelp` left NULL on Processor-created users | Set `inlineHelp = 1` to match both production paths (RegistrationForm, UserRoleAssignmentReceive) | lib/pkp |
| 2026-04-30 | Participant | EventLog `SUBMISSION_LOG_ADD_PARTICIPANT` row not written | Mirror `Repo::eventLog()->add()` from `StageParticipantGridHandler::saveParticipant` for each participant | lib/pkp |
| 2026-04-30 | Participant | `EDITOR_ASSIGNMENT_REQUIRED` notifications not cleaned up after manager/sub-editor assignment | Delete `withAssoc(SUBMISSION, $id)->withType(EDITOR_ASSIGNMENT_REQUIRED)` once any editor lands (idempotent) | lib/pkp |

## §3 · Post-fix performance comparison

Re-run the same suite with `PKP_SCENARIO_TIMING=1` after Phase 2 closes. Compare median per fixture against §0.

| Endpoint | Fixture shape | Baseline median (ms) | Post-fix median (ms) | Δ (%) | Note |
|---|---|---|---|---|---|
| _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ | _(pending)_ |
