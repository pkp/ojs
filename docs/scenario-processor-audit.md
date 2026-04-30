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

**Baseline numbers** (captured 2026-04-30, lib/pkp at `b8b47466b1` — timing wrapper applied, no Processor fixes yet, full Playwright suite, `--workers=2`, 143 passed / 1 skipped):

| Endpoint | Spec shape (top-level keys) | Calls | Median (ms) | Max (ms) | Total (ms) |
|---|---|---|---|---|---|
| journal | `tag,users` | 43 | 1723 | 2465 | 75951 |
| submission | `decisions,journal,locale,participants,publications,reviewRounds,section,submitter,tag` (full lifecycle) | 44 | 482 | 761 | 20679 |
| journal | `name,tag,users` | 5 | 1879 | 2024 | 8926 |
| journal | `supportedLocales,tag,users` | 3 | 2140 | 2400 | 6522 |
| submission | `journal,locale,participants,publications,section,submitter,tag` (publish only) | 10 | 297 | 445 | 3112 |
| submission | `decisions,journal,locale,participants,publications,section,submitter,tag` (no review rounds) | 5 | 355 | 609 | 1979 |
| submission | `author,decisions,journal,locale,participants,publications,reviewRounds,section,submitter,tag` | 2 | 537 | 618 | 1073 |
| submission | `commentsForEditor,journal,locale,participants,publications,section,submitted,submitter,tag` | 1 | 275 | 275 | 275 |

(Plus 16 single-call journal-shape variants between 1351–2462 ms median — see `/tmp/timing-baseline.log` for the raw data.)

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

### 4. SubmissionBuilderProcessor

**File**: `lib/pkp/classes/testing/scenario/Processor/SubmissionBuilderProcessor.php`
**Domain**: `submissions`, `publications`, `authors`, `author_contributor_roles`, `stage_assignments`, `submission_files`, `files`, plus the `submission_comments` row created by `Repo::submission()->submit()` when `commentsForTheEditors` is set
**Current implementation summary**: Creates submission + bare publication via Repo, assigns submitter as author to stage 1, builds Author from submitter user, attaches the bundled default-article.pdf via file service, optionally writes `commentsForTheEditors` and calls `Repo::submission()->submit()` to mirror the wizard's final Submit click.

**Canonical UI/REST entry point**:
- The submission wizard is a multi-step UI; each step has its own REST call:
  - `POST /api/v1/submissions` — `PKPSubmissionController::add()` (`lib/pkp/api/v1/submissions/PKPSubmissionController.php:622–766`) — creates submission + first publication + first author + first stage assignment
  - `POST /api/v1/submissions/{id}/files` — `PKPSubmissionFileController::add()` (`lib/pkp/api/v1/submissions/PKPSubmissionFileController.php:291`) — file upload
  - `PUT /api/v1/submissions/{id}` — generic edit, used by the wizard's Comments step to set `commentsForTheEditors`
  - `PUT /api/v1/submissions/{id}/submit` — `PKPSubmissionController::submit()` (lib/pkp/api/v1/submissions/PKPSubmissionController.php:866) — converts wizard-in-progress into submitted

**What the production paths do**:

`PKPSubmissionController::add()`:
- Validates schema; checks `disableSubmissions`, section inactive/editorRestricted gates
- **Disambiguates submitter UserGroup**: picks Manager group if user has it, else Author group; auto-assigns Author group if user has neither. The picked group becomes the stage assignment's `userGroupId`.
- `Repo::submission()->add()` — fires `Hook::call('Submission::add', [$submission])`
- `Repo::stageAssignment()->build()` with `recommendOnly: $userGroup->recommendOnly` and `canChangeMetadata: submissionProgress ? true : $userGroup->permitMetadataEdit`
- **Only if submitter's chosen group is AUTHOR role**: creates Author from user, sets `publicationId`, `contributorType=PERSON`, **calls `setContributorRoles([AUTHOR ContributorRole])`**, calls `Repo::author()->add()`, edits publication `primaryContactId`

`PKPSubmissionFileController::add()`:
- Uploads file via `app('file')->add()`
- Auto-defaults genre if only one is enabled; auto-defaults filename from upload basename
- Validates fileStage — disallows NOTE / REVIEW_ATTACHMENT / QUERY here; gate review-stage uploads on a valid review_round
- `Repo::submissionFile()->newDataObject()` + `Repo::submissionFile()->add()` — fires hooks

