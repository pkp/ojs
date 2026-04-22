# OJS Application Map

Pointers to screens a Playwright test is likely to touch. Organized by editorial journey rather than URL, because test descriptions usually read *"reviewer accepts an assignment"*, not *"test /reviewer/submission/123"*.

For each screen:
- **URL pattern** — what navigates there
- **Roles** — who typically uses it
- **Source** — Vue page/component or PHP handler with `file:line`
- **Key controls** — what a test is likely to interact with

Do not treat this as an exhaustive DOM catalog. It's a map to the **source of truth** — selectors should be confirmed against the Vue component or by running the spec with `--ui`.

---

## A. Public / reader side

### A1. Journal homepage
- **URL:** `/index.php/publicknowledge/` (or just `/publicknowledge/`)
- **Roles:** Public
- **Handler:** `pages/index/IndexHandler.php`
- **Template:** rendered via the active theme (`plugins/themes/default/` by default)
- **Controls:** "Current Issue" link, "Submit" button, journal nav menu, announcements

### A2. Current / past issues
- **URL:** `/publicknowledge/issue/view/{issueId}` (published) or `/publicknowledge/issue/current`
- **Roles:** Public (readers; subscription gates apply in subscription-based journals)
- **Handler:** `pages/issue/IssueHandler.php`
- **Controls:** TOC by section, per-article galley links, issue cover

### A3. Article landing
- **URL:** `/publicknowledge/article/view/{submissionId}` — optional `/version/{publicationId}` for a specific version
- **Roles:** Public
- **Handler:** `pages/article/ArticleHandler.php`
- **Controls:** Abstract, author list, galley download buttons (PDF, HTML, XML), "How to Cite", metrics, open review attachments, user comments

### A4. Search / archive
- **URL:** `/publicknowledge/search` (search); `/publicknowledge/issue/archive` (issue archive)
- **Roles:** Public
- **Handler:** `pages/search/SearchHandler.php`
- **Controls:** Keyword input, section/date filters, result list

### A5. Register / begin submission
- **URL:** `/publicknowledge/user/register` (registration); dashboard → "New Submission" once logged in
- **Roles:** Unregistered → reader/author; logged-in users → author
- **Handler:** `lib/pkp/pages/user/UserHandler.php` (register), `pages/submission/` for the wizard
- **Controls:** Registration form, "Register as: Reviewer" checkbox, reCAPTCHA (if configured)

---

## B. Post-login dashboard

### B1. Dashboard / My Queue
- **URL:** `/dashboard` (post-login landing) or `/publicknowledge/dashboard/...`
- **Roles:** Author, editor, section editor, reviewer, assistant — everyone logged in
- **Handler:** `pages/dashboard/DashboardHandler.php` (journal-specific setup) + `lib/pkp/pages/dashboard/PKPDashboardHandler.php`
- **Vue page:** `lib/ui-library/src/pages/dashboard/DashboardPage.vue`
- **Store:** `lib/ui-library/src/pages/dashboard/dashboardPageStore.js`
- **Role-specific views:** "My Queue" (reviewer's assignments, editor's queue), "Unassigned", "All Submissions", "Archived"
- **Controls:** View dropdown (top-left), filter panel (sections, stages, categories, reviewer status), search box (by title/ID), column sort, pagination, per-row action menu, "New Submission" button
- **Playwright tip:** `page.getByRole('heading', {name: 'Dashboard'})` confirms the Vue app mounted (the `DashboardPage` POM at `lib/pkp/playwright/pages/DashboardPage.js:11` uses this).

---

## C. Submission wizard (author)

### C1. Start a submission
- **URL:** `/publicknowledge/submission` or triggered via dashboard "New Submission" button
- **Roles:** Author (any logged-in user, once granted author role in the context — happens on first submission)
- **Handler:** `lib/pkp/pages/submission/PKPSubmissionHandler.php`
- **Vue page:** `lib/ui-library/src/pages/submissionWizard/` (see `SubmissionWizardPage.vue` in the OMP counterpart for reference; OJS uses the same pattern)
- **OJS POM:** `playwright/pages/SubmissionWizardPage.js`

