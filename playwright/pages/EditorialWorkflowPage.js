// @ts-check
const {expect} = require('@playwright/test');
const {BasePage} = require('../../lib/pkp/playwright/pages/BasePage.js');

/**
 * POM for the OJS editorial workflow page — the per-submission view an
 * editor lands on from the editorial dashboard. Drives the stage tabs,
 * the panels within each stage, and navigation back to the dashboard.
 *
 * URL shape mirrors what the existing Cypress suite uses (see
 * cypress/tests/integration/Z_ArticleViewDCMetadata.cy.js:393):
 *   /index.php/{journalPath}/{locale}/dashboard/editorial?workflowSubmissionId={id}
 *
 * OJS-specific — OMP/OPS have their own editorial workflow layouts. If
 * cross-app behaviour emerges, pull a base class into lib/pkp.
 */
exports.EditorialWorkflowPage = class EditorialWorkflowPage extends BasePage {
	/**
	 * Names the spec can use with stageIndicator() / expectStage().
	 * Matches the five OJS workflow stages in canonical order.
	 */
	static STAGES = ['submission', 'review', 'copyediting', 'production', 'published'];

	/** @param {import('@playwright/test').Page} page */
	constructor(page) {
		super(page);

		// Stage indicators — forgiving text matchers until the exact DOM
		// is in hand. Each points at the first visible occurrence of the
		// stage label. Tighten to role/region-scoped selectors once the
		// real page is inspected (likely a stage tab list with
		// aria-current on the active tab).
		this.stageIndicators = {
			submission: page.getByText(/submission/i).first(),
			review: page.getByText(/review/i).first(),
			copyediting: page.getByText(/copyediting|copyedit/i).first(),
			production: page.getByText(/production/i).first(),
			published: page.getByText(/published/i).first(),
		};
	}

	/**
	 * Navigate to the workflow view for a specific submission.
	 *
	 * @param {number} submissionId
	 * @param {{journalPath?: string, locale?: string}} [opts]
	 */
	async goto(submissionId, {journalPath = 'publicknowledge', locale = 'en'} = {}) {
		await this.page.goto(
			`/index.php/${journalPath}/${locale}/dashboard/editorial?workflowSubmissionId=${submissionId}`,
		);
	}

	/**
	 * Return the locator for a stage's indicator. Use for
	 * expect(locator).toBeVisible() / toBeHidden() in tests.
	 *
	 * @param {'submission'|'review'|'copyediting'|'production'|'published'} stage
	 */
	stageIndicator(stage) {
		const loc = this.stageIndicators[stage];
		if (!loc) {
			throw new Error(
				`Unknown stage '${stage}'. Known: ${EditorialWorkflowPage.STAGES.join(', ')}`,
			);
		}
		return loc;
	}

	/**
	 * Click a primary-decision action button on the workflow page.
	 * Decision buttons render as PkpButton (role=button) with the
	 * decision label from editor.submission.decision.*. Clicking the
	 * button navigates away to `decision/record/{id}` — wait for that
	 * navigation before returning so callers can drive the decision page.
	 *
	 * @param {string} label  e.g. 'Send for Review', 'Decline Submission'
	 */
	async clickDecision(label) {
		await this.page
			.getByRole('button', {name: label, exact: true})
			.first()
			.click();
		await this.page.waitForURL(/\/decision\/record\//, {timeout: 15_000});
	}

	/**
	 * Wait for the Composer email step to finish auto-loading its
	 * template. Email steps (e.g. "notifyAuthors") ship an empty
	 * subject/body in the initial server payload and populate them via
	 * an AJAX GET once the Composer mounts — see Composer.vue
	 * `isLoadingTemplate` / `.composer__loadingTemplateMask`. Submitting
	 * the decision while the subject/body are empty fails server-side
	 * validation ("This field is required"), so every email step must
	 * be awaited before Continue/Record Decision.
	 */
	async awaitEmailTemplateLoaded() {
		const mask = this.page.locator('.composer__loadingTemplateMask');
		// Mask may flicker in-and-out; wait to observe it gone.
		await expect(mask).toHaveCount(0, {timeout: 15_000});
	}

	/**
	 * Advance past a wizard-style step on the decision page. Every step
	 * except the last exposes a Continue button; the last swaps it out
	 * for "Record Decision" (see lib/pkp/templates/decision/record.tpl).
	 *
	 * Waits for any pending email-template load on the current step to
	 * settle first — otherwise a "Continue" click on an email step
	 * carries forward empty subject/body and the decision submit fails
	 * server-side validation.
	 */
	async clickContinue() {
		await this.awaitEmailTemplateLoaded();
		await this.page
			.getByRole('button', {name: 'Continue', exact: true})
			.click();
	}

	/**
	 * Submit the decision (final step) and wait for the success dialog.
	 * The success dialog is a role=dialog containing a "View Submission"
	 * link back to the workflow page.
	 *
	 * @param {string} [expectedMessage]  substring of the completion text
	 *   (DecisionType::getCompletedMessage), e.g. "has been sent to the
	 *   review stage". Omit to skip the message assertion.
	 */
	async recordDecision(expectedMessage) {
		await this.awaitEmailTemplateLoaded();
		await this.page
			.getByRole('button', {name: 'Record Decision', exact: true})
			.click();
		// The completion dialog is rendered by reka-ui inside a PkpDialog
		// (lib/ui-library/src/components/Modal/Dialog.vue) with
		// data-cy="dialog". role=dialog is set by reka but the portal
		// nests multiple; scope to the legacy hook for stability.
		const dialog = this.page.locator('[data-cy="dialog"]');
		await expect(dialog).toBeVisible({timeout: 15_000});
		if (expectedMessage) {
			await expect(dialog).toContainText(expectedMessage);
		}
	}

	/**
	 * Click "View Submission" on the success dialog and wait to land
	 * back on the editorial workflow page.
	 *
	 * @param {number} submissionId  the submission we decided on
	 */
	async viewSubmissionFromCompletionDialog(submissionId) {
		// DecisionPage.vue offers either "View Submission" (workflow-page
		// entry point) or "View Submission Summary" (dashboard entry
		// point, via a `ret` query param pointing at
		// dashboard/editorial?workflowSubmissionId=...). We land in the
		// dashboard shape today because the EditorialWorkflowPage POM
		// goto() uses the dashboard URL. Match either.
		await Promise.all([
			this.page.waitForURL(/workflowSubmissionId=/, {timeout: 15_000}),
			this.page
				.locator('[data-cy="dialog"]')
				.getByRole('link', {name: /^View Submission( Summary)?$/})
				.click(),
		]);
		// Sanity: we're on the right submission.
		await expect(this.page).toHaveURL(
			new RegExp(`workflowSubmissionId=${submissionId}(?:&|$)`),
		);
	}

	/**
	 * Fetch the submission as JSON via the REST API using the page's
	 * session cookies. Used after a decision to verify status/stage
	 * without depending on a specific UI indicator.
	 *
	 * @param {number} submissionId
	 * @param {string} [journalPath='publicknowledge']
	 * @returns {Promise<object>}
	 */
	async fetchSubmission(submissionId, journalPath = 'publicknowledge') {
		const res = await this.page.request.get(
			`/index.php/${journalPath}/api/v1/submissions/${submissionId}`,
		);
		if (!res.ok()) {
			throw new Error(
				`GET submission ${submissionId} failed: ${res.status()} ${await res.text()}`,
			);
		}
		return res.json();
	}
};