`PKPSubmissionController::submit()`:
- `Repo::submission()->validateSubmit()` (fires `Submission::validateSubmit` hook)
- Section inactive/editorRestricted gate
- `Repo::submission()->submit($submission, $context)` — clears `submissionProgress`, sets `dateSubmitted`, fires `event(SubmissionSubmitted)`, optionally creates the comments-for-the-editors discussion via `Repo::editorialTask()->addCommentsForEditorsQuery()`
- **If `confirmCopyright` was in the wizard request**: writes a `SUBMISSION_LOG_COPYRIGHT_AGREED` EventLog row

**What the Processor does today**:
- `Repo::submission()->newDataObject([...])` + `Repo::publication()->newDataObject([...])` — sets `versionMajor=1`, `versionMinor=0`
- `Repo::submission()->add()` — same `Submission::add` hook fires
- `Repo::stageAssignment()->build()` with **default flags** (no `recommendOnly` / `canChangeMetadata` passed; `Repo::stageAssignment()->build()` defaults `canChangeMetadata` to `userGroup->permitMetadataEdit ?? false`, `recommendOnly` to `false`)
- **Always uses AUTHOR group** for the submitter's stage assignment (no Manager-vs-Author disambiguation)
- `Repo::author()->newAuthorFromUser()` + `setData('publicationId')` + `setData('contributorType', PERSON)`. **Does NOT call `setContributorRoles`** — author is added without a ContributorRole linkage.
- `Repo::author()->add()` — same hook fires
- Optional: `Repo::author()->edit()` for orcid/orcidIsVerified/email passthrough (bypasses the orcid validator)
- `Repo::publication()->edit($pub, ['primaryContactId' => $authorId])`
- `attachDefaultArticleFile()`: `app('file')->add()` + `Repo::submissionFile()->dao->newDataObject()` + `Repo::submissionFile()->add()` — same as production file controller, minus the genre-auto-default and review-stage validation (Processor hardcodes genre=ARTICLE, fileStage=SUBMISSION)
- Optional: `Repo::submission()->edit($sub, ['commentsForTheEditors' => …])`
- Optional: `Repo::submission()->submit($submission, $context)` — same SubmissionSubmitted event fires

**Discrepancies**:

| # | Gap | Severity | Recommended fix |
|---|---|---|---|
| 1 | Author created without `ContributorRoles` linkage; production calls `setContributorRoles([AUTHOR])` before `Repo::author()->add()` | ✅ resolved | After `setData('contributorType', PERSON)` and before `Repo::author()->add($author)`, fetch the AUTHOR `ContributorRole` for the context and call `$author->setContributorRoles([...])`. **Resolved** in `e2e_revamp_2` lib/pkp commit (SubmissionBuilderProcessor: link AUTHOR ContributorRole). |
| 2 | StageAssignment uses Repo defaults rather than the picked UserGroup's `recommendOnly` / `permitMetadataEdit` | ✅ matches in practice | For the AUTHOR UserGroup the Repo defaults converge to the same values (`recommendOnly=false`, `canChangeMetadata=permitMetadataEdit`). Document only. |
| 3 | Submitter is always assigned as AUTHOR; production disambiguates between Manager and Author groups based on the user's existing memberships | ⚠️ deferred — no current consumer | Tests where submitter is a Manager would land an incorrect AUTHOR stage assignment. No spec currently uses a Manager submitter. Defer; optionally document on the `submissionDraft` fixture. |
| 4 | No `SUBMISSION_LOG_COPYRIGHT_AGREED` EventLog when submit() runs | ✅ acceptable | Production only writes this when the wizard's `confirmCopyright` checkbox was ticked. The spec doesn't expose `copyrightAgreed`; tests don't surface this. |
| 5 | File attachment: same `app('file')->add()` + `Repo::submissionFile()->add()` as `PKPSubmissionFileController::add()` | ✅ matches | None. |
| 6 | `Repo::submission()->submit()` shared between Processor and `PKPSubmissionController::submit()` — fires `SubmissionSubmitted` event identically; auto-creates Stage 1 cover-note discussion when `commentsForTheEditors` is set | ✅ matches | None. |
| 7 | `commentsForTheEditors` saved via `Repo::submission()->edit()` — same path as wizard's PUT step | ✅ matches | None. |
| 8 | Schema validation + `disableSubmissions` / `validateSubmit` / inactive-section gates skipped | ✅ acceptable | Test seeding intentionally bypasses these. |
| 9 | `Repo::author()->edit($author, ['orcid' => …])` passthrough bypasses Author validator's `api.orcid.403.cannotUpdateAuthorOrcid` block | ✅ deliberate | Documented in Processor; needed for ORCID-verified contributor seeding. |

