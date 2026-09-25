// @ts-check

/**
 * Scenario fixture — a submission that has gone through one full review
 * round, the editor requested revisions, then opened a second external
 * review round with a fresh reviewer.
 *
 * State at the end of the chain:
 *   - submission stageId = WORKFLOW_STAGE_ID_EXTERNAL_REVIEW (3), in progress
 *   - two review_rounds rows for stage 3
 *     - round 1: phudson reviewed (status=completed, recommendation=pendingRevisions)
 *     - round 2: jjanssen invited (no response yet)
 *   - decision history records sendExternalReview → requestRevisions
 *     → newExternalRound, all by dbarnes
 *
 * Notable plan-vs-reality gotcha: the live `newExternalRound` decision
 * resets round 1's stored `status` column to PENDING_REVIEWERS as a
 * side-effect of `parent::runAdditionalActions` in DecisionType. Tests
 * that need to assert "round 1 closed with revisions requested" should
 * read that fact off the decision history (the requestRevisions row),
 * not off review_rounds.status.
 *
 * Use for tests that exercise round-2 lifecycle (close round 2, start
 * round 3, accept after round 2, etc.) without driving the full
 * round-1 + revisions UI.
 *
 * Use from a test:
 *   const spec = submissionInRound2({tag});
 *   const {submission, reviewRounds} = await pkpApi.createSubmission(spec);
 */

/**
 * @param {Object} opts
 * @param {string} opts.tag                   required; appended to every title locale for parallel isolation
 * @param {string} [opts.submitter='rvaca']   baseline user that submits
 * @param {string} [opts.editor='dbarnes']    baseline user that drives the decisions (used only when `participants` not provided)
 * @param {Array}  [opts.participants]        override default participant list; defaults to `[{user: editor, role: 'editor'}]`.
 * @param {Array}  [opts.reviewRounds]        override the two-round reviewer setup wholesale.
 * @returns {object} scenario spec payload
 */
module.exports = function submissionInRound2({
	tag,
	submitter = 'rvaca',
	editor = 'dbarnes',
	participants,
	reviewRounds,
} = {}) {
	if (!tag) {
		throw new Error('submissionInRound2: tag is required');
	}

	return {
		tag,
		journal: 'publicknowledge',
		submitter,
		section: 'ART',
		locale: 'en',

		participants: participants ?? [{user: editor, role: 'editor'}],

		// Decision-map key is `newExternalRound` (matches DecisionProcessor::DECISION_MAP).
		// The class behind it is NewExternalReviewRound (Decision::NEW_EXTERNAL_ROUND = 14).
		decisions: [
			{type: 'sendExternalReview', by: editor},
			{type: 'requestRevisions', by: editor},
			{type: 'newExternalRound', by: editor},
		],

		reviewRounds: reviewRounds ?? [
			{
				reviewers: [
					{
						user: 'phudson',
						method: 'anonymous',
						status: 'completed',
						recommendation: 'pendingRevisions',
					},
				],
			},
			{
				reviewers: [{user: 'jjanssen', method: 'anonymous', status: 'invited'}],
			},
		],

		publications: [
			{
				versionStage: 'AO',
				metadata: {
					title: {en: `Multi-round article ${tag}`},
					abstract: {en: '<p>Round 2 in progress.</p>'},
					keywords: {en: ['testing', 'multi-round']},
				},
				published: false,
			},
		],
	};
};
