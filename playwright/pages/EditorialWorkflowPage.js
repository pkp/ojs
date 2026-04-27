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

	/**
	 * Fetch the publications list for a submission. Useful for asserting
	 * publication state transitions (status, datePublished, version
	 * numbers) independently of the UI.
	 *
	 * @param {number} submissionId
	 * @param {string} [journalPath='publicknowledge']
	 * @returns {Promise<object[]>}
	 */
	async fetchPublications(submissionId, journalPath = 'publicknowledge') {
		const res = await this.page.request.get(
			`/index.php/${journalPath}/api/v1/submissions/${submissionId}/publications`,
		);
		if (!res.ok()) {
			throw new Error(
				`GET publications for ${submissionId} failed: ${res.status()} ${await res.text()}`,
			);
		}
		const body = await res.json();
		return body.items || body;
	}

	/**
	 * The workflow page renders inside a reka-ui dialog tagged
	 * `[data-cy="active-modal"]`. Several of its sub-panels (publish
	 * side-modal, create-version dialog) stack on top as separate
	 * `[data-cy="active-modal"]`, `[role="dialog"]`, or `[data-cy="dialog"]`
	 * elements — scope accordingly.
	 */
	workflowModal() {
		return this.page.locator('[data-cy="active-modal"]').first();
	}

	/**
	 * Open a Publication-panel sub-item in the side-nav. Handles both
	 * single-version workflows (one "Title & Abstract" etc.) and
	 * multi-version workflows where each version exposes its own set of
	 * sub-items; pass `{version: 'last'}` to target the newest version's
	 * item, which is what every versioned flow needs.
	 *
	 * Publication-panel entries in the side-nav are plain <a href="#">
	 * anchors whose text is the sub-item label. The version sections
	 * appear above their sub-items in DOM order ("Version of Record 1.0"
	 * → its six sub-items → "Version of Record 1.1" → its six sub-items).
	 * `first` / `last` is enough to disambiguate.
	 *
	 * @param {string} label                        e.g. 'Title & Abstract'
	 * @param {{version?: 'first'|'last'}} [opts]   defaults to 'first'
	 */
	async openPublicationPanel(label, {version = 'first'} = {}) {
		const link = this.workflowModal()
			.locator('nav')
			.getByText(label, {exact: true});
		const target = version === 'last' ? link.last() : link.first();
		await target.click();
	}

	/**
	 * Drive the full publish flow from the current Publication sub-panel.
	 * Assumes the editor has already opened a Publication sub-panel (e.g.
	 * Title & Abstract) on the publication to be published.
	 *
	 * Two-step UI:
	 *   1. Click "Schedule For Publication" (v1 panels) or "Publish" (v2+
	 *      panels — the button label changes for non-initial versions).
	 *      Both open the same "Review Publishing Details" side-modal.
	 *   2. In that side-modal:
	 *        - pick the "Assign To Current/Back Issue" option,
	 *        - pick the issue from the issueId <select>,
	 *        - confirm version stage & significance,
	 *      then click Confirm.
	 *   3. A separate `.pkpWorkflow__publishModal` confirms the publish
	 *      target ("All publication requirements have been met…"); click
	 *      its Publish button to commit.
	 *
	 * Defaults target the bootstrap-seeded "Vol. 1 No. 2 (2014)"; override
	 * with the `issueLabel` option for different seeded issues.
	 *
	 * @param {object} [opts]
	 * @param {string} [opts.issueLabel='Vol. 1 No. 2 (2014)']
	 * @param {string} [opts.versionStage='VoR']     AO | PMUR | VoR
	 * @param {'true'|'false'} [opts.versionIsMinor='true']
	 */
	async publishCurrentPanel({
		issueLabel = 'Vol. 1 No. 2 (2014)',
		versionStage = 'VoR',
		versionIsMinor = 'true',
	} = {}) {
		const modal = this.workflowModal();
		// Both button labels open the same Review Publishing Details flow;
		// the initial-version Title & Abstract panel uses
		// "Schedule For Publication", v2+ panels (and a few sibling panels)
		// use "Publish". Whichever the server renders, the side-nav loads
		// the panel async so the button isn't immediately visible. Use a
		// `.or()` locator + waitFor so we don't race the panel's mount and
		// then misidentify which label is present.
		const schedule = modal.getByRole('button', {
			name: 'Schedule For Publication',
			exact: true,
		});
		const publish = modal.getByRole('button', {name: 'Publish', exact: true});
		await schedule.or(publish).first().waitFor({state: 'visible', timeout: 15_000});
		if (await schedule.isVisible().catch(() => false)) {
			await schedule.click();
		} else {
			await publish.click();
		}

		// Review Publishing Details side-modal. On v1 it stacks as another
		// [data-cy="active-modal"]; on v2+ the server renders it as a
		// role=dialog with an accessible name. Matching by role+name
		// covers both cases.
		const reviewDetails = this.page.getByRole('dialog', {
			name: /Review Publishing Details/i,
		});
		await expect(reviewDetails).toBeVisible({timeout: 15_000});
		await reviewDetails.getByText(/Assign To Current\/Back Issue/i).click();
		const issueSelect = reviewDetails.locator('select[name=issueId]');
		// On "Assign To Current/Back Issue" the issueId select appears
		// visible; it's hidden for the other radios. Select only when present.
		if (await issueSelect.isVisible().catch(() => false)) {
			await issueSelect.selectOption({label: issueLabel});
		}
		await reviewDetails
			.locator('select[name=versionStage]')
			.selectOption(versionStage);
		await reviewDetails
			.locator('select[name=versionIsMinor]')
			.selectOption(versionIsMinor);
		await reviewDetails
			.getByRole('button', {name: 'Confirm', exact: true})
			.click();

		// Final publish confirmation — `.pkpWorkflow__publishModal` is the
		// "All publication requirements have been met" banner with a single
		// Publish button.
		const publishModal = this.page.locator('.pkpWorkflow__publishModal');
		await expect(publishModal).toBeVisible({timeout: 15_000});
		await publishModal
			.getByRole('button', {name: 'Publish', exact: true})
			.click();
		// The modal closes itself; wait for it to leave the DOM so
		// downstream assertions run against the settled workflow page.
		await expect(publishModal).toBeHidden({timeout: 15_000});
	}

	/**
	 * Click Unpublish on the current Publication sub-panel and confirm
	 * the dialog. Caller is responsible for first opening a sub-panel
	 * (e.g. Title & Abstract) on the publication to be unpublished —
	 * the Unpublish button only appears on panels of a published
	 * publication.
	 *
	 * The confirmation dialog is the PkpDialog (`[data-cy="dialog"]`) with
	 * a single "Unpublish" action.
	 */
	async unpublishCurrentPanel() {
		const modal = this.workflowModal();
		await modal.getByRole('button', {name: 'Unpublish', exact: true}).click();
		const dialog = this.page.locator('[data-cy="dialog"]');
		await expect(dialog).toBeVisible({timeout: 10_000});
		await dialog.getByRole('button', {name: 'Unpublish', exact: true}).click();
		await expect(dialog).toBeHidden({timeout: 10_000});
	}

	/**
	 * Drive the Create New Version dialog from the Publication side-nav.
	 * Clicks the "Create New Version" nav entry, fills stage + significance
	 * in the resulting dialog, and confirms.
	 *
	 * Caveat: after this dialog closes, the side-nav needs a full page
	 * reload to render the new version's entries — the Vue store seems to
	 * cache the publications list locally. Reload via
	 * `EditorialWorkflowPage#goto(submissionId)` before opening a panel on
	 * the new version.
	 *
	 * @param {object} [opts]
	 * @param {string} [opts.versionStage='VoR']
	 * @param {'true'|'false'} [opts.versionIsMinor='true']
	 */
	async createNewVersion({
		versionStage = 'VoR',
		versionIsMinor = 'true',
	} = {}) {
		const modal = this.workflowModal();
		await modal.getByText('Create New Version', {exact: true}).first().click();
		const dialog = this.page.locator('[data-cy="dialog"]');
		await expect(dialog).toBeVisible({timeout: 10_000});
		await dialog.locator('select[name=versionStage]').selectOption(versionStage);
		await dialog
			.locator('select[name=versionIsMinor]')
			.selectOption(versionIsMinor);
		await dialog.getByRole('button', {name: 'Confirm', exact: true}).click();
		await expect(dialog).toBeHidden({timeout: 15_000});
	}
};