### C2. Wizard steps
`PKPSubmissionHandler::SECTION_TYPE_*` constants (search the handler file) define the step types:
- `SECTION_TYPE_FORM` — Title, abstract, language, keywords, reconfiguration (section, language)
- `SECTION_TYPE_CONTRIBUTORS` — Add/edit authors
- `SECTION_TYPE_FILES` — Upload manuscript + supplementary files
- `SECTION_TYPE_REVIEW` — Review the assembled submission
- `SECTION_TYPE_CONFIRM` — Final confirmation step

**Controls per step:** "Save and continue", "Back", step navigation (left sidebar). File uploads use a PKP upload widget — expect to interact with `<input type="file">` (hidden) via `setInputFiles`.

---

## D. Editorial workflow (per submission)

This is the hub once a submission is in-flight. A single URL drives a multi-stage UI.

### D1. Workflow page
- **URL:** `/publicknowledge/workflow/{submissionId}` (legacy: `/publicknowledge/workflow/index/{submissionId}/{stageId}`)
- **Roles:** Editor, section editor, manager, assistant (per-stage participants), reviewer (sees their own view — D4)
- **Handler:** `pages/workflow/WorkflowHandler.php` (OJS) + `lib/pkp/pages/workflow/PKPWorkflowHandler.php`
- **Vue page:** `lib/ui-library/src/pages/workflow/WorkflowPage.vue` → OJS-specific extension at `WorkflowPageOJS.vue`
- **Store:** `lib/ui-library/src/pages/workflow/workflowStore.js`
- **Side menu stages:** Submission → Review → Copyediting → Production → Publication

### D2. Submission stage
- First stop when the submission arrives. Editor decisions: send to review, accept, decline.
- **Controls:** Assign participants (author, editors), "Send to Review" / "Accept Submission" / "Decline Submission" buttons (in the right-rail control area, marked with `data-cy="workflow-controls-right"` — a legacy Cypress hook that still ships in the DOM).

### D3. Review stage (external review)
- Where reviewers get assigned and their reviews land.
- **Controls:** "Add Reviewer" modal, reviewer list with status (Requested, Accepted, Declined, Complete, Overdue), "Request Revisions" / "Accept Submission" / "Decline Submission" / "Resubmit for Review" decision buttons, round management ("New Review Round").
- **Components:** workflow primary modals at `lib/ui-library/src/pages/workflow/modals/`.

