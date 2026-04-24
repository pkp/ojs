# E2E Playwright migration — roadmap

This is the shared roadmap for migrating the Cypress suite to Playwright as focused, feature-based specs. Each subsequent migration PR picks a row from §1, writes the spec, and ticks the row off. Endpoint extensions in §2 unblock later waves.

## Context

The main reason we're refactoring e2e coverage (in addition to moving to Playwright) is that today's Cypress suite is one long serial chain. A failure at spec #25 forces rerunning 1..24 first; a bad debug cycle is 30–40 min. That's a consequence of the fixture model: ~20 files under `cypress/tests/data/60-content/` named `*Submission.cy.js` double as test **fixtures** (create a submission, leave it on disk for later specs) AND as test **bodies** (embed editorial-workflow assertions mid-creation). Integration specs downstream then call `findSubmissionAsEditor('dbarnes', null, 'Corino')` to find "their" submission — a hard dependency on the serial order above having run first.

The Discussion Manager migration shipped on `e2e_revamp` demonstrates the alternative: each test calls `pkpApi.createSubmission(spec)` with a unique tag and owns its state end-to-end. No cross-spec dependencies, parallel workers work, a failed test blocks only itself.

## Principles

1. **A feature is defined by its capability, not by its UI surface.** One feature = one spec. If a capability exists on the editor side *and* the reader side (e.g., versioning: editor creates v2, reader sees a version picker), a single spec tests both. Split only when there's genuinely no shared concept (e.g., "announcements CRUD" is one feature; "article DC metadata on the reader page" is a different one — they don't share state).
2. **Each test seeds its own state.** Use `pkpApi.createSubmission(scenario)` per test; never rely on state left by another spec. Unique tags (worker-index + suffix) keep parallel runs from colliding.
3. **The bootstrapped journal is read-only for tests.** Bootstrap seeds a stable `publicknowledge` journal that every spec can *read from* (default sections, default email templates, default plugins off, etc.). Any test that needs to *mutate* journal-level configuration — creating sections, enabling Categories/Data-availability, adding navigation items, configuring a plugin (DOI, Crossref, Pubmed, ORCID), customising reviewer recommendations, creating task templates, changing wizard field config, etc. — must create its own scratch journal via extension **E0** (see §2). Same reason submissions are per-test: shared mutable state is what made Cypress fragile.
4. **Honor the shared / OJS-root split.** Behavior identical in OJS + OMP + OPS → `lib/pkp/playwright/tests/`. OJS-only concepts (issues, galleys, subscriptions, journal homepage, DOI, Pubmed, Crossref, native XML issue export) → `playwright/tests/`. POMs for shared UI components live under `lib/pkp/playwright/pages/` regardless of where the spec lives (the Discussion Manager precedent).
5. **Retire the submission-fixture specs entirely.** The 20 files under `cypress/tests/data/60-content/` have no feature value that isn't covered by more-specific specs elsewhere; their "data" value becomes per-test scenario seeding.
6. **Scenario endpoint grows only when needed.** Extend `PKPSubmissionScenarioController` lazily, per the first feature that needs a capability. Extensions are consolidated in §2.

## Organization: migration waves

Features are **not** grouped by UI area — most features span several surfaces, so area-grouping would be arbitrary. Each feature sits in exactly one **migration wave**, derived from a single objective question: *what's the most advanced scenario state the spec needs?*

| Wave | Enabling condition |
|---|---|
| 1 | No submission state — admin/config screens (may span admin + public page view, as long as no submission is needed). |
| 2 | Submission wizard — the test drives a fresh wizard run; no scenario seeding. |
| 3 | Draft / in-review / accepted submission — pre-publication editorial workflow. |
| 4 | Published content — publication lifecycle; each spec freely mixes editor actions and reader-side verification of the same capability. |
| 5 | Participants + permissions — role-specific scenarios that assert access control. |
| 6 | Admin cross-cutting — multi-journal, impersonation, jobs, API smoke. |
| 7 | Blocked — requires a scenario-endpoint extension (E1–E6 in §2). |
| 8 | Retire Cypress — delete the old suite once every row above is green. |

The wave also gives execution order: start at row 1, work down.

## §1 · Features by wave

