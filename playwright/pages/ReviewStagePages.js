/**
 * @file playwright/pages/ReviewStagePages.js
 *
 * OJS-local Page Objects and flow helpers for the review stage & rounds
 * feature (spec: lib/pkp/docs/product/specs/review-stage-and-rounds.md).
 *
 * Surfaces:
 * - WorkflowPage — the per-submission workflow dialog on the editorial
 *   (dashboard/editorial) and author (dashboard/mySubmissions) dashboards:
 *   round menu, status box, file/reviewer/participant panels, decision
 *   buttons, the Request Revisions entry modal.
 * - DecisionPage — the full-page decision wizard (decision/record/…):
 *   composer steps, promote-files step, Record Decision, success dialog.
 * - uploadViaWizard — the legacy jQuery file-upload wizard (3 tabs:
 *   Upload File → Review Details → Confirm).
 * - addReviewer / performReview / assignParticipant — the legacy grid flows
 *   around the review stage (Add Reviewer form, reviewer steps 1–4, the
 *   stage-participant form).
 *
 * Labels are the live locale strings (lib/pkp/locale/en/*.po); the DOM shapes
 * were confirmed against a live install (aria snapshots, 2026-07-31).
 */
const path = require('path');
const {expect} = require('@playwright/test');
const {BasePage} = require('../../lib/pkp/playwright/pages/BasePage.js');
const {waitForJQueryIdle} = require('../support/legacy.js');

/** Default upload fixture (app-local). */
const FIXTURE_PDF = path.join(__dirname, '..', 'fixtures', 'files', 'article.pdf');
const FIXTURE_PDF_NAME = 'article.pdf';

exports.FIXTURE_PDF = FIXTURE_PDF;
exports.FIXTURE_PDF_NAME = FIXTURE_PDF_NAME;

exports.WorkflowPage = class WorkflowPage extends BasePage {
    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} contextPath
     */
    constructor(page, contextPath) {
        super(page);
        this.contextPath = contextPath;
    }

    /** Open a submission's workflow on the editorial dashboard. */
    async gotoEditorial(submissionId) {
        await this.page.goto(
            this.contextUrl(this.contextPath, `/dashboard/editorial?workflowSubmissionId=${submissionId}`)
        );
        await this.expectOpen();
    }

    /** Open the author view of the same workflow (My Submissions). */
    async gotoAuthor(submissionId) {
        await this.page.goto(
            this.contextUrl(this.contextPath, `/dashboard/mySubmissions?workflowSubmissionId=${submissionId}`)
        );
        await this.expectOpen();
    }

    /** The workflow dialog is mounted (any stage). */
    async expectOpen() {
        await expect(
            this.page.getByRole('heading', {name: /^Workflow:/})
        ).toBeVisible({timeout: 30_000});
    }

    /** The stage page title, e.g. expectPageTitle('Review (Round 1)'). */
    async expectPageTitle(title) {
        await expect(
            this.page.getByRole('heading', {name: `Workflow: ${title}`})
        ).toBeVisible({timeout: 30_000});
    }

    /** Workflow-menu link for a round entry, e.g. roundLink(2). */
    roundLink(round) {
        return this.page.getByRole('link', {name: `Review Round ${round}`, exact: true});
    }

    async selectRound(round) {
        await this.roundLink(round).click();
        await this.expectPageTitle(`Review (Round ${round})`);
    }

    /**
     * The round status box (WorkflowSubmissionStatus): a bordered box with an
     * h3 heading ("Round {N} Status" on the current round, plain "Status" on
     * past rounds/stages) and the status sentence.
     */
    statusBox(heading) {
        return this.page
            .locator('div.border')
            .filter({has: this.page.getByRole('heading', {name: heading, exact: true})});
    }

    async expectStatus(heading, body) {
        await expect(this.statusBox(heading)).toContainText(body, {timeout: 20_000});
    }

    /**
     * A PkpTable panel wrapper (header + controls + table) located via the
     * table's accessible name (aria-labelledby = the panel h3). `.last()`
     * resolves to the innermost wrapping div, which contains the panel's own
     * buttons alongside the table.
     */
    panel(title) {
        return this.page
            .locator('div')
            .filter({has: this.page.getByRole('table', {name: title, exact: true})})
            .last();
    }

    /** A row of the named panel containing the given text. */
    panelRow(title, text) {
        return this.panel(title).getByRole('row').filter({hasText: text});
    }

    /** A decision/action button in the workflow action area. */
    decisionButton(label) {
        return this.page.getByRole('button', {name: label, exact: true});
    }

    /**
     * "Request Revisions" first opens the WorkflowSelectRevisionFormModal with
     * a radio choice; the new-round choice continues under the wizard name
     * "Resubmit for Review" (Rule 11).
     *
     * @param {{newRound?: boolean}} options
     */
    async clickRequestRevisions({newRound = false} = {}) {
        await this.decisionButton('Request Revisions').click();
        const modal = this.page
            .getByRole('dialog')
            .filter({hasText: 'Require New Review Round'});
        const label = newRound
            ? 'Revisions will be subject to a new round of peer reviews.'
            : 'Revisions will not be subject to a new round of peer reviews.';
        await modal.getByRole('radio', {name: label}).check();
        await modal.getByRole('button', {name: 'Next', exact: true}).click();
    }

    /** A participant list item's More Actions button ("{Name} More Actions"). */
    participantMoreActions(name) {
        return this.page.getByRole('button', {name: `${name} More Actions`});
    }
};

