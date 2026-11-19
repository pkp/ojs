// @ts-check
/**
 * @file playwright/tests/reviewer-assignment.spec.js
 *
 * Reviewer assignment & management — OJS suite, one test per canonical
 * scenario the spec runs on OJS (common scenarios 1–12 + OJS-specific 14;
 * scenario 13 is OMP-only, 15 OPS-only — they live in those repos).
 * Spec: lib/pkp/docs/product/specs/reviewer-assignment-and-management.md
 *
 * Deliberately NOT covered (register IDs from the spec's Findings register —
 * a 🐞 is never asserted as contract, a ❓ is parked, not a gap):
 * - A1 🐞: no ORCID surface — attaching a verified iD needs the external
 *   OAuth flow no screen here provides.
 * - A2 🐞: S12 asserts the resent row's status title only; the second line's
 *   date is the bug's record, asserted neither way.
 * - A7 🐞: no test asserts the "Request Sent" row's second line either way.
 * - A8 🐞: S5 walks the refusal (window stays open, no row) per the spec's
 *   scenario; the absence of an error message is not asserted as correct.
 * - A9 🐞: S12 submits the Resend window's preset dates without asserting
 *   their values.
 * - A10 🐞: S9 asserts the revert transitions the spec's Rule 16 names; that
 *   viewing never produces "Review Viewed" is not asserted either way.
 * - A11/A12 🐞: S6 asserts the change notice arrives; its body (the stale
 *   deadlines) and its unsubscribe page are the bugs' record.
 * - A13/A14/A16 🐞: Email Reviewer body enforcement, the enroll form's false
 *   required message, and typed-date discarding are not exercised (all date
 *   input goes through the calendar, the screen's working path).
 * - A15/A17 ❓ + A3/A4/A5/A6 ❓: parked pending product rulings (no
 *   assistant-table, editorial-notes, site-admin-add or past-date-warning
 *   assertions; S7 uses the Edit window's past-date route as the spec's own
 *   overdue recipe, asserting nothing about warnings).
 * - Automatic reminders (scheduled task; serial-scope, settings-owned
 *   clocks) and reviewer one-click access (settings modifier) have no
 *   canonical scenario here.
 * - S10 note: the two PDFs are asserted as real downloads; the author-only /
 *   full content split is asserted on the same menu's XML exports (mpdf
 *   compresses PDF text streams — the split is byte-identical logic).
 *
 * Seeding: scenario endpoints only; publicknowledge and the 18 seeded users
 * are read-only. Tests that mutate roles/accounts (S2–S4), need a private
 * toast queue, a bounded task list or a throwaway mailbox (S6, S7, S9, S11)
 * run on scratch journals with throwaway users whose addresses carry app +
 * test in the username (u27s7ojsw0…@mail.test). Mail assertions on seeded
 * reviewers are scoped by recipient + the scratch submission's unique
 * tag-bearing title. Silence claims are bounded (pkpMail.count after a
 * bounding find; list absences bounded by the row/response that carries
 * them). No hard-coded waits.
 */
const fs = require('fs');
const {test, expect} = require('../support/fixtures.js');
const {
    WorkflowPage,
    performReview,
    legacyModal,
    pickDate,
    openAddReviewerModal,
    searchReviewerList,
    selectReviewer,
    waitForJQueryIdle,
} = require('../pages/ReviewStagePages.js');
const {LoginPage} = require('../../lib/pkp/playwright/pages/LoginPage.js');

const JOURNAL = 'publicknowledge';

/** Unique per-run tag: single alphanumeric token, app + scenario + worker. */
function makeTag(scenario, testInfo) {
    return `u27${scenario}ojsw${testInfo.parallelIndex}${Math.random().toString(36).slice(2, 8)}`;
}

/** Local date as the app's short format (Y-m-d). */
function ymd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function daysFromNow(n) {
    const d = new Date();
    d.setDate(d.getDate() + n);
    return d;
}

/** Seed a submission standing in external review round 1. */
async function seedInReview(ojsApi, tag, {
    context = JOURNAL,
    submitter = 'author.alex',
    reviewers = [],
} = {}) {
    return ojsApi.createSubmission({
        tag,
        context,
        submitter,
        title: `Submission ${tag}`,
        decisions: ['sendExternalReview'],
        reviewRounds: [{reviewers}],
    });
}

/**
 * Open a reviewer row's "More Actions" menu and click one entry by its exact
 * accessible name (exact — "Edit" must not match "Editorial Notes"; the
 * headlessui menu portals to the document root, so items resolve page-wide).
 */
async function clickRowAction(page, row, name) {
    await row.getByRole('button', {name: 'More Actions'}).click();
    await page.getByRole('menuitem', {name, exact: true}).click();
}

