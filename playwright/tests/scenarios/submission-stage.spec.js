// @ts-check
const {test, expect} = require('../../support/fixtures.js');
const {EditorialWorkflowPage} = require('../../pages/EditorialWorkflowPage.js');
const submissionInReview = require('../../fixtures/scenarios/submission-in-review.js');
const submissionPublished = require('../../fixtures/scenarios/submission-published.js');

/**
 * First end-to-end exercise of the Phase 2 submission scenario endpoint.
 *
 * Each test builds a scenario via pkpApi.createSubmission(), opens the
 * resulting submission in the editorial workflow page, and asserts the
 * stage indicator matches what the scenario drove the submission into.
 *
 * Intentionally light on UI assertions — the real proof that scenarios
 * work is "the page loads and the expected stage indicator is visible".
 * Tighten the POM's stage locators once the first real run surfaces the
 * exact DOM.
 */
test.describe('submission scenarios land at the right workflow stage', () => {
	// Every test in this describe acts as the editor who'll navigate the
	// workflow page. Storage state is lazily materialized by base-test's
	// `user` fixture on first use.
	test.use({user: 'dbarnes'});

	test('submission sent to external review shows the review stage', async ({pkpApi, page}) => {
		const tag = uniqueTag(test.info(), 'in-review');
		const spec = submissionInReview({tag});
		const result = await pkpApi.createSubmission(spec);

		// Sanity: the scenario response reflects what we asked for.
		expect(result.tag).toBe(tag);
		expect(result.decisions.map((d) => d.type)).toContain('sendExternalReview');
		expect(result.reviewRounds).toHaveLength(1);
		expect(result.reviewRounds[0].reviewers).toHaveLength(2);

		const workflow = new EditorialWorkflowPage(page);
		await workflow.goto(result.submission.id);
		await expect(page).toHaveURL(/workflowSubmissionId=/);

		// Tag is appended to every title locale by PublicationMetadataProcessor
		// — finding it confirms we're looking at our submission, not someone
		// else's on a shared list.
		await expect(page.getByText(tag, {exact: false}).first()).toBeVisible();

		await expect(workflow.stageIndicator('review')).toBeVisible();
	});

	test('submission chained through to production shows the production stage', async ({pkpApi, page}) => {
		const tag = uniqueTag(test.info(), 'published');
		const spec = submissionPublished({tag});
		const result = await pkpApi.createSubmission(spec);

		// Sanity: the chain of decisions recorded.
		expect(result.decisions.map((d) => d.type)).toEqual([
			'sendExternalReview',
			'accept',
			'sendToProduction',
		]);
		expect(result.publications).toHaveLength(1);
		expect(result.publications[0].status).toBeGreaterThan(1); // published (3) not queued (1)
		expect(result.publications[0].datePublished).toBeTruthy();
		expect(result.publications[0].issueId).toBeGreaterThan(0);

		const workflow = new EditorialWorkflowPage(page);
		await workflow.goto(result.submission.id);
		await expect(page).toHaveURL(/workflowSubmissionId=/);

		await expect(page.getByText(tag, {exact: false}).first()).toBeVisible();

		await expect(workflow.stageIndicator('production')).toBeVisible();
	});
});

/**
 * Build a tag scoped to this parallel worker + test title so two tests
 * running concurrently don't collide on the shared submissions list.
 *
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	const slug = info.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').slice(0, 16);
	return `t-w${info.parallelIndex}-${suffix}-${slug}`;
}