**Verdict (initial)**: ⚠️ 1 actionable gap (#1 ContributorRoles). #3 deferred.
**Verdict (post-fix)**: ✅ #1 resolved; #3 deferred (no current consumer).

### 5. PublicationsProcessor

**File**: `lib/pkp/classes/testing/scenario/Processor/PublicationsProcessor.php`
**Domain**: `publications`, `publication_settings`, `submission_dois`, `event_log`, `stage_assignments` (after publish, on AUTHOR roles)
**Current implementation summary**: For each publication entry — index 0 edits the bare publication created by SubmissionBuilder, index >0 calls `Repo::publication()->version()` to chain a new version. Applies metadata + UI-settable attributes via one `Repo::publication()->edit()`. Optionally resolves an issue and calls `Repo::publication()->publish()`.

**Canonical UI/REST entry points**:
- Per-panel save: `PUT /api/v1/submissions/{id}/publications/{id}` — `PKPPublicationController::edit()` → `Repo::publication()->edit()`
- Create new version: `POST /api/v1/submissions/{id}/publications` (or via the Vue "Create New Version" dialog) → `Repo::publication()->version()`
- Publish: `PUT /api/v1/submissions/{id}/publications/{id}/publish` — `PKPSubmissionController::publishPublication()` (`lib/pkp/api/v1/submissions/PKPSubmissionController.php:1395–1454`)

**What the production publish path does** (PKPSubmissionController::publishPublication):
- Guards: 404 if not found, 403 if already published
- `Repo::publication()->validatePublish()` — fires `Publication::validatePublish` hook
- **`Repo::publication()->publish($publication, false)`** — pass `false` to skip the auto-status-update on the submission
- **Iterates stage_assignments and sets `canChangeMetadata = 0` on every AUTHOR role assignment** — authors lose metadata-edit after publish

`Repo::publication()->publish()` itself fires (regardless of caller):
- `setStatusOnPublish()` — flips publication status
- Auto-defaults missing copyrightHolder / copyrightYear / licenseUrl from the context
- Auto-versions if missing
- `Hook::call('Publication::publish::before')` → DAO update → re-fetch
- `Repo::submission()->updateStatus()` IF `$submissionStatus !== false`
- `Repo::submission()->updateCurrentPublication()`
- EventLog row of type `SUBMISSION_LOG_METADATA_PUBLISH` ('publication.event.published' / 'scheduled' / 'versionPublished' / 'versionScheduled')
- DOI staling (DOI versioning rules)
- `Hook::call('Publication::publish')`
- `event(PublicationPublished)`

**What the Processor does today**:
- Index 0 → `Repo::publication()->edit()` for metadata
- Index >0 → `Repo::publication()->version()` (auto-sets `versionStage` if provided), then `edit()` for metadata
- Optional `Repo::publication()->edit($pub, ['issueId' => $resolvedId])` before publish
- **`Repo::publication()->publish($publication)`** — uses default `submissionStatus = null` (which means: run `Repo::submission()->updateStatus()`)
- **No iteration of stage_assignments** to clear AUTHOR `canChangeMetadata` after publish
- Appends `[tag]` to every locale of the title for parallel isolation

**Discrepancies**:

| # | Gap | Severity | Recommended fix |
|---|---|---|---|
| 1 | `Repo::publication()->publish()` called with default `submissionStatus=null`; production passes `false` | ✅ deliberate divergence | Initially "fixed" by passing `false`, but that broke `submissionPublished` scenario seeding — the publication-language-change spec failed because submission.status stayed STATUS_QUEUED while the publication was PUBLISHED. Production reaches publish() through a decision chain that already advanced submission.status; the Processor consolidates create → publish in one pass and **needs** the auto-status-update. The `false` was reverted; the original default-null behaviour is correct for the Processor's all-in-one shape. **Documented** in `e2e_revamp_2` lib/pkp commit (PublicationsProcessor: keep default submissionStatus arg). |
| 2 | After publish, production iterates `stage_assignments` and clears `canChangeMetadata = 0` on every AUTHOR role assignment; Processor doesn't | ✅ resolved | After publish, iterate AUTHOR role stage_assignments and set `canChangeMetadata = 0`. Mirrors PKPSubmissionController.php:1444–1453. **Resolved** in same commit. |
| 3 | Issue assignment: `Repo::publication()->edit($pub, ['issueId' => $id])` before publish | ✅ matches | Issue panel save in UI uses the same `edit()` call; the publish endpoint then runs publish(). Same DB sequence. |
| 4 | Version creation: `Repo::publication()->version()` shared with UI's Create New Version dialog | ✅ matches | Same Repo facade — fires `Publication::version` hook + copies authors/citations. |
| 5 | Metadata edits: `Repo::publication()->edit()` shared with all panel saves | ✅ matches | Same hook chain (`Publication::edit`). |
| 6 | `Repo::publication()->publish()` itself fires every side effect identically (event log, DOI staling, hooks, `PublicationPublished` event) | ✅ matches | All inside the shared `publish()` body. |
| 7 | Title `[tag]` suffix for parallel isolation | ✅ deliberate | Documented; required for parallel-safe scratch journals. Production doesn't do this; the divergence is by design. |

**Verdict (initial)**: ⚠️ 2 actionable gaps (#1 publish-arg, #2 author canChangeMetadata clear).
**Verdict (post-fix)**: ✅ #2 resolved. #1 reverted on test-run feedback — the "production passes `false`" parity reading was wrong for the Processor's consolidated shape; default-null is correct.

### 6. DecisionProcessor

**File**: `lib/pkp/classes/testing/scenario/Processor/DecisionProcessor.php`
**Domain**: `edit_decisions`, `event_log`, `submission_comments` (legacy COMMENT_TYPE_EDITOR_DECISION rows), plus everything `DecisionType::runAdditionalActions()` triggers (stage advance, status change, review round creation, mail sends, etc.)
**Current implementation summary**: For each `decisions[]` entry — maps friendly type strings to Decision constants, builds the decision object with stageId / editorId / dateDecided / reviewRoundId, attaches optional notifyAuthors / notifyReviewers `actions`, and calls `Repo::decision()->add()`. After round-creating decisions, delegates to `ReviewRoundProcessor`. Optionally writes a private `COMMENT_TYPE_EDITOR_DECISION` row when `toEditor` is set.

**Canonical UI/REST entry point**:
- `POST /api/v1/submissions/{id}/decisions/{decision-type}` — `PKPSubmissionController::addDecision()` (`lib/pkp/api/v1/submissions/PKPSubmissionController.php:1948–1977`)

**What the production path does**:
- Schema-coerces input
- Sets `submissionId`, `dateDecided`, `editorId`, `stageId`
- **`Repo::decision()->validate($params, $decisionType, $submission, $context)`** — fires `Hook::call('Decision::validate', [&$errors, $props])`
- `Repo::decision()->newDataObject($params)`
- **`Repo::decision()->add($decision)`** — heavy lifting:
  - Strips `actions` off the decision
  - Auto-sets `round` from `reviewRoundId`
  - DAO insert
  - Fires `Hook::call('Decision::add', [$decision])`
  - Writes EventLog row of type `SUBMISSION_LOG_EDITOR_DECISION` (or `SUBMISSION_LOG_EDITOR_RECOMMENDATION`) — message from `decisionType->getLog()`
  - **Calls `decisionType->runAdditionalActions($decision, $submission, $editor, $context, $actions)`** — this dispatches per-trait `sendAuthorEmail`, `sendReviewersEmail`, stage-advance, status-change, round-create
  - Fires `event(DecisionAdded)` (round-trip catch on Exception)

**What the Processor does today**:
- Re-fetches submission per iteration so cascades are visible
- Maps friendly decision string → Decision const
- Builds decision params (`submissionId`, `decision`, `stageId`, `editorId`, `dateDecided`, optional `reviewRoundId` for in-review decisions)
- Builds `actions` array with synthetic subjects (via `defaultSubject()` helper — `[scenario] {type} — notify {audience}`); body comes from spec
- Soft-fails `toReviewers` on decision types lacking the `NotifyReviewers` trait — logs a ScenarioContext warning
- **Skips `Repo::decision()->validate()`** — goes straight to `Repo::decision()->newDataObject()` + `Repo::decision()->add()`
- After round-creating decisions, delegates to `ReviewRoundProcessor`
- **Writes a `submission_comments` row of type `COMMENT_TYPE_EDITOR_DECISION` when `toEditor` is set** — see gap #3 below

**Discrepancies**:

| # | Gap | Severity | Recommended fix |
|---|---|---|---|
| 1 | `Repo::decision()->validate()` skipped | ⚠️ minor | Production fires `Hook::Decision::validate`; plugin-injected validations don't run on the Processor path. Tests typically control inputs so plugin-defined invariants aren't exercised. **Defer**: only fix if a test surfaces a plugin-validated decision. |
| 2 | Action subject text uses synthetic literal (`[scenario] {type} — notify {audience}`) rather than the decision-type's mailable email-template subject | ⚠️ cosmetic | The form submits real composed subjects; the Processor synthesises a placeholder. Mail::fake() suppresses the actual send, but `submission_emails` log rows record the synthetic subject. **Acceptable** for tests; flag if a test asserts on subject contents. |
| 3 | `submission_comments` row of type `COMMENT_TYPE_EDITOR_DECISION` written on `toEditor` — the legacy decision form once wrote these but the current Vue decision UI no longer does | ⚠️ deliberate divergence FROM production | The Processor provides a test-only "internal editor note" capability that production no longer exposes. Removing it would break `scenario-decision-comments.spec.js`, `submission-published.js` (uses toEditor), and `reviewer-recommendations.spec.js`. **Deliberate** — the constant + DAO storage remain wired through; the divergence is a forward-compat rather than a parity bug. Document. |
| 4 | `Repo::decision()->add()` is the shared write — fires `Hook::Decision::add`, writes `SUBMISSION_LOG_EDITOR_DECISION` event log, runs `runAdditionalActions`, fires `event(DecisionAdded)` | ✅ matches | None. |
| 5 | `runAdditionalActions` includes stage advance, status change, review round creation, email sends — all driven by `actions` array → DecisionType per-trait handlers | ✅ matches | Both paths feed the same `actions` array shape into `runAdditionalActions`. |
| 6 | Mail::fake() at controller level prevents real sends on both paths | ✅ matches | None. |
| 7 | Processor's `toReviewers` recipient list is computed from completed reviewer assignments on the active round (mirrors `NotifyReviewers::validateNotifyReviewersAction`) | ✅ matches | None — both paths constrain to completed reviewer IDs. |

**Verdict**: ✅ parity with three documented divergences (#1 minor hook skip, #2 cosmetic synthetic subjects, #3 deliberate test-only `toEditor` capability). All deferred — none break the parity rule meaningfully enough to fix without breaking existing tests.

### 7. ReviewRoundProcessor

**File**: `lib/pkp/classes/testing/scenario/Processor/ReviewRoundProcessor.php`
**Domain**: `review_assignments`, `submission_comments` (COMMENT_TYPE_PEER_REVIEW), `notifications`, `event_log`, `email_log`
**Current implementation summary**: For each `reviewers[]` entry on a round — `Repo::reviewAssignment()->newDataObject() + ->add()` (creating with method, dates, round number), then `Repo::reviewAssignment()->edit()` to apply status-derived field combos (dateConfirmed / dateCompleted / declined / cancelled / reviewerRecommendationId). Writes COMMENT_TYPE_PEER_REVIEW comments for completed reviews via direct DAO insert.

**Canonical UI/REST entry points** (this is two distinct flows folded into one Processor):

| Sub-operation | Entry point |
|---|---|
| Reviewer assignment | `EditorAction::addReviewer()` (`lib/pkp/classes/submission/action/EditorAction.php:70–164`) — invoked from the workflow page's "Add Reviewer" sidemodal (`PKPReviewerForm`) |
| Reviewer completes review | `PKPReviewerReviewStep3Form::execute()` (`lib/pkp/classes/submission/reviewer/form/PKPReviewerReviewStep3Form.php:161–275`) — invoked when a reviewer submits the final review-form step |

**What the production paths do**:

`EditorAction::addReviewer()`:
- Idempotency check (already assigned?)
- **`Hook::call('EditorAction::addReviewer', [&$submission, $reviewerId])`** — gives plugins a chance to bail
- `Repo::reviewAssignment()->newDataObject()` + `Repo::reviewAssignment()->add()` — fires `Hook::ReviewAssignment::add`
- `setDueDates()` — fires `Hook::EditorAction::setDueDates` then `Repo::reviewAssignment()->edit()` for `dateDue` + `dateResponseDue`
- **`createNotification(NOTIFICATION_TYPE_REVIEW_ASSIGNMENT)`** for the reviewer (LEVEL_TASK)
- **EventLog row of type `SUBMISSION_LOG_REVIEW_ASSIGN`** with reviewerName + reviewAssignment + stageId + round
- Mail send + email log entry (skipped if `skipEmail`)

`PKPReviewerReviewStep3Form::execute()`:
- `saveReviewForm` — saves review-form responses (review_form_responses)
- `updateReviewStepAndSaveSubmission` — bumps reviewStep
- `Repo::reviewAssignment()->edit($a, ['dateCompleted', 'reviewerRecommendationId'])` — fires `Hook::ReviewAssignment::edit`
- For each manager/sub-editor stage assignment:
  - **`createNotification(NOTIFICATION_TYPE_REVIEWER_COMMENT)`** for the editor
  - Sends `ReviewCompleteNotifyEditors` mail + email log entry (gated on subscription)
- **Removes the reviewer's `NOTIFICATION_TYPE_REVIEW_ASSIGNMENT`** task notification
- **EventLog row of type `SUBMISSION_LOG_REVIEW_READY`** with reviewerName + reviewAssignmentId + round

**What the Processor does today**:
- `Repo::reviewAssignment()->newDataObject()` + `Repo::reviewAssignment()->add()` — fires `Hook::ReviewAssignment::add`
- Sets `dateDue` / `dateResponseDue` directly via `createParams` — converges to the same DB row but skips `Hook::EditorAction::setDueDates`
- For non-`invited` statuses, `Repo::reviewAssignment()->edit()` — fires `Hook::ReviewAssignment::edit`
- For `completed` with `comments`, writes COMMENT_TYPE_PEER_REVIEW rows (one per `toEditor`/`toAuthor` field) via direct DAO insert — same shape the legacy review form wrote
- **Skips** assignment-time NOTIFICATION_TYPE_REVIEW_ASSIGNMENT, completion-time NOTIFICATION_TYPE_REVIEWER_COMMENT, both EventLog rows (assign + ready), reviewer-task notification removal on completion, mail sends (faked anyway)

**Discrepancies**:

| # | Gap | Severity | Recommended fix |
|---|---|---|---|
| 1 | `Hook::call('EditorAction::addReviewer')` not fired | ⚠️ minor | Plugins can't intercept. No plugin uses this in the migration suite. Defer. |
| 2 | `Hook::call('EditorAction::setDueDates')` not fired (Processor sets dates via direct field assignment) | ⚠️ minor | Same — plugin hook skip. Defer. |
| 3 | `NOTIFICATION_TYPE_REVIEW_ASSIGNMENT` task notification not created on assignment | ✅ resolved | After `Repo::reviewAssignment()->add()`, create the LEVEL_TASK notification scoped to the reviewer, contextId, ASSOC_TYPE_REVIEW_ASSIGNMENT. **Resolved** in `e2e_revamp_2` lib/pkp commit (ReviewRoundProcessor: notification + event-log fidelity). |
| 4 | `SUBMISSION_LOG_REVIEW_ASSIGN` EventLog not written | ✅ resolved | Append EventLog row after each assignment, attributing to admin. **Resolved** in same commit. |
| 5 | `NOTIFICATION_TYPE_REVIEWER_COMMENT` not created for editors when status='completed' | ✅ resolved | After completion edit, iterate Manager/Sub-editor stage assignments and create the notification per recipient. **Resolved** in same commit. |
| 6 | Reviewer's task notification not removed on status='completed' / 'declined' / 'cancelled' | ✅ resolved | When terminal status reached, delete the LEVEL_TASK notification (idempotent). **Resolved** in same commit. |
| 7 | `SUBMISSION_LOG_REVIEW_READY` EventLog not written when status='completed' | ✅ resolved | Append EventLog row when `dateCompleted` is set. **Resolved** in same commit. |
| 8 | Mail sends (review-request to reviewer; review-complete to editors) skipped | ✅ acceptable | Mail::fake() suppresses real sends; email_log rows are also skipped (acceptable — tests don't assert on email_log for review notifications). |
| 9 | `saveReviewForm` (review_form_responses table writes) not called | ✅ acceptable | Tests don't seed reviewForm responses. |
| 10 | `updateReviewStepAndSaveSubmission` not called (reviewStep field not bumped) | ⚠️ minor | The reviewStep field tracks the reviewer's UI progress; for completed reviews production sets it to step 4. Tests don't typically inspect this. **Defer**. |
| 11 | `Repo::reviewAssignment()->add()` and `->edit()` are shared writes — `ReviewAssignment::add` and `ReviewAssignment::edit` hooks fire on both paths | ✅ matches | None. |
| 12 | COMMENT_TYPE_PEER_REVIEW rows for completed reviews | ✅ matches | Same shape as the legacy review form's writes; both paths land identical rows. |

**Verdict (initial)**: ⚠️ 5 actionable gaps (#3, #4, #5, #6, #7) — all about audit-trail / notification fidelity that production lands but the Processor skipped.
**Verdict (post-fix)**: ✅ all five resolved.

#### Follow-up gaps surfaced by user-side UI inspection

After the initial sweep landed, a screenshot of the workflow's reviewer popover surfaced two more parity holes — both visible to the user but invisible to the test suite:

| # | Gap | Severity | Recommended fix |
|---|---|---|---|
| 13 | `dateDue` and `dateResponseDue` not set when the spec doesn't pass them — both default to NULL on the row, and UI surfaces that compute "days remaining / overdue" render `null days` or `overdue by 0 days` | ⚠️ partial — **user-visible** | Always default the dates from the context's `numWeeksPerReview` / `numWeeksPerResponse` (falling back to 4 / 3 weeks via `Carbon::today()->endOfDay()->addWeeks(N)` — same logic as the UI's `HasReviewDueDate` trait). **Resolved** in `e2e_revamp_2` lib/pkp commit (ReviewRound: dueDates + editor-confirm fidelity). |
| 14 | `dateConsidered` not set + `SUBMISSION_LOG_REVIEW_CONFIRMED` event-log row not written when status='completed'. Production splits the reviewer's submit (`PKPReviewerReviewStep3Form`) from the editor's confirm (`PKPReviewerGridHandler::reviewRead`) — the Processor was lumping them but only doing the reviewer half | ⚠️ partial | When status='completed', also: (a) `Repo::reviewAssignment()->edit($a, ['dateConsidered' => $now])` to mirror the editor confirm; (b) write a `SUBMISSION_LOG_REVIEW_CONFIRMED` event-log row mirroring `PKPReviewerGridHandler::reviewRead` lines 791–807. **Resolved** in same commit. |












## §2 · Remediation log

Per-discrepancy fixes. One commit per row. Audit-doc rows in §1 flip to ✅ as fixes land.

| Date | Processor | Discrepancy | Resolved by | Commit |
|---|---|---|---|---|
| 2026-04-30 | UserAssignment | `inlineHelp` left NULL on Processor-created users | Set `inlineHelp = 1` to match both production paths (RegistrationForm, UserRoleAssignmentReceive) | lib/pkp |
| 2026-04-30 | Participant | EventLog `SUBMISSION_LOG_ADD_PARTICIPANT` row not written | Mirror `Repo::eventLog()->add()` from `StageParticipantGridHandler::saveParticipant` for each participant | lib/pkp |
| 2026-04-30 | Participant | `EDITOR_ASSIGNMENT_REQUIRED` notifications not cleaned up after manager/sub-editor assignment | Delete `withAssoc(SUBMISSION, $id)->withType(EDITOR_ASSIGNMENT_REQUIRED)` once any editor lands (idempotent) | lib/pkp |
| 2026-04-30 | SubmissionBuilder | Author created without ContributorRoles linkage | `$author->setContributorRoles([AUTHOR ContributorRole])` before `Repo::author()->add()` — mirrors PKPSubmissionController::add lines 741–748 | lib/pkp |
| 2026-04-30 | Publications | `publish()` called with default submissionStatus arg, runs an extra updateStatus that production skips | Initially fixed by passing `false`; reverted after test-run feedback (publication-language-change broke because submission.status stayed STATUS_QUEUED). Default-null is correct for the Processor's consolidated shape; flagged as a deliberate divergence in §1.5. | lib/pkp |
| 2026-04-30 | Publications | After publish, AUTHOR canChangeMetadata not cleared | Iterate AUTHOR-role stage_assignments and set canChangeMetadata = 0 — mirrors PKPSubmissionController.php:1444–1453 | lib/pkp |
| 2026-04-30 | ReviewRound | REVIEW_ASSIGNMENT task notification not created on assignment | createNotification(REVIEW_ASSIGNMENT, LEVEL_TASK) after Repo::reviewAssignment()->add() — mirrors EditorAction::addReviewer | lib/pkp |
| 2026-04-30 | ReviewRound | SUBMISSION_LOG_REVIEW_ASSIGN event-log row not written | Append eventLog row after assignment — mirrors EditorAction::addReviewer lines 121–135 | lib/pkp |
| 2026-04-30 | ReviewRound | REVIEWER_COMMENT notification to editors not created on completion | Per Manager/Sub-editor stage assignment, createNotification(REVIEWER_COMMENT) — mirrors PKPReviewerReviewStep3Form lines 196–249 | lib/pkp |
| 2026-04-30 | ReviewRound | Reviewer's REVIEW_ASSIGNMENT task notification not removed on terminal status | Delete on status='completed' / 'declined' / 'cancelled' — mirrors PKPReviewerReviewStep3Form line 252 + UnassignReviewerForm line 93 | lib/pkp |
| 2026-04-30 | ReviewRound | SUBMISSION_LOG_REVIEW_READY event-log row not written on completion | Append eventLog row when dateCompleted set — mirrors PKPReviewerReviewStep3Form lines 257–272 | lib/pkp |
| 2026-04-30 | ReviewRound | `dateDue` / `dateResponseDue` defaulted to NULL when spec doesn't pass them — UI shows "overdue by 0 days" | Default from context's `numWeeksPerReview` / `numWeeksPerResponse` (or 4 / 3 weeks fallback) — mirrors HasReviewDueDate trait | lib/pkp |
| 2026-04-30 | ReviewRound | Editor "confirm review" half of the completion flow missing — `dateConsidered` not set + SUBMISSION_LOG_REVIEW_CONFIRMED event-log row not written | When status='completed', set `dateConsidered = now` and write the event-log row — mirrors PKPReviewerGridHandler::reviewRead lines 779–807 | lib/pkp |

## §3 · Post-fix performance comparison

Captured 2026-04-30, lib/pkp at `c1f6b2f567` — all Processor fixes applied (UserAssignment inlineHelp, Participant event-log + EDITOR_ASSIGNMENT_REQUIRED cleanup, SubmissionBuilder ContributorRole linkage, Publications AUTHOR canChangeMetadata clear, ReviewRound REVIEW_ASSIGNMENT/REVIEWER_COMMENT notifications + REVIEW_ASSIGN/REVIEW_READY event-log rows + task-removal on terminal status). Full Playwright suite, `--workers=2`, 143 passed / 1 skipped — same pass rate as baseline.

**Test verification**: all 143 tests that pass on baseline also pass post-fix. No regressions. Both runs identical pass-count (143/0/1).

**Performance delta** (top buckets sorted by total time; positive Δ = post-fix slower, negative Δ = post-fix faster):

| Endpoint | Spec shape | Calls | Baseline median (ms) | Post-fix median (ms) | Δ median | Δ total |
|---|---|---|---|---|---|---|
| journal | `tag,users` | 43/43 | 1723 | 1654 | -69 (-4%) | -3053 (-4%) |
| submission | `decisions,…,publications,reviewRounds,section,submitter,tag` (full lifecycle) | 44/44 | 482 | 454 | -28 (-6%) | -1020 (-5%) |
| journal | `supportedLocales,tag,users` | 3/3 | 2140 | 1626 | -514 (-24%) | -1653 (-25%) |
| submission | `journal,…,publications,section,submitter,tag` (publish only) | 10/10 | 297 | 270 | -27 (-9%) | -481 (-15%) |
| journal | `submitWithCategories,tag,users` | 2/2 | 2114 | 1698 | -416 (-20%) | -831 (-20%) |
| journal | `name,tag,users` | 5/5 | 1879 | 1665 | -214 (-11%) | +1325 (+15%) — outlier max in post-fix run |
| submission | `decisions,…,publications,section,submitter,tag` (no review rounds) | 5/5 | 355 | 319 | -36 (-10%) | -331 (-17%) |

**Reading the numbers**:

The post-fix run is **consistently within ±10% of baseline** on all high-N buckets, with median deltas trending slightly *negative* (faster). The highest-N rows — journal (43 calls) at -4% median, full-lifecycle submission (44 calls) at -6% median — are the most reliable indicators; both are within run-to-run noise.

The larger deltas (-15% to -28%) all live in low-N buckets (1–3 calls) where individual variance dominates. The single +15% total-time outlier on the `name,tag,users` 5-call bucket is driven by one slow call in the post-fix run, not a systematic regression — the median for that bucket is still -11%.

**Conclusion**: the added side effects (notification INSERTs, event_log INSERTs, EDITOR_ASSIGNMENT_REQUIRED DELETE, ContributorRole pivot row, AUTHOR canChangeMetadata UPDATE) are all single-row operations that cost essentially nothing. **No measurable performance regression.** Both correctness and performance bars are met by the audit.