test.describe('reviewer-assignment', () => {
    test('S1: invite a reviewer', {tag: '@smoke'}, async ({asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s1', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag);

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);

        // Search the pool and select the seeded reviewer.
        const modal = await openAddReviewerModal(editorPage);
        await selectReviewer(editorPage, modal, 'Julia Reviewer');

        // The request letter arrives prefilled from the request template.
        await expect(
            editorPage
                .frameLocator('iframe[id^="personalMessage"]')
                .locator('body')
        ).toContainText('you would serve as an excellent reviewer', {timeout: 30_000});

        // The two due dates default per the journal's review setup (the
        // datepicker's hidden altField carries the submitted Y-m-d value).
        await expect(
            modal.locator('input[id^="responseDueDate"][id$="-altField"]')
        ).toHaveValue(ymd(daysFromNow(4 * 7)));
        await expect(
            modal.locator('input[id^="reviewDueDate"][id$="-altField"]')
        ).toHaveValue(ymd(daysFromNow(4 * 7)));

        await modal.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
        await expect(modal).toHaveCount(0, {timeout: 30_000});
        await waitForJQueryIdle(editorPage);

        // The panel lists the reviewer as "Request Sent" (the missing
        // response-deadline second line is register A7 — asserted neither way).
        await editorPage.reload();
        await workflow.expectOpen();
        const row = workflow.panelRow('Reviewers', 'Julia Reviewer');
        await expect(row).toBeVisible();
        await expect(row).toContainText('Request Sent');

        // The reviewer's mailbox holds the request email (scoped by recipient
        // + the tag-bearing submission title).
        await pkpMail.find({
            to: 'reviewer.julia@mail.test',
            subject: 'Invitation to review',
            contains: tag,
        });
    });

    test('S2: the list warns before anonymity breaks', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s2', testInfo);
        const manager = `mgr${tag}`;
        const author = `au${tag}`;
        const lockedName = `Lock${tag}`;
        const assignedName = `reva${tag}`;
        // Scratch journal: the locked entry needs a reviewer who also holds a
        // manager role — never a mutation of the shared roster.
        await ojsApi.createContext({
            tag,
            users: [
                {username: manager, roles: ['manager']},
                {username: author, roles: ['author']},
                {username: `lock${tag}`, givenName: lockedName, roles: ['externalReviewer', 'manager']},
                {username: assignedName, roles: ['externalReviewer']},
            ],
        });
        const {submissionId} = await seedInReview(ojsApi, tag, {
            context: tag,
            submitter: author,
            reviewers: [{username: assignedName, status: 'invited'}],
        });

        const managerPage = await (await asUser(manager)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        const modal = await openAddReviewerModal(managerPage);

        // The manager-reviewer is locked with the author-identity warning and
        // no Select button; "Unlock" frees it.
        const lockedItem = await searchReviewerList(managerPage, modal, lockedName);
        await expect(lockedItem).toContainText(
            'This reviewer is locked because they have been assigned a role which allows them to view the author\'s identity.'
        );
        await expect(lockedItem.getByText(`Select ${lockedName}`)).toHaveCount(0);
        await lockedItem.getByRole('button', {name: 'Unlock'}).click();
        await expect(lockedItem.getByText(`Select ${lockedName}`)).toBeVisible();

        // A reviewer already on the round cannot be selected again.
        const assignedItem = await searchReviewerList(managerPage, modal, assignedName);
        await expect(assignedItem).toContainText(
            'This reviewer has already been assigned to this review round.'
        );
        await expect(assignedItem.getByText(`Select ${assignedName}`)).toHaveCount(0);
    });

    test('S3: create a brand-new reviewer', async ({asUser, browser, baseURL, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        test.setTimeout(240_000);
        const tag = makeTag('s3', testInfo);
        const manager = `mgr${tag}`;
        const author = `au${tag}`;
        const givenName = `Rev${tag}x`;
        const expectedUsername = givenName.toLowerCase();
        const reviewerEmail = `${expectedUsername}@mail.test`;
        // Scratch journal: the minted account and its reviewer enrolment are
        // journal-level mutations, and the welcome mail needs a throwaway box.
        await ojsApi.createContext({
            tag,
            users: [
                {username: manager, roles: ['manager']},
                {username: author, roles: ['author']},
            ],
        });
        const {submissionId} = await seedInReview(ojsApi, tag, {context: tag, submitter: author});

        const managerPage = await (await asUser(manager)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        const modal = await openAddReviewerModal(managerPage);
        await modal.getByRole('link', {name: 'Create New Reviewer'}).click();

        const createModal = managerPage
            .getByRole('dialog')
            .filter({has: managerPage.locator('form#createReviewerForm')});
        const form = createModal.locator('form#createReviewerForm');
        await expect(form.locator('input[name="username"]')).toBeVisible({timeout: 30_000});
        await form.locator('input[name="givenName[en]"]').fill(givenName);
        await form.locator('input[name="email"]').fill(reviewerEmail);

        // "Suggest" fills a lowercase proposal from the given name.
        await form.getByRole('button', {name: 'Suggest'}).click();
        await expect(form.locator('input[name="username"]')).toHaveValue(expectedUsername, {
            timeout: 30_000,
        });

        await form.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
        await expect(createModal).toHaveCount(0, {timeout: 30_000});
        await waitForJQueryIdle(managerPage);

        // The row appears as "Request Sent".
        await managerPage.reload();
        await workflow.expectOpen();
        const row = workflow.panelRow('Reviewers', givenName);
        await expect(row).toBeVisible();
        await expect(row).toContainText('Request Sent');

        // The new address's mailbox holds the registration email (with the
        // generated password) and the review request.
        const registration = await pkpMail.find({
            to: reviewerEmail,
            subject: 'Registration as Reviewer',
        });
        await pkpMail.find({to: reviewerEmail, subject: 'Invitation to review'});
        const full = await pkpMail.fullMessage(registration.ID);
        const credentials = (full.HTML || '').match(
            /Username:\s*([^<\s]+)\s*<br\s*\/?>\s*Password:\s*([^<\s]+)/i
        );
        expect(credentials, 'emailed username and password').toBeTruthy();
        expect(credentials?.[1]).toBe(expectedUsername);

        // Signing in with the emailed password lands on "Change Password".
        const freshCtx = await browser.newContext({
            baseURL,
            storageState: {cookies: [], origins: []},
        });
        try {
            const loginPage = new LoginPage(await freshCtx.newPage());
            await loginPage.page.goto(`/index.php/${tag}/login`);
            await loginPage.usernameInput.fill(expectedUsername);
            await loginPage.fillPassword(credentials?.[2] || '');
            await loginPage.submitButton.click();
            await loginPage.page.waitForURL(/\/login\/changePassword/, {
                waitUntil: 'commit',
                timeout: 15_000,
            });
            await expect(
                loginPage.page.getByRole('heading', {name: 'Change Password'})
            ).toBeVisible();
        } finally {
            await freshCtx.close();
        }
    });

    test('S4: enroll an existing user', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s4', testInfo);
        const manager = `mgr${tag}`;
        const author = `au${tag}`;
        const enrollee = `enr${tag}`;
        const existingReviewer = `rev${tag}`;
        // Scratch journal: enrolling grants a permanent role — a mutation the
        // shared roster must never see.
        await ojsApi.createContext({
            tag,
            users: [
                {username: manager, roles: ['manager']},
                {username: author, roles: ['author']},
                {username: enrollee, roles: ['author']},
                {username: existingReviewer, roles: ['externalReviewer']},
            ],
        });
        const {submissionId} = await seedInReview(ojsApi, tag, {context: tag, submitter: author});

        const managerPage = await (await asUser(manager)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        const modal = await openAddReviewerModal(managerPage);
        await modal.getByRole('link', {name: 'Enroll Existing User'}).click();

        const enrollModal = managerPage
            .getByRole('dialog')
            .filter({has: managerPage.locator('form#enrollExistingReviewerForm')});
        const form = enrollModal.locator('form#enrollExistingReviewerForm');
        await expect(
            form.getByRole('heading', {name: 'Enroll an Existing User as Reviewer'})
        ).toBeVisible({timeout: 30_000});
        // The reviewer role select renders even with a single group.
        await expect(form.locator('select[name="userGroupId"]')).toBeVisible();

        // The autocomplete offers journal members without any reviewer role.
        const nameInput = form.locator('input[id^="userId_input"]');
        await nameInput.pressSequentially(enrollee, {delay: 30});
        const menu = managerPage.locator('ul.ui-autocomplete').filter({visible: true});
        const match = menu.locator('li').filter({hasText: enrollee});
        await expect(match).toBeVisible({timeout: 30_000});
        await match.click();

        await form.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
        await expect(enrollModal).toHaveCount(0, {timeout: 30_000});
        await waitForJQueryIdle(managerPage);

        // The row appears.
        await managerPage.reload();
        await workflow.expectOpen();
        await expect(workflow.panelRow('Reviewers', enrollee)).toBeVisible();

        // The journal's users list shows the user now also holds Reviewer.
        await managerPage.goto(`/index.php/${tag}/management/settings/access`);
        await expect(managerPage.getByRole('heading', {name: 'Users & Roles'})).toBeVisible({
            timeout: 30_000,
        });
        const userRow = managerPage
            .getByRole('table', {name: /Current Users/})
            .getByRole('row')
            .filter({hasText: `${enrollee}@mail.test`});
        await expect(userRow).toBeVisible({timeout: 30_000});
        await expect(userRow).toContainText('Reviewer');

        // Control: an existing reviewer's name finds nothing in the same
        // autocomplete (bounded by the widget's own "No Matches" entry).
        await workflow.gotoEditorial(submissionId);
        const modal2 = await openAddReviewerModal(managerPage);
        await modal2.getByRole('link', {name: 'Enroll Existing User'}).click();
        const form2 = managerPage.locator('form#enrollExistingReviewerForm');
        await expect(form2.locator('input[id^="userId_input"]')).toBeVisible({timeout: 30_000});
        await form2.locator('input[id^="userId_input"]').pressSequentially(existingReviewer, {delay: 30});
        const menu2 = managerPage.locator('ul.ui-autocomplete').filter({visible: true});
        await expect(menu2.getByText('No Matches')).toBeVisible({timeout: 30_000});
        await expect(menu2.locator('li').filter({hasText: existingReviewer})).toHaveCount(0);
    });

    test('S5: deadlines are validated', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s5', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag);

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        const modal = await openAddReviewerModal(editorPage);
        await selectReviewer(editorPage, modal, 'Julia Reviewer');

        // The permanent guidance sentence states the rule.
        await expect(modal.getByText('Review due date must be greater or equal to response due date.')).toBeVisible();

        // Review due date before the response due date: submitting adds
        // nothing — the window stays open (the missing error message is
        // register A8, asserted neither way).
        await pickDate(editorPage, modal, 'reviewDueDate', daysFromNow(7));
        await modal.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
        await waitForJQueryIdle(editorPage);
        await expect(modal.locator('input[id^="responseDueDate"]').first()).toBeVisible();
        await expect(workflow.panelRow('Reviewers', 'Julia Reviewer')).toHaveCount(0);

        // Correcting the dates succeeds and the row appears.
        await pickDate(editorPage, modal, 'reviewDueDate', daysFromNow(35));
        await modal.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
        await expect(modal).toHaveCount(0, {timeout: 30_000});
        await waitForJQueryIdle(editorPage);
        await editorPage.reload();
        await workflow.expectOpen();
        // Exactly one row: the refused submit created nothing (a leftover
        // assignment would have made this second add a refused duplicate).
        await expect(workflow.panelRow('Reviewers', 'Julia Reviewer')).toHaveCount(1);
        await expect(workflow.panelRow('Reviewers', 'Julia Reviewer')).toContainText('Request Sent');
    });

    test('S6: edit an assignment, reviewer is told', async ({asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        test.setTimeout(240_000);
        const tag = makeTag('s6', testInfo);
        const manager = `mgr${tag}`;
        const author = `au${tag}`;
        const reviewer = `rev${tag}`;
        // Scratch journal: the change notice needs a throwaway mailbox and
        // the task assertion a bounded task list.
        await ojsApi.createContext({
            tag,
            users: [
                {username: manager, roles: ['manager']},
                {username: author, roles: ['author']},
                {username: reviewer, roles: ['externalReviewer']},
            ],
        });
        const {submissionId} = await seedInReview(ojsApi, tag, {
            context: tag,
            submitter: author,
            reviewers: [{username: reviewer, status: 'invited'}],
        });

        const managerPage = await (await asUser(manager)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        const row = workflow.panelRow('Reviewers', reviewer);
        await expect(row).toBeVisible();

        // Control first: an edit that changes neither dates nor type (here
        // the visibility box) sends nothing — proven by the count below.
        await clickRowAction(managerPage, row, 'Edit');
        const editModal = legacyModal(managerPage, 'editReviewForm');
        const visibilityBox = editModal.locator('input[name="isReviewPubliclyVisible"]');
        await expect(visibilityBox).toBeVisible({timeout: 30_000});
        await visibilityBox.setChecked(!(await visibilityBox.isChecked()));
        await editModal.getByRole('button', {name: 'OK', exact: true}).click();
        await expect(editModal.locator('form#editReviewForm')).toBeHidden({timeout: 30_000});
        await waitForJQueryIdle(managerPage);

        // The real change: move the review due date a week later (seeded at
        // +4 weeks, the form's own default; calendar pick — the widget
        // discards typed dates, A16).
        await managerPage.reload();
        await workflow.expectOpen();
        await clickRowAction(managerPage, row, 'Edit');
        const editModal2 = legacyModal(managerPage, 'editReviewForm');
        await expect(editModal2.locator('input[name="isReviewPubliclyVisible"]')).toBeVisible({
            timeout: 30_000,
        });
        await pickDate(managerPage, editModal2, 'reviewDueDate', daysFromNow(5 * 7));
        await editModal2.getByRole('button', {name: 'OK', exact: true}).click();
        await expect(editModal2.locator('form#editReviewForm')).toBeHidden({timeout: 30_000});
        await waitForJQueryIdle(managerPage);

        // The reviewer's mailbox holds the change notice — and only one:
        // the visibility-only edit sent nothing (count bounded by the find).
        await pkpMail.find({
            to: `${reviewer}@mail.test`,
            subject: 'Your review assignment has been changed',
        });
        expect(
            await pkpMail.count({
                to: `${reviewer}@mail.test`,
                subject: 'Your review assignment has been changed',
            })
        ).toBe(1);

        // Signing in as the reviewer shows the "Review assignment updated."
        // task in their task list.
        const reviewerPage = await (await asUser(reviewer)).newPage();
        await reviewerPage.goto(`/index.php/${tag}/dashboard/reviewAssignments`);
        await reviewerPage.getByRole('button', {name: 'Tasks'}).click();
        const tasksDialog = reviewerPage
            .getByRole('dialog')
            .filter({hasText: 'Tasks'});
        await expect(tasksDialog.getByText('Review assignment updated.').first()).toBeVisible({
            timeout: 30_000,
        });
    });

    test('S7: remind an overdue reviewer', async ({asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        test.setTimeout(240_000);
        const tag = makeTag('s7', testInfo);
        const manager = `mgr${tag}`;
        const author = `au${tag}`;
        const overdueReviewer = `rev${tag}`;
        const onTimeReviewer = `revb${tag}`;
        // Scratch journal: private toast queue + throwaway reminder mailbox.
        await ojsApi.createContext({
            tag,
            users: [
                {username: manager, roles: ['manager']},
                {username: author, roles: ['author']},
                {username: overdueReviewer, roles: ['externalReviewer']},
                {username: onTimeReviewer, roles: ['externalReviewer']},
            ],
        });
        const {submissionId} = await seedInReview(ojsApi, tag, {
            context: tag,
            submitter: author,
            reviewers: [
                {username: overdueReviewer, status: 'invited'},
                {username: onTimeReviewer, status: 'invited'},
            ],
        });

        const managerPage = await (await asUser(manager)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        const row = workflow.panelRow('Reviewers', overdueReviewer);
        await expect(row).toBeVisible();

        // Overdue recipe (spec footnote s): backdate the response due date
        // through the Edit window — the screen's own route to a passed date.
        await clickRowAction(managerPage, row, 'Edit');
        const editModal = legacyModal(managerPage, 'editReviewForm');
        await expect(editModal.locator('input[name="isReviewPubliclyVisible"]')).toBeVisible({
            timeout: 30_000,
        });
        await pickDate(managerPage, editModal, 'responseDueDate', daysFromNow(-1));
        await editModal.getByRole('button', {name: 'OK', exact: true}).click();
        await expect(editModal.locator('form#editReviewForm')).toBeHidden({timeout: 30_000});
        await waitForJQueryIdle(managerPage);

        await managerPage.reload();
        await workflow.expectOpen();
        await expect(row).toContainText('Overdue');

        // The overdue row's button reads "Send Reminder"; the window shows
        // the "Review Schedule" readout.
        await row.getByRole('button', {name: 'Send Reminder', exact: true}).click();
        const reminderModal = legacyModal(managerPage, 'sendReminderForm');
        await expect(reminderModal.getByText('Review Schedule')).toBeVisible({timeout: 30_000});
        await expect(reminderModal.getByText("Editor's Request")).toBeVisible();
        await expect(reminderModal.getByText('Response Due Date')).toBeVisible();
        await expect(reminderModal.getByText('Review Due Date')).toBeVisible();
        await reminderModal
            .locator('form#sendReminderForm')
            .getByRole('button', {name: 'Send Reminder', exact: true})
            .click();

        // Notice, reminder email, and the History "Reminder" milestone
        // (checked before any reviewer response — its survival is A15).
        await expect(managerPage.getByText('Notification sent.')).toBeVisible({timeout: 30_000});
        await expect(reminderModal.locator('form#sendReminderForm')).toBeHidden({timeout: 30_000});
        await waitForJQueryIdle(managerPage);
        await pkpMail.find({
            to: `${overdueReviewer}@mail.test`,
            subject: 'A reminder to please complete your review',
        });

        await clickRowAction(managerPage, row, 'History');
        const historyModal = managerPage
            .getByRole('dialog')
            .filter({has: managerPage.locator('.pkp_review_history')});
        await expect(historyModal.getByText('Assigned')).toBeVisible({timeout: 30_000});
        await expect(historyModal.getByText('Reminder')).toBeVisible();
        await historyModal.getByRole('button', {name: 'Close'}).click();

        // Control: a row that is not overdue offers no "Send Reminder".
        const controlRow = workflow.panelRow('Reviewers', onTimeReviewer);
        await expect(controlRow).toBeVisible();
        await expect(controlRow.getByRole('button', {name: 'More Actions'})).toBeVisible();
        await expect(controlRow.getByRole('button', {name: 'Send Reminder', exact: true})).toHaveCount(0);
    });

    test('S8: log a response on the reviewer\'s behalf', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s8', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag, {
            reviewers: [{username: 'reviewer.julia', status: 'invited'}],
        });

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        const row = workflow.panelRow('Reviewers', 'Julia Reviewer');
        await expect(row).toBeVisible();

        await clickRowAction(editorPage, row, 'Log Response');
        const logModal = editorPage
            .getByRole('dialog')
            .filter({hasText: 'Record the response on behalf of the reviewer'});
        await expect(logModal).toBeVisible({timeout: 30_000});
        await logModal
            .getByRole('radio', {name: 'Reviewer has accepted the invitation to review'})
            .check();
        await logModal.getByRole('button', {name: 'Log Response', exact: true}).click();
        await expect(logModal).toBeHidden({timeout: 30_000});

        // The row reads "Request Accepted" with the review due date.
        await editorPage.reload();
        await workflow.expectOpen();
        await expect(row).toContainText('Request Accepted');
        // Seeded review due = today + the journal's review weeks (4 on the
        // baseline journal) — the Add Reviewer form's own arithmetic.
        await expect(row).toContainText(`Review due: ${ymd(daysFromNow(4 * 7))}`);

        // Control: "Log Response" is gone from the menu (bounded by the
        // always-offered "Email Reviewer" in the same menu).
        await row.getByRole('button', {name: 'More Actions'}).click();
        await expect(
            editorPage.getByRole('menuitem', {name: 'Email Reviewer'})
        ).toBeVisible();
        await expect(editorPage.getByRole('menuitem', {name: 'Log Response'})).toHaveCount(0);
    });

    test('S9: read, rate, confirm, thank — and take it back', async ({asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        test.setTimeout(300_000);
        const tag = makeTag('s9', testInfo);
        const manager = `mgr${tag}`;
        const author = `au${tag}`;
        const reviewer = `rev${tag}`;
        // Scratch journal: private toast queue + throwaway thank-you mailbox.
        await ojsApi.createContext({
            tag,
            users: [
                {username: manager, roles: ['manager']},
                {username: author, roles: ['author']},
                {username: reviewer, roles: ['externalReviewer']},
            ],
        });
        const {submissionId} = await seedInReview(ojsApi, tag, {
            context: tag,
            submitter: author,
            reviewers: [{username: reviewer, status: 'accepted'}],
        });

        // The reviewer submits a review with both comment blocks.
        const reviewerPage = await (await asUser(reviewer)).newPage();
        await performReview(reviewerPage, tag, submissionId, {
            comments: `Shared comment ${tag}`,
            privateComments: `Private remark ${tag}`,
        });

        const managerPage = await (await asUser(manager)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        const row = workflow.panelRow('Reviewers', reviewer);
        await expect(row).toContainText('Review Submitted');

        // Read Review: the comments split into their two headed blocks.
        await row.getByRole('button', {name: 'Read Review', exact: true}).click();
        const readModal = legacyModal(managerPage, 'readReviewForm');
        await expect(readModal.getByText('For author and editor')).toBeVisible({timeout: 30_000});
        await expect(readModal.getByText(`Shared comment ${tag}`)).toBeVisible();
        await expect(readModal.getByRole('heading', {name: 'For editor', exact: true})).toBeVisible();
        await expect(readModal.getByText(`Private remark ${tag}`)).toBeVisible();

        // Pick a star rating and confirm — the row turns "Complete".
        await readModal.locator('input[name="quality"][value="5"]').check();
        await readModal.getByRole('button', {name: 'Confirm', exact: true}).click();
        await expect(readModal.locator('form#readReviewForm')).toBeHidden({timeout: 30_000});
        await waitForJQueryIdle(managerPage);
        await managerPage.reload();
        await workflow.expectOpen();
        await expect(row).toContainText('Complete');

        // Thank the reviewer.
        await row.getByRole('button', {name: 'Thank Reviewer', exact: true}).click();
        const thankModal = legacyModal(managerPage, 'sendThankYouForm');
        await expect(thankModal.locator('form#sendThankYouForm')).toBeVisible({timeout: 30_000});
        await thankModal
            .locator('form#sendThankYouForm')
            .getByRole('button', {name: 'Thank Reviewer', exact: true})
            .click();
        await expect(managerPage.getByText('Thank you email sent to reviewer.')).toBeVisible({
            timeout: 30_000,
        });
        await expect(thankModal.locator('form#sendThankYouForm')).toBeHidden({timeout: 30_000});
        await waitForJQueryIdle(managerPage);
        await managerPage.reload();
        await workflow.expectOpen();
        await expect(row).toContainText('Reviewer Thanked');
        await pkpMail.find({to: `${reviewer}@mail.test`, subject: 'Thank you for your review'});

        // Revert Decision → "Unconsider this Review" → "Review Viewed".
        await row.getByRole('button', {name: 'Revert Decision', exact: true}).click();
        const revertDialog = managerPage
            .getByRole('dialog')
            .filter({hasText: 'Unconsider this Review'});
        await expect(revertDialog).toBeVisible({timeout: 30_000});
        await revertDialog.getByRole('button', {name: 'OK', exact: true}).click();
        await managerPage.reload();
        await workflow.expectOpen();
        await expect(row).toContainText('Review Viewed');
    });

    test('S10: download the review', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        test.setTimeout(300_000);
        const tag = makeTag('s10', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag, {
            reviewers: [{username: 'reviewer.paul', status: 'accepted'}],
        });

        const reviewerPage = await (await asUser('reviewer.paul')).newPage();
        await performReview(reviewerPage, JOURNAL, submissionId, {
            comments: `Shared comment ${tag}`,
            privateComments: `Private remark ${tag}`,
        });

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        const row = workflow.panelRow('Reviewers', 'Paul Reviewer');
        await row.getByRole('button', {name: 'Read Review', exact: true}).click();
        const readModal = legacyModal(editorPage, 'readReviewForm');
        await expect(readModal.getByText('For author and editor')).toBeVisible({timeout: 30_000});

        /** Open the "Download Review Form" menu and download one export. */
        async function downloadExport(label) {
            const item = editorPage.getByRole('menuitem', {name: label});
            if (!(await item.isVisible())) {
                await readModal.getByRole('button', {name: 'Download Review Form'}).click();
                await expect(item).toBeVisible({timeout: 30_000});
            }
            const [download] = await Promise.all([
                editorPage.waitForEvent('download'),
                item.click(),
            ]);
            return download;
        }

        // Both PDFs download through the browser.
        for (const label of [
            'Author-Only Sections Displayed (PDF)',
            'Editor Form Shows All Review Sections (PDF)',
        ]) {
            const download = await downloadExport(label);
            expect(download.suggestedFilename()).toMatch(/\.pdf$/i);
            const filePath = await download.path();
            expect(fs.statSync(filePath).size).toBeGreaterThan(0);
        }

        // The author-only / full split, asserted on the same menu's XML
        // exports (mpdf compresses the PDFs' text streams; see header note):
        // author-only omits the editor-only remarks and anonymizes the
        // reviewer, the full export carries both blocks and the name.
        const authorXml = fs.readFileSync(
            await (await downloadExport('Author-Only Sections Displayed (XML)')).path(),
            'utf8'
        );
        expect(authorXml).toContain('<anonymous');
        expect(authorXml).not.toContain('Paul Reviewer');
        expect(authorXml).toContain(`Shared comment ${tag}`);
        expect(authorXml).not.toContain(`Private remark ${tag}`);

        const editorXml = fs.readFileSync(
            await (await downloadExport('Editor Form Shows All Review Sections (XML)')).path(),
            'utf8'
        );
        expect(editorXml).toContain('Paul Reviewer');
        expect(editorXml).toContain(`Shared comment ${tag}`);
        expect(editorXml).toContain(`Private remark ${tag}`);
    });

    test('S11: unassign before, cancel after, reinstate', async ({asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        test.setTimeout(240_000);
        const tag = makeTag('s11', testInfo);
        const manager = `mgr${tag}`;
        const author = `au${tag}`;
        const unanswered = `rev${tag}`;
        const accepted = `revb${tag}`;
        // Scratch journal: private toast queue + throwaway notice mailboxes.
        await ojsApi.createContext({
            tag,
            users: [
                {username: manager, roles: ['manager']},
                {username: author, roles: ['author']},
                {username: unanswered, roles: ['externalReviewer']},
                {username: accepted, roles: ['externalReviewer']},
            ],
        });
        const {submissionId} = await seedInReview(ojsApi, tag, {
            context: tag,
            submitter: author,
            reviewers: [
                {username: unanswered, status: 'invited'},
                {username: accepted, status: 'accepted'},
            ],
        });

        const managerPage = await (await asUser(manager)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        const unansweredRow = workflow.panelRow('Reviewers', unanswered);
        const acceptedRow = workflow.panelRow('Reviewers', accepted);
        await expect(unansweredRow).toBeVisible();
        await expect(acceptedRow).toBeVisible();

        // Before a response the entry reads "Unassign Reviewer"; removing
        // deletes the row outright.
        await clickRowAction(managerPage, unansweredRow, 'Unassign Reviewer');
        const unassignModal = legacyModal(managerPage, 'unassignReviewerForm');
        await unassignModal
            .locator('form#unassignReviewerForm')
            .getByRole('button', {name: 'Unassign Reviewer', exact: true})
            .click();
        await expect(managerPage.getByText('Reviewer removed.')).toBeVisible({timeout: 30_000});
        await expect(unassignModal.locator('form#unassignReviewerForm')).toBeHidden({
            timeout: 30_000,
        });
        await waitForJQueryIdle(managerPage);
        await managerPage.reload();
        await workflow.expectOpen();
        await expect(acceptedRow).toBeVisible();
        await expect(unansweredRow).toHaveCount(0);

        // After a response the same entry reads "Cancel Reviewer"; the row
        // stays as "Request Cancelled".
        await clickRowAction(managerPage, acceptedRow, 'Cancel Reviewer');
        const cancelModal = legacyModal(managerPage, 'unassignReviewerForm');
        await cancelModal
            .locator('form#unassignReviewerForm')
            .getByRole('button', {name: 'Cancel Reviewer', exact: true})
            .click();
        await expect(cancelModal.locator('form#unassignReviewerForm')).toBeHidden({
            timeout: 30_000,
        });
        await waitForJQueryIdle(managerPage);
        await managerPage.reload();
        await workflow.expectOpen();
        await expect(acceptedRow).toContainText('Request Cancelled');

        // Reinstate returns the row to the state its dates imply.
        await clickRowAction(managerPage, acceptedRow, 'Reinstate Reviewer');
        const reinstateModal = legacyModal(managerPage, 'reinstateReviewerForm');
        await reinstateModal
            .locator('form#reinstateReviewerForm')
            .getByRole('button', {name: 'Reinstate Reviewer', exact: true})
            .click();
        await expect(reinstateModal.locator('form#reinstateReviewerForm')).toBeHidden({
            timeout: 30_000,
        });
        await waitForJQueryIdle(managerPage);
        await managerPage.reload();
        await workflow.expectOpen();
        await expect(acceptedRow).toContainText('Request Accepted');

        // The reviewer's mailbox holds the cancel and reinstate notices.
        await pkpMail.find({to: `${accepted}@mail.test`, subject: 'Request for Review Cancelled'});
        await pkpMail.find({to: `${accepted}@mail.test`, subject: 'Can you still review something'});
    });

    test('S12: decline, then ask again', async ({asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s12', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag, {
            reviewers: [{username: 'reviewer.amara', status: 'declined'}],
        });

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        const row = workflow.panelRow('Reviewers', 'Amara Reviewer');
        await expect(row).toContainText('Request Declined');

        // Resend the request, keeping the window's preset dates (their
        // preset values are register A9 — asserted neither way).
        await clickRowAction(editorPage, row, 'Resend Review Request');
        const resendModal = legacyModal(editorPage, 'resendRequestReviewerForm');
        await resendModal
            .locator('form#resendRequestReviewerForm')
            .getByRole('button', {name: 'Resend Review Request', exact: true})
            .click();
        await expect(resendModal.locator('form#resendRequestReviewerForm')).toBeHidden({
            timeout: 30_000,
        });
        await waitForJQueryIdle(editorPage);

        // The row reads "Request Resent" (its second line's date is register
        // A2 — asserted neither way) and the request counts as unanswered
        // again: the menu re-offers "Unassign Reviewer" and "Log Response".
        await editorPage.reload();
        await workflow.expectOpen();
        await expect(row).toContainText('Request Resent');
        await row.getByRole('button', {name: 'More Actions'}).click();
        await expect(editorPage.getByRole('menuitem', {name: 'Unassign Reviewer'})).toBeVisible();
        await expect(editorPage.getByRole('menuitem', {name: 'Log Response'})).toBeVisible();
        await editorPage.keyboard.press('Escape');

        // The reviewer's mailbox holds the reconsider request.
        await pkpMail.find({
            to: 'reviewer.amara@mail.test',
            subject: 'Requesting your review again',
            contains: tag,
        });
    });

    test('S14: the recommendation runs through the table', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        test.setTimeout(240_000);
        const tag = makeTag('s14', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag, {
            reviewers: [{username: 'reviewer.adam', status: 'accepted'}],
        });

        const reviewerPage = await (await asUser('reviewer.adam')).newPage();
        await performReview(reviewerPage, JOURNAL, submissionId, {
            recommendation: 'Accept Submission',
        });

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        const row = workflow.panelRow('Reviewers', 'Adam Reviewer');
        await expect(row).toContainText('Review Submitted');

        // The read window's recommendation dropdown carries the reviewer's
        // choice and can be changed by the editor.
        await row.getByRole('button', {name: 'Read Review', exact: true}).click();
        const readModal = legacyModal(editorPage, 'readReviewForm');
        const recommendationSelect = readModal.locator('select[name="reviewerRecommendationId"]');
        await expect(recommendationSelect).toBeVisible({timeout: 30_000});
        await expect(recommendationSelect.locator('option:checked')).toHaveText(/Accept Submission/);
        await recommendationSelect.selectOption({label: 'Revisions Required'});
        await readModal.getByRole('button', {name: 'Confirm', exact: true}).click();
        await expect(readModal.locator('form#readReviewForm')).toBeHidden({timeout: 30_000});
        await waitForJQueryIdle(editorPage);

        // The "Complete" row's status cell shows the (editor-set)
        // recommendation under the status.
        await editorPage.reload();
        await workflow.expectOpen();
        await expect(row).toContainText('Complete');
        await expect(row).toContainText('Revisions Required');
    });
});