/**
 * The full-page decision wizard (decision/record/{id}). Steps render as a
 * jQuery-free Vue page: composer steps ("Notify Authors", …), an optional
 * promote-files step, footer buttons Continue / Record Decision, then a
 * success dialog with a "View Submission" link.
 */
exports.DecisionPage = class DecisionPage {
    /**
     * @param {import('@playwright/test').Page} page
     */
    constructor(page) {
        this.page = page;
        this.continueButton = page.getByRole('button', {name: 'Continue', exact: true});
        this.recordButton = page.getByRole('button', {name: 'Record Decision', exact: true});
        this.viewSubmissionLink = page.getByRole('link', {name: 'View Submission'});
    }

    /**
     * The wizard page is open under the given decision title. The h1 reads
     * "{Decision}: {Step}" on multi-step wizards and plain "{Decision}" on
     * single-step ones (templates/decision/record.tpl).
     */
    async expectOpen(title) {
        const escaped = title.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        await expect(
            this.page.getByRole('heading', {name: new RegExp(`^${escaped}(:|$)`), level: 1})
        ).toBeVisible({timeout: 30_000});
    }

    /**
     * Wait for any email-template composer on the current step to finish
     * loading (locator pitfall: submitting during the load mask posts an empty
     * body that fails server-side validation).
     */
    async awaitComposerLoaded() {
        await expect(this.page.locator('.composer__loadingTemplateMask')).toHaveCount(0, {
            timeout: 30_000,
        });
    }

    /** Advance one step. */
    async continueStep() {
        await this.awaitComposerLoaded();
        await this.continueButton.click();
    }

    /** The promote-files checkbox rendered inside a label naming the file. */
    promoteFileCheckbox(fileName) {
        return this.page
            .locator('label')
            .filter({hasText: fileName})
            .locator('input[type="checkbox"]');
    }

    /** Record the decision and return to the workflow via "View Submission". */
    async record() {
        await this.awaitComposerLoaded();
        await this.recordButton.click();
        await expect(this.viewSubmissionLink).toBeVisible({timeout: 30_000});
        await this.viewSubmissionLink.click();
        await expect(this.page.getByRole('heading', {name: /^Workflow:/})).toBeVisible({
            timeout: 30_000,
        });
    }

    /**
     * Walk every remaining step with no per-step input: wait out composer
     * loads, Continue until "Record Decision" appears, record, and return to
     * the workflow.
     */
    async completeAll({maxSteps = 6} = {}) {
        for (let i = 0; i < maxSteps; i++) {
            await this.awaitComposerLoaded();
            if (await this.recordButton.isVisible()) {
                await this.record();
                return;
            }
            await this.continueButton.click();
        }
        throw new Error(`DecisionPage.completeAll: no "Record Decision" button within ${maxSteps} steps`);
    }
};

