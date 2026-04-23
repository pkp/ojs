// @ts-check
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
};
