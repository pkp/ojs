// @ts-check
const {test, expect} = require('../../../lib/pkp/playwright/support/base-test.js');
const {EditorialWorkflowPage} = require('../../pages/EditorialWorkflowPage.js');
const submissionInRound2 = require('../../fixtures/scenarios/submission-in-round-2.js');

/**
 * Sanity spec for the submission-in-round-2 fixture: confirms the
 * scenario endpoint produces the chained multi-round shape that the
 * fixture's docblock advertises.
 *
 * The verification leans on three load-bearing signals:
 *   - scenario response — two reviewRounds entries, the second's
 *     reviewer is jjanssen at status invited.
 *   - submission API — reviewAssignments shows phudson on round 1
 *     (completed) and jjanssen on round 2 (round number = 2, not the
 *     hardcoded-1 we hit before fixing ReviewRoundProcessor).
 *   - workflow page (as dbarnes) — the submission renders inside the
 *     editorial workflow modal so a real editor can pick up where the
 *     scenario left off (no error, no stuck "round 1" state).
 */

test.describe('submission-in-round-2 fixture — chained multi-round seeding', () => {
	test.use({user: 'dbarnes'});

	test('seeds round 1 closed + round 2 in progress', async ({pkpApi, page}) => {
		const tag = uniqueTag('round-2');
		const spec = submissionInRound2({tag});
		const result = await pkpApi.createSubmission(spec);

		// Scenario response sanity — chain landed.
		expect(result.warnings ?? []).toEqual([]);
		expect(result.decisions.map((d) => d.type)).toEqual([
			'sendExternalReview',
			'requestRevisions',
			'newExternalRound',
		]);
		expect(result.reviewRounds).toHaveLength(2);
		expect(result.reviewRounds[0].round).toBe(1);
		expect(result.reviewRounds[0].reviewers).toHaveLength(1);
		expect(result.reviewRounds[0].reviewers[0].username).toBe('phudson');
		expect(result.reviewRounds[0].reviewers[0].status).toBe('completed');
		expect(result.reviewRounds[1].round).toBe(2);
		expect(result.reviewRounds[1].reviewers).toHaveLength(1);
		expect(result.reviewRounds[1].reviewers[0].username).toBe('jjanssen');
		expect(result.reviewRounds[1].reviewers[0].status).toBe('invited');
		// Round 2's roundId must differ from round 1's — same id would
		// mean the second decision didn't actually create a new row.
		expect(result.reviewRounds[1].roundId).not.toBe(result.reviewRounds[0].roundId);

		// Submission API — review assignments carry the correct round
		// numbers. The pre-fix bug shipped jjanssen as round=1; this
		// assertion is the single load-bearing check that the
		// ReviewRoundProcessor patch held.
		const subRes = await page.request.get(
			`/index.php/publicknowledge/api/v1/submissions/${result.submission.id}`,
		);
		expect(subRes.ok(), `submission GET ${subRes.status()}`).toBe(true);
		const subBody = await subRes.json();
		expect(subBody.stageId).toBe(3); // WORKFLOW_STAGE_ID_EXTERNAL_REVIEW
		const assignments = subBody.reviewAssignments ?? [];
		expect(assignments).toHaveLength(2);
		const phudson = assignments.find((a) => a.reviewerUserName === 'phudson');
		const jjanssen = assignments.find((a) => a.reviewerUserName === 'jjanssen');
		expect(phudson?.round).toBe(1);
		expect(jjanssen?.round).toBe(2);
		expect(jjanssen?.roundId).toBe(result.reviewRounds[1].roundId);

		// Workflow page renders the submission cleanly. Stage indicator
		// matches the existing submission-stage.spec.js pattern.
		const workflow = new EditorialWorkflowPage(page);
		await workflow.goto(result.submission.id);
		await expect(page).toHaveURL(/workflowSubmissionId=/);
		await expect(page.getByText(tag, {exact: false}).first()).toBeVisible();
		await expect(workflow.stageIndicator('review')).toBeVisible();
	});
});

/**
 * Build a tag scoped to this parallel worker + suffix so two tests
 * running concurrently don't collide on the shared submissions list.
 *
 * @param {string} suffix
 */
function uniqueTag(suffix) {
	const slug = test
		.info()
		.title.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.slice(0, 16);
	return `t-w${test.info().parallelIndex}-${suffix}-${slug}`;
}