/** The legacy file-upload wizard's side modal. */
function uploadWizardDialog(page) {
    return page.getByRole('dialog').filter({has: page.locator('div[id^="fileUploadWizard"]')});
}
exports.uploadWizardDialog = uploadWizardDialog;

/**
 * Drive the legacy 3-tab file-upload wizard (Upload File → Review Details →
 * Confirm) already opened by a panel's Upload control or the author's
 * "Upload revisions" button.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{genre?: string, file?: string}} options
 */
exports.uploadViaWizard = async function uploadViaWizard(page, {genre = 'Article Text', file = FIXTURE_PDF} = {}) {
    const dialog = uploadWizardDialog(page);
    const fileInput = dialog.locator('input[type="file"]');
    await expect(fileInput).toBeAttached({timeout: 30_000});
    const genreSelect = dialog.locator('select[id^="genreId"]');
    if (await genreSelect.count()) {
        await genreSelect.selectOption({label: genre});
    }
    await fileInput.setInputFiles(file);
    const continueButton = dialog.getByRole('button', {name: 'Continue', exact: true});
    await expect(continueButton).toBeEnabled({timeout: 30_000});
    await continueButton.click();
    // Review Details tab
    await expect(dialog.getByRole('tab', {name: '2. Review Details'})).toHaveAttribute(
        'aria-selected',
        'true',
        {timeout: 30_000}
    );
    await dialog.getByRole('button', {name: 'Continue', exact: true}).click();
    // Confirm tab
    await expect(dialog.getByRole('tab', {name: '3. Confirm'})).toHaveAttribute(
        'aria-selected',
        'true',
        {timeout: 30_000}
    );
    await dialog.getByRole('button', {name: 'Complete', exact: true}).click();
    await expect(dialog).toHaveCount(0, {timeout: 30_000});
    await waitForJQueryIdle(page);
};

/**
 * Add a reviewer to the selected round through the Reviewers panel's
 * "Add Reviewer" legacy form (advanced search → select → review type → add).
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} name the reviewer's display name, e.g. 'Julia Reviewer'
 * @param {{method?: string}} options review type radio label, e.g. 'Open'
 */
exports.addReviewer = async function addReviewer(page, name, {method} = {}) {
    await page.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
    const modal = page
        .getByRole('dialog')
        .filter({has: page.locator('.listPanel--selectReviewer')});
    const search = modal.locator('.listPanel--selectReviewer input.pkpSearch__input');
    await expect(search).toBeVisible({timeout: 30_000});
    await search.fill(name);
    await search.press('Enter');
    await modal.getByText(`Select ${name}`).click();
    await waitForJQueryIdle(page);
    if (method) {
        await modal.getByRole('radio', {name: method, exact: true}).check();
    }
    await modal.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
    await expect(modal).toHaveCount(0, {timeout: 30_000});
    await waitForJQueryIdle(page);
};

/**
 * Complete a review as the signed-in reviewer: open the reviewer page for the
 * submission, walk whatever steps remain (accept invitation, guidelines),
 * pick a recommendation and submit.
 *
 * @param {import('@playwright/test').Page} page an authenticated reviewer page
 * @param {string} contextPath
 * @param {number} submissionId
 * @param {{recommendation?: string}} options
 */
