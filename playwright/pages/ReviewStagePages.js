/**
 * @file playwright/pages/ReviewStagePages.js
 *
 * OJS-local Page Objects and flow helpers for the review stage & rounds
 * feature (spec: lib/pkp/docs/e2e/specs/U26-review-stage-and-rounds.md).
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
    // Selecting a reviewer copies the Review Request template into the hidden
    // assignment form's TinyMCE editor (AdvancedReviewerSearchHandler
    // #handleReviewerAssign_); the server seeds personalMessage empty. If the
    // editor hasn't finished initializing the content is silently lost and the
    // submit 500s on an empty mail body — a race the side modal's 450ms
    // slide-in used to mask before the harness disabled animations.
    await page.waitForFunction(() => {
        const textarea = document.querySelector(
            '#reviewerFormFooter textarea[name="personalMessage"]'
        );
        const mce = window.tinyMCE || window.tinymce;
        return !!(textarea && mce?.get(textarea.id)?.initialized);
    }, undefined, {timeout: 30_000});
    await modal.getByText(`Select ${name}`).click();
    await waitForJQueryIdle(page);
    // Guard the submit on the message body actually holding the template.
    await expect(
        modal.frameLocator('iframe[id^="personalMessage"]').locator('body')
    ).not.toHaveText('', {timeout: 30_000});
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
 * optionally fill the two comment fields, pick a recommendation and submit.
 *
 * @param {import('@playwright/test').Page} page an authenticated reviewer page
 * @param {string} contextPath
 * @param {number} submissionId
 * @param {{recommendation?: string, comments?: string, privateComments?: string}} options
 *   comments → "For author and editor"; privateComments → the editor-only box.
 */
exports.performReview = async function performReview(page, contextPath, submissionId, {recommendation = 'Accept Submission', comments, privateComments} = {}) {
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
    // Step 3's comment boxes are TinyMCE-backed: type through the editor
    // (click + key events) — a DOM-level fill() bypasses the editor model
    // and the typed text never reaches the submitted textarea.
    if (comments) {
        const body = page
            .frameLocator('iframe[id^="comments"]:not([id^="commentsPrivate"])')
            .locator('body');
        await body.click();
        await body.pressSequentially(comments);
    }
    if (privateComments) {
        const body = page.frameLocator('iframe[id^="commentsPrivate"]').locator('body');
        await body.click();
        await body.pressSequentially(privateComments);
    }
    await page.locator('select[id="reviewerRecommendationId"]').selectOption({label: recommendation});
    await submitButton.click();
    // Legacy confirmation dialog
    await page.getByRole('button', {name: 'OK', exact: true}).click();
    await expect(page.getByRole('heading', {name: 'Review Submitted'})).toBeVisible({
        timeout: 30_000,
    });
};

/**
 * A legacy side modal located by the form it carries (the wrapper reports
 * visibility: hidden during transitions — anchor waits on inner content).
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} formId e.g. 'editReviewForm', 'sendReminderForm'
 */
exports.legacyModal = function legacyModal(page, formId) {
    return page.getByRole('dialog').filter({has: page.locator(`form#${formId}`)});
};

/**
 * Pick a date on a jQuery UI datepicker field the way the screen offers it —
 * a calendar pick (typed dates are discarded by the widget; register A16 of
 * the reviewer-assignment spec, walked but never asserted). The visible
 * input's id is runtime-suffixed, so fields are addressed by id prefix.
 *
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} scope the form/modal holding the field
 * @param {string} fieldPrefix 'responseDueDate' | 'reviewDueDate'
 * @param {Date} date
 */
exports.pickDate = async function pickDate(page, scope, fieldPrefix, date) {
    const input = scope.locator(`input.datepicker[id^="${fieldPrefix}"]`);
    await input.click();
    const picker = page.locator('#ui-datepicker-div');
    await expect(picker).toBeVisible({timeout: 30_000});
    await picker.locator('select.ui-datepicker-year').selectOption(String(date.getFullYear()));
    await picker.locator('select.ui-datepicker-month').selectOption(String(date.getMonth()));
    await picker
        .locator('td:not(.ui-datepicker-other-month) a')
        .filter({hasText: new RegExp(`^${date.getDate()}$`)})
        .first()
        .click();
    await expect(picker).toBeHidden({timeout: 30_000});
};

/**
 * The Add Reviewer window (legacy form around the Vue search panel), opened
 * from the Reviewers panel's top button.
 *
 * @param {import('@playwright/test').Page} page
 */
exports.openAddReviewerModal = async function openAddReviewerModal(page) {
    await page.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
    const modal = page
        .getByRole('dialog')
        .filter({has: page.locator('.listPanel--selectReviewer')});
    await expect(
        modal.locator('.listPanel--selectReviewer input.pkpSearch__input')
    ).toBeVisible({timeout: 30_000});
    return modal;
};

/**
 * Search the "Locate a Reviewer" list by name (Enter-commit) and return the
 * matching list item.
 *
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} modal from openAddReviewerModal
 * @param {string} name
 */
exports.searchReviewerList = async function searchReviewerList(page, modal, name) {
    const search = modal.locator('.listPanel--selectReviewer input.pkpSearch__input');
    await search.fill(name);
    await search.press('Enter');
    const item = modal.locator('.listPanel--selectReviewer .listPanel__item').filter({hasText: name});
    await expect(item).toBeVisible({timeout: 30_000});
    return item;
};

/**
 * Search the "Locate a Reviewer" list and press the entry's Select button.
 * The selection handler prefills the request letter through the TinyMCE API
 * (AdvancedReviewerSearchHandler), so the click must wait for the editor to
 * be initialized — selecting earlier silently loses the prefill.
 *
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} modal from openAddReviewerModal
 * @param {string} name
 */
exports.selectReviewer = async function selectReviewer(page, modal, name) {
    const item = await exports.searchReviewerList(page, modal, name);
    await page.waitForFunction(() => {
        const textarea = document.querySelector(
            '#reviewerFormFooter textarea[name="personalMessage"]'
        );
        // eslint-disable-next-line no-undef
        const editor = textarea && window.tinyMCE && window.tinyMCE.EditorManager.get(textarea.id);
        // `initialized` matters: get() answers before the async render is
        // done, and a prefill written in that window is wiped when init
        // loads the (still empty) textarea — the letter then posts empty
        // and the add 500s server-side on the empty mail body.
        return !!(editor && editor.initialized);
    });
    // The list re-renders while search fetches settle, which can swallow a
    // click on the just-replaced node — retry until the request form has
    // actually swapped in for the search grid.
    await expect(async () => {
        await item.getByRole('button', {name: `Select ${name}`}).click({timeout: 5_000});
        await expect(modal.locator('#regularReviewerForm')).toBeVisible({timeout: 3_000});
    }).toPass({timeout: 30_000});
    await expect(modal.locator('[id^="selectedReviewerName"]')).toHaveText(name);
    await waitForJQueryIdle(page);
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