### D4. Reviewer-side view
- **URL:** `/publicknowledge/reviewer/submission/{submissionId}` (for the reviewer's own view)
- **Roles:** Reviewer
- **Handler:** `pages/reviewer/ReviewerHandler.php`
- **Vue page:** `lib/ui-library/src/pages/reviewerSubmission/ReviewerSubmissionPage.vue`
- **Controls:** Request steps (Request → Guidelines → Download & Review → Completion), accept/decline invitation, upload review attachment, review form fields, recommendation dropdown, "Submit Review" button.

### D5. Copyediting stage
- Side menu → "Copyediting" on the workflow page.
- **Roles:** Editor (assigns), copyeditor (does the work)
- **Controls:** Upload copyedited manuscript, participant list (add copyeditor), "Send to Production" decision.

### D6. Production stage
- **Roles:** Editor, layout editor, proofreader
- **Controls:** Upload galleys (PDF, HTML, XML), mark galleys ready, "Schedule for Publication" decision.

### D7. Publication tab
- Not a side-menu stage — it's a top-level tab on the workflow page (separate from the stage pipeline).
- **Controls:**
  - **Title & Abstract** — edit metadata per locale
  - **Contributors** — finalize author list and order
  - **Metadata** — keywords, subjects, languages, disciplines
  - **Citations** — imported references
  - **Issue** — assign to issue, set pages, section, categories, cover image, publication date
  - **JATS** — XML metadata (`components/publication/WorkflowPublicationJats.vue`)
  - **Identifiers** — DOI, URN
  - **Version control** — `components/publication/WorkflowPublicationVersionControl.vue`
  - **Permissions & Disclosure** — license
  - Top-right: "Schedule for Publication" / "Publish" button. Once published, "Unpublish" replaces it.

---

## E. Settings (manager / admin)

### E1. Journal settings
- **URL:** `/publicknowledge/management/settings/{page}` where `{page}` is one of `context`, `website`, `workflow`, `distribution`, `access`
- **Roles:** Manager, site admin
- **Handler:** `pages/management/SettingsHandler.php` (OJS) + `lib/pkp/pages/management/PKPToolsHandler.php` for tools
- **Pages (tabs within each):**
  - **Context:** journal name, abbreviation, ISSN, contact, masthead, editorial team, about, policies
  - **Website:** theme, setup (logo, header image), appearance, information, languages, plugins, navigation
  - **Workflow:** submission settings, sections, review setup, publisher library, emails, authorship/guidelines
  - **Distribution:** license, access, payment, archiving (PKP PN, LOCKSS), indexing (sitemap, DOI), permissions
- **Controls:** Form-per-tab, "Save" button at bottom of each form, image upload widgets.

### E2. Users & roles
- **URL:** `/publicknowledge/management/settings/access` (users tab within Access settings)
- **Roles:** Manager, site admin
- **Controls:** User list panel, "Add User" modal, edit modal (enroll in role, set journal, disable), search/filter.
- **Backend:** user list component driven by API `/api/v1/users`.

### E3. Statistics
- **URL:** `/publicknowledge/stats/{view}` where `{view}` is `publications`, `issues`, `context`, `users`, `editorial`
- **Roles:** Manager, editor, section editor (limited)
- **Handler:** `pages/stats/StatsHandler.php`
- **Vue pages:** `lib/ui-library/src/pages/stats{Context,Issues,Publications,Users}/`
- **Controls:** Date range picker (defaults to last 30 days), section/issue filters, download report (COUNTER R5).

### E4. Tools / Import-Export
- **URL:** `/publicknowledge/management/tools`
- **Roles:** Manager, site admin
- **Controls:** Import/export plugin list (Users, Native XML, CrossRef, DOAJ, etc.), "Statistics" report generator, scheduled tasks.

---

## F. Issues management (OJS-only)

### F1. Manage issues
- **URL:** `/publicknowledge/manageIssues`
- **Roles:** Manager, editor
- **Handler:** `pages/manageIssues/ManageIssuesHandler.php`
- **Controls:** Tabbed list (Future, Back, Current), "Create Issue" button, per-issue edit modal, publish/unpublish, delete, TOC reorder.
- **Edit modal tabs:** Issue Data, Access, Identifiers, Issue Galleys, Table of Contents, Cover.

---

## G. User profile / account

### G1. User profile
- **URL:** `/publicknowledge/user/profile` (defaults to "Identity" tab); `/publicknowledge/user/profile/{tab}` for specific tabs
- **Roles:** All logged-in users
- **Handler:** `pages/user/UserHandler.php` (OJS extension), `lib/pkp/pages/user/PKPUserHandler.php`
- **Tabs:** Identity (name, ORCID), Contact, Roles (read-only), Public (bio, image), Password, Notifications, API Key, Subscriptions (OJS-only if subscriptions enabled).

### G2. Accept reviewer invitation / new-user invitation
- **URL:** `/accept-invitation/{token}` (token in email)
- **Roles:** Invited user (may or may not have an account yet)
- **Vue page:** `lib/ui-library/src/pages/acceptInvitation/AcceptInvitationPage.vue`
- **Controls:** Multi-step wizard — verify email, accept terms, complete account (if new), confirm.

---

## H. Site admin (multi-journal)

- **URL:** `/admin/` (and subpaths: `/admin/contexts` for journal list, `/admin/settings` for site-wide)
- **Roles:** Site admin only
- **Handler:** `lib/pkp/pages/admin/PKPAdminHandler.php`
- **Controls:** Journal list (create/edit/delete), site settings (languages, plugins, appearance), system information, version check, site-wide users.

---

## If the map is missing a screen

Some things aren't listed here — especially rarely-tested screens (announcements, submission files download URLs, galley preview, subscription individual/institutional forms).

To find them:
1. **Vue page:** grep `lib/ui-library/src/pages/` for a likely directory name, then open the top-level `*.vue` file.
2. **PHP handler:** `pages/<area>/<Name>Handler.php` (OJS-specific) or `lib/pkp/pages/<area>/` (shared).
3. **Route:** search `lib/pkp/classes/core/PKPRouter.php` or grep for a URL segment in `pages/*/index.php` (each routes its handler).
4. **Smarty template:** `templates/` (OJS-specific), `lib/pkp/templates/` (shared) — older, pre-Vue pages live here.