exports.performReview = async function performReview(page, contextPath, submissionId, {recommendation = 'Accept Submission'} = {}) {
    await page.goto(`/index.php/${contextPath}/reviewer/submission/${submissionId}`);
    // Whichever steps remain: a fresh invitation offers "Accept Review,
    // Continue to Step #2"; an already-accepted assignment revisits step 1
    // with "Save and continue"; step 2 offers "Continue to Step #3". The
    // steps are jQuery tabs — past panels stay in the DOM hidden, so every
    // read is visibility-filtered.
    const acceptButton = page.getByRole('button', {name: /Accept Review, Continue to Step #2/});
    const saveButton = page.getByRole('button', {name: 'Save and continue', exact: true});
    const step3Button = page.getByRole('button', {name: 'Continue to Step #3'});
    const submitButton = page.getByRole('button', {name: 'Submit Review', exact: true});
    const anyStepButton = submitButton
        .or(step3Button)
        .or(acceptButton)
        .or(saveButton)
        .filter({visible: true});
    await expect(anyStepButton.first()).toBeVisible({timeout: 30_000});
    // Step 1 (request), when offered. The page is stable right after load, so
    // this sampling races no tab transition.
    const stepOneButton = acceptButton.or(saveButton).filter({visible: true});
    if (await stepOneButton.count()) {
        const privacy = page.locator('input[name="privacyConsent"]').filter({visible: true});
        if (await privacy.count()) {
            await privacy.check();
        }
        await stepOneButton.first().click();
    }
    // Step 2 (guidelines), when offered; then step 3.
    const stepThreeOrSubmit = step3Button.or(submitButton).filter({visible: true});
    await expect(stepThreeOrSubmit.first()).toBeVisible({timeout: 30_000});
    if (await step3Button.filter({visible: true}).count()) {
        await step3Button.filter({visible: true}).first().click();
    }
    await expect(submitButton.filter({visible: true})).toBeVisible({timeout: 30_000});
    await page.locator('select[id="reviewerRecommendationId"]').selectOption({label: recommendation});
    await submitButton.click();
    // Legacy confirmation dialog
    await page.getByRole('button', {name: 'OK', exact: true}).click();
    await expect(page.getByRole('heading', {name: 'Review Submitted'})).toBeVisible({
        timeout: 30_000,
    });
};

/**
 * Assign a stage participant through the Participants panel's legacy form.
 *
 * @param {import('@playwright/test').Page} page
 * @param {{group: string, name: string, searchName: string, recommendOnly?: boolean}} options
 *   group: user-group option label (e.g. 'Journal manager'); name: display
 *   name shown in the results grid; searchName: name fragment to search by.
 */
exports.assignParticipant = async function assignParticipant(page, {group, name, searchName, recommendOnly = false}) {
    await page.getByRole('button', {name: 'Assign', exact: true}).click();
    const modal = page
        .getByRole('dialog')
        .filter({has: page.locator('select[name="filterUserGroupId"]')});
    await expect(modal.locator('select[name="filterUserGroupId"]')).toBeVisible({timeout: 30_000});
    await modal.locator('select[name="filterUserGroupId"]').selectOption({label: group});
    await waitForJQueryIdle(page);
    await modal.locator('input[id^="namegrid-users-userselect-userselectgrid-"]').fill(searchName);
    await modal
        .locator('form[id="searchUserFilter-grid-users-userselect-userselectgrid"]')
        .locator('button[id^="submitFormButton-"]')
        .click();
    await waitForJQueryIdle(page);
    await modal
        .getByRole('row')
        .filter({hasText: name})
        .locator('input[name="userId"]')
        .check();
    if (recommendOnly) {
        await modal.locator('input[name="recommendOnly"]').check();
    }
    await modal.getByRole('button', {name: 'OK', exact: true}).click();
    await expect(modal).toHaveCount(0, {timeout: 30_000});
    await waitForJQueryIdle(page);
};

module.exports.waitForJQueryIdle = waitForJQueryIdle;