**Flat path.** Every spec lives at `{Home}/playwright/tests/{Spec file}` — `Home = lib/pkp/` for shared specs, empty for OJS-only. **No subfolder taxonomy is predecided**; folders can emerge later as an organic refactor once ~15–20 specs are written and natural clusters are obvious. That avoids the "is Categories a settings/ thing or a submission-wizard/ thing?" trap that UI-area folders keep falling into.

Columns in the wave tables:
- **Home** — `lib/pkp` = shared · `ojs` = OJS-only.
- **Spec file** — filename inside `{Home}/playwright/tests/`.
- **Cypress source** — spec(s) contributing the coverage.
- **Tests** — concrete one-liners. `E:` = editor/admin tests · `R:` = reader tests · both may appear in one spec.
- **Ext?** — `—` if none needed; otherwise references §2.

### Wave 1 · No submission state

| # | Feature | Home | Spec file | Cypress source | Tests | Ext? |
|---|---|---|---|---|---|---|
| 1 | Announcements CRUD ✅ DONE | lib/pkp | `announcements.spec.js` | `lib/pkp/.../Announcements.cy.js` | E: create (TinyMCE body); edit; delete — on E0 scratch journal | **E0** |
| 2 | Navigation menus ⏸️ DEFERRED | lib/pkp | `navigation-menus.spec.js` | `lib/pkp/.../NavigationMenus.cy.js` | DEFERRED — the Navigation Menu Editor mixes modern Vue tabs with the legacy `pkp_controllers_linkAction` grid; worth porting only after evaluating whether to also migrate the legacy grid handler away or accept fragile text/CSS-class selectors | **E0** |
| 3 | Editorial masthead ✅ DONE | lib/pkp | `editorial-masthead.spec.js` | `lib/pkp/.../EditorialMasthead.cy.js` | R: masthead page renders for anonymous readers | — |
| 4 | Email templates ✅ DONE | lib/pkp | `email-templates.spec.js` | `lib/pkp/.../emailTemplates/EmailTemplates.cy.js` + `cypress/.../emailTemplates/EmailTemplates.cy.js` | E: toggle default template unrestricted→restricted with user-group assignment; create restricted custom template with TinyMCE body + two user groups; create unrestricted custom template and verify user-group checkboxes are hidden. Dropped reset-to-default and delete-custom flows (pure UI confirmations of API ops — no meaningful delta vs. the create tests) and the standalone "hide/show user groups" toggle tests (folded into the three create/edit flows). | **E0** |
| 5 | Multilingual form fields ✅ DONE | lib/pkp | `multilingual.spec.js` | `lib/pkp/.../Multilingual.cy.js` | E: toggle fr_CA UI-active flag from the Languages grid (round-trip via reload); enter French in the masthead acronym field when the UI locale is disabled and verify the value persists across reload. Submission-metadata-in-French (Cypress step 3) deferred — the existing submission-draft scenario fixture hard-codes `journal='publicknowledge'`, so an in-review submission on a scratch journal needs an inline scenario spec; worth a dedicated future row. | **E0** |
| 6 | Reviewer-recommendation customisation ✅ DONE | lib/pkp | `reviewer-recommendations.spec.js` | `ReviewerRecommendation.cy.js` | E: defaults render + have type; CRUD custom recommendation; active/inactive toggle. "Used recommendation can't be edited" and "inactive recommendation hidden in review form" deferred to row #28 (need in-review scenario) | **E0** |
| 7 | Issues ✅ DONE | ojs | `issues.spec.js` | `cypress/.../50-CreateIssues.cy.js` | E: create future issue; edit volume/number/year (value persists on reload); publish; set-current swaps between two published issues; unpublish moves the issue back to Future · R: anonymous archive page lists the published issue and its public view page loads. Reader "TOC renders with section grouping" reduced to "view page loads + shows Vol/No/Year heading" — a seeded TOC assertion requires issue-assigned published articles (row #30) and galleys (row #51). | **E0** |
| 8 | Sections ✅ DONE | ojs | `sections.spec.js` | `cypress/.../50-CreateSections.cy.js` | E: create new section; edit-inactive flag persists across reload; edit editor-only flag persists across reload. Wizard-side effect of editor-only sections deferred to row #12 (Wizard — section rules) to keep this spec scoped to admin-UI. | **E0** |
| 9 | Subscription types & policies ✅ DONE | ojs | `subscription-config.spec.js` | `Subscriptions.cy.js` (first half) | E: create subscription type (name + CAD + cost + duration + individual); edit subscription-policies form (contact name / email / mailing address) and verify persistence on reload; delete subscription type via legacy `show_extras` → Delete → reka-ui OK dialog. Dropped: reader-side access tests (row #52, needs E4); Distribution > Access publishingMode toggle and Distribution > Payments gateway config (not part of the subscription-config capability in the absence of reader assertions); per-issue access-status config (row #52). | **E0** |

### Wave 2 · Submission wizard

Each test runs a fresh wizard session; the wizard writes through its normal API.

| # | Feature | Home | Spec file | Cypress source | Tests | Ext? |
|---|---|---|---|---|---|---|
| 10 | Wizard — validation ✅ DONE | lib/pkp | `wizard-validation.spec.js` | `SubmissionWizard.cy.js` tests 4–5 | E: author clears Title, advances to Review, verifies errors banner + Title "This field is required." + file-missing warning + Submit disabled; re-opens Details, restores Title, returns to Review, verifies Title error clears. Full happy-path submit deferred — requires uploading an Article Text file (row #17's territory) since the no-file warning stays gating Submit. | — |
| 11 | Wizard — copyright gate ✅ DONE | lib/pkp | `wizard-copyright-notice.spec.js` | `SubmissionWizard.cy.js` test 3 | E: E0 scratch journal seeded with `copyrightNotice`; author runs the wizard end-to-end, verifies the notice text renders inside a `<blockquote>` in the Confirmation panel, toggles the `confirmCopyright` checkbox across unchecked→checked→unchecked and verifies Submit remains disabled (compound gate with the file-missing error). French-locale assertion dropped (covered by row #13). Submit-success deferred for the same file-upload reason as row #10. `ContextBuilderProcessor` extended to accept `copyrightNotice` as an optional passthrough so the journal is immutable-after-create. | **E0** (extended) |
| 12 | Wizard — section rules | lib/pkp | `wizard-section-rules.spec.js` | `SubmissionWizard.cy.js` test 2 | E: inactive section hidden; editor-only section hidden from author | **E0** |
| 13 | Wizard — language change ✅ DONE | lib/pkp | `wizard-language.spec.js` | `SubmissionWizard.cy.js` test 5 | E: start wizard in English + Articles; open "Change Submission Settings"; pick French (Canada) + Reviews; save; verify caption re-renders and the Details step mounts with `titleAbstract-title-control-fr_CA` as the active locale control (French title value round-trips through editor.save()). Dropped the journal-field-config mutation (row #16's scope), the file upload + "Submission complete" assertion (row #17), and the English-alongside-French secondary-locale reveal (covered by row #5's multilingual spec). Extends `SubmissionWizardPage` with `openReconfigureModal` / `changeReconfigureSettings` / `setCommentsForEditors` / `currentSubmissionId`. | — |
| 14 | Wizard — comments → discussion ✅ DONE (partial) | lib/pkp | `wizard-comments-become-discussion.spec.js` | `SubmissionWizard.cy.js` test 1 | E: start wizard; skip Upload Files + Details + Contributors; type comment into commentsForTheEditors TinyMCE on "For the Editors" step; advance to Review; verify comment text renders in the "For the Editors" review panel (autosave + review-panel binding). **Dropped**: the end-to-end "comment becomes a Stage 1 discussion with editors + author as participants" assertion — wizard submit requires an Article Text file (row #17 / E1) and the scenario API bypasses `Repo::submission()->submit()`, the only code path that calls `addCommentsForEditorsQuery`. Reopens at row #17 once file upload lands. Substituted `dbarnes` for Cypress's `ccorino` (not in Playwright baseline). | — |
| 15 | Categories | lib/pkp | `categories.spec.js` | `lib/pkp/.../Categories.cy.js` | E: field hidden by default; enable in settings; author selects; stored on submission | **E0** |
| 16 | Wizard — field-config reset | ojs | `wizard-config-reset.spec.js` | `SubmissionWizard.cy.js` test 6 | E: toggle wizard field configuration; verify effect in new wizard session | **E0** |
| 17 | Filenames sanitization | lib/pkp | `filenames.spec.js` | `lib/pkp/.../Filenames.cy.js` | E: upload with unsafe chars; multilingual filename preserved in download | — |

### Wave 3 · Draft / in-review / accepted (pre-publication workflow)

Scenario seeds the appropriate pre-publication state and the spec exercises workflow UI.

| # | Feature | Home | Spec file | Cypress source | Tests | Ext? |
|---|---|---|---|---|---|---|
| 18 | Discussion Manager ✅ DONE | ojs (spec) / lib/pkp (POM) | `discussion-manager.spec.js` *(currently at `discussions/discussion-manager.spec.js` — flatten during folder refactor)* | `Discussions.cy.js` | E: full CRUD + access control | — |
| 19 | Decision: send to review | lib/pkp | `decision-send-to-review.spec.js` | embedded in `AmwandengaSubmission`, `CcorinoSubmission`, `DdioufSubmission` | E: open stage-1 submission; send to review; stage indicator advances | — |
| 20 | Reviewer assignment (UI-level) | lib/pkp | `reviewer-assignment.spec.js` | embedded in `AmwandengaSubmission`, `DdioufSubmission`, `DphillipsSubmission` | E: assign reviewer; set anonymity; set due date; reviewer appears in list | — |
| 21 | Decision: decline | lib/pkp | `decision-decline.spec.js` | scattered in submission specs | E: decline at stage 1; decline after review; submission marked declined | — |
| 22 | Section-editor recommendation | lib/pkp | `section-editor-recommendation.spec.js` | `CcorinoSubmission.cy.js` test 3 | E: section editor recommends accept; editor sees recommendation | — |
| 23 | Stage-participant management | lib/pkp | `stage-participants.spec.js` | `DdioufSubmission.cy.js` (copyeditor / layout editor / proofreader) | E: add/remove participant; role filter; assignment scoped by stage | — |
| 24 | Decision: accept | lib/pkp | `decision-accept.spec.js` | `AmwandengaSubmission`, `DdioufSubmission` | E: accept-after-review; accept-without-review; stage advances to copyediting | — |
| 25 | Task templates (config + apply) | lib/pkp | `task-templates.spec.js` | `TaskTemplates.cy.js` | E: add per-stage template (settings); role-restrict; edit; apply during workflow on a seeded submission; restricted roles don't see restricted template | **E0** |

### Wave 4 · Published content (editor + reader merged)

Scenario seeds a published submission (optionally with issue + versions + DOI). Each spec freely mixes editor actions and reader verification of the same capability.

| # | Feature | Home | Spec file | Cypress source | Tests | Ext? |
|---|---|---|---|---|---|---|
| 26 | Decision: send to production | lib/pkp | `decision-send-to-production.spec.js` | `AmwandengaSubmission`, `DdioufSubmission` | E: send to production; participants preserved | — |
| 27 | Publication metadata editing | lib/pkp | `publication-metadata-editing.spec.js` | `AmwandengaSubmission.cy.js` | E: edit title/abstract/keywords/contributors; persists on publication · R: updated values appear on article page | — |
| 28 | Publish & unpublish | lib/pkp | `publish-unpublish.spec.js` | `AmwandengaSubmission.cy.js` tests 6–7 | E: publish draft · R: article loads publicly · E: unpublish · R: article returns 404 · E: republish · R: article loads again | — |
| 29 | Versioning | lib/pkp | `versioning.spec.js` | `AmwandengaSubmission.cy.js` tests 8–10 | E: create new version; edit draft version · E: publish new version · R: article page shows version picker with v1 + v2 · R: earlier version accessible via picker · E: unpublish v2 · R: v2 no longer listed | — |
| 30 | Issue assignment | ojs | `issue-assignment.spec.js` | `AmwandengaSubmission.cy.js` | E: assign to future issue · E: move to current · R: article appears in issue TOC · E: unassign · R: article removed from TOC | — |
| 31 | DOI assignment | ojs | `doi-assignment.spec.js` | `Doi.cy.js` | E: auto-assign; manual assign; versioned DOIs; deposit state · R: DOI meta tag on article page; DOI on printed citation | **E0** |
| 32 | DOI Crossref registration | ojs | `doi-crossref.spec.js` | `DoiCrossref.cy.js` | E: plugin config; deposit flow; mark registered | **E0** |
| 33 | Publication language change | ojs | `publication-language-change.spec.js` | `ChangeSubmissionLanguage.cy.js` | E: change blocked when published; unpublish; change allowed; restore original; republish · R: article page reflects the new language locale | — |
| 34 | Article DC metadata | ojs | `article-dc-metadata.spec.js` | `Z_ArticleViewDCMetadata.cy.js` | R: DC meta tags; missing-translation fallback; keywords; subjects; issue meta | — |
| 35 | Journal homepage | ojs | `journal-homepage.spec.js` | derived from implicit reader coverage | R: current issue renders; archive lists past issues; section grouping on TOC | — |
| 36 | Article statistics | ojs | `article-statistics.spec.js` | `Statistics.cy.js` | E: editor views article stats; metrics counters | — |
| 37 | Pubmed metadata | ojs | `pubmed-metadata.spec.js` | `Pubmed.cy.js` | E: plugin config (enable Pubmed export) · R: Pubmed meta tags on article page | **E0** |
| 38 | Public comments | lib/pkp | `public-comments.spec.js` | `lib/pkp/.../publicComents/PublicComments.cy.js` | E: enable comments plugin · R: post comment · E: moderator approves · R: comment renders | **E0** |
| 39 | OAI — DC endpoint | lib/pkp | `oai-dc.spec.js` | `lib/pkp/.../oai/DC.cy.js` | R: ListRecords returns DC for published item; GetRecord round-trip | — |
| 40 | Data-availability statements | lib/pkp | `data-availability.spec.js` | `lib/pkp/.../DataAvailabilityStatements.cy.js` | E: enable in settings · E: author sets in wizard · R: statement visible on article page | **E0** |

### Wave 5 · Access control

| # | Feature | Home | Spec file | Cypress source | Tests | Ext? |
|---|---|---|---|---|---|---|
| 41 | Recommend-only editor restrictions | lib/pkp | `recommend-only-editor.spec.js` | `AmwandengaSubmission.cy.js` tests 11–12 | E: recommend-only editor sees no decision buttons; can recommend | — |
| 42 | Section-editor metadata permissions | lib/pkp | `section-editor-metadata.spec.js` | `AmwandengaSubmission.cy.js` test 12 | E: section editor with/without metadata permission; UI matches | — |
| 43 | Author edit-published permission | lib/pkp | `author-edit-published.spec.js` | `AmwandengaSubmission.cy.js` tests 3–5 | E: author can edit draft; blocked after publish; re-enabled when editor toggles permission | — |

### Wave 6 · Admin cross-cutting

| # | Feature | Home | Spec file | Cypress source | Tests | Ext? |
|---|---|---|---|---|---|---|
| 44 | Login-as (impersonation) | lib/pkp | `login-as.spec.js` | `AmwandengaSubmission.cy.js` test 13 | E: admin logs in as user; logout returns to admin session | — |
| 45 | Jobs queue | lib/pkp | `jobs-queue.spec.js` | `lib/pkp/.../Jobs.cy.js` | E: enqueue; list; manually process; failed-jobs view | — |
| 46 | Multiple contexts | ojs | `multiple-contexts.spec.js` | `MultipleContexts.cy.js` | E: second journal; cross-journal navigation; user roles scoped per journal | uses **E0** as tool |
| 47 | API smoke | lib/pkp | `api-smoke.spec.js` | `lib/pkp/.../API.cy.js` + `cypress/.../API.cy.js` | E: health endpoint; auth required where expected; versioned responses | — |

### Wave 7 · Blocked — need scenario-endpoint extensions (see §2)

| # | Feature | Home | Spec file | Cypress source | Tests | Ext? |
|---|---|---|---|---|---|---|
| 48 | Decision: request revisions | lib/pkp | `decision-request-revisions.spec.js` | embedded | E: request revisions; author sees task; author uploads revised file | **E1, E2** |
| 49 | Reviewer completes review | lib/pkp | `reviewer-completes-review.spec.js` | embedded | E: reviewer accepts invite (via captured token); fills form; submits with recommendation + attachments | **E1, E2** |
| 50 | Review-round lifecycle | lib/pkp | `review-round.spec.js` | embedded | E: editor sees all reviews; closes round; starts round 2 | **E3** |
| 51 | Galleys | ojs | `galleys.spec.js` | `AmwandengaSubmission.cy.js` | E: add PDF galley; add HTML galley; label & URL path; delete · R: galley download links on article page | **E1, E5** |
| 52 | Subscription-based access | ojs | `subscription-access.spec.js` | `Subscriptions.cy.js` (second half) | R: anon reader blocked; subscriber reads; editor reads | **E4** |
| 53 | Native XML: submission | ojs | `native-xml-submission.spec.js` | `lib/pkp/.../NativeXmlImportExportSubmission.cy.js` (seeds an OJS submission) | E: export submission; reimport; asserts | **E1** |
| 54 | Native XML: issue | ojs | `native-xml-issue.spec.js` | `Y_NativeXmlImportExportIssue.cy.js` | E: export issue; reimport | **E1** |
| 55 | ORCID integration | lib/pkp | `orcid.spec.js` | `lib/pkp/.../orcid/Orcid.cy.js` + `cypress/.../orcid/Orcid.cy.js` | E: config · E: author authenticates · E: ORCID stored on profile · R: ORCID displayed on article page | **E0** + OAuth mocking |

### Wave 8 · Retire Cypress

- 56 · Delete `cypress/`, `lib/pkp/cypress/`, CI workflows, and `package.json` scripts. Only after every row in Waves 1–7 is green.

## §2 · Scenario-endpoint extensions

E0 is a **prerequisite** — build it first, before starting Wave 1 work on any journal-mutating feature. The others are ordered by downstream impact.

| ID | Extension | Unblocks | Description | Home |
|---|---|---|---|---|
| **E0** | **JournalScenarioController** *(prerequisite)* | 1, 2, 4, 5, 6, 7, 8, 9, 12, 15, 16, 25, 31, 32, 37, 38, 40, 46, 55 | `POST /api/v1/_test/scenarios/journal` creates a scratch journal (unique tag) with minimal defaults + assigns requested users/roles (journal manager, editor, etc.). Returned payload includes journal id + path + primary-manager credentials. Tests that mutate journal-level config use this instead of the bootstrapped `publicknowledge` journal. OMP/OPS get sibling `press` / `server` versions of the superclass. | OJS (sibling apps for OMP/OPS) + shared `PKPContextScenarioController` in `lib/pkp/api/v1/_test/` |
| E1 | **FilesProcessor** | 48, 49, 51, 53, 54 | Upload submission files, review attachments, galley files at any stage. Shape: `files: [{stage, genre, filename, fixture}]` pointing at `playwright/fixtures/files/`. | `lib/pkp` |
| E2 | **Email / invitation-token capture** | 48, 49, 55 | Intercept outbound mail during scenario run; expose review-invite / author-revision tokens via the scenario response so tests can "click the link" without reading email. | `lib/pkp` |
| E3 | **RevisionRoundProcessor** | 50 | Multi-round review: seed a submission with a completed round + author revision pending. | `lib/pkp` |
| E4 | **SubscriptionProcessor** | 52 | Seed subscription types and per-user subscriptions. Extends OJS's currently-empty `SubmissionScenarioController` (`api/v1/_test/SubmissionScenarioController.php`). | OJS |
| E5 | **GalleyProcessor** | 51 | Attach galleys (PDF/HTML) to a publication with specified file fixture. Pairs with E1. | OJS |
| E6 | **Decision-rich DecisionProcessor** | polish 19–26 | Existing processor seeds decision + deciding editor but lacks to-author / to-editor comments + recommendation payloads surfaced in UI. Extend existing. | `lib/pkp` |

### Operational note on E0

- **Granularity** — create a scratch journal per test (max isolation) or per describe block (amortised setup cost). Both are Playwright-idiomatic; per-describe usually wins because journal creation writes many default rows. Implementation decision deferred to when E0 lands.
- **Cleanup** — not required for correctness (DB is nuked between runs), but add a `deleteByTag` teardown hook if the per-run DB grows large.
- **Discoverability** — the returned payload should include path + primary-manager credentials so tests can `goto(journal.path + '/manager/settings')` without scraping the settings tree.

## How to use this doc

1. **Pick a row** from §1. Start at the top of the lowest open wave.
2. **Check §2** — if the row has a non-dash Ext? that is not yet built, either build the extension first or pick a different row.
3. **Write the spec** at `{Home}/playwright/tests/{Spec file}` following the shape of `playwright/tests/discussions/discussion-manager.spec.js`.
4. **Tick the row** — mark ✅ DONE next to the feature name in the PR that lands the spec.
5. **Update this doc** if the row's scope changes or you discover the Cypress source was misread.

Existing scenario fixtures to reuse: `playwright/fixtures/scenarios/submission-draft.js`, `submission-in-review.js`, `submission-published.js`. The scenario endpoint lives at `api/v1/_test/SubmissionScenarioController.php` (currently an empty OJS subclass) and `lib/pkp/api/v1/_test/PKPSubmissionScenarioController.php` (shared implementation).
