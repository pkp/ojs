// @ts-check

/**
 * Scenario fixture — a submission that has been sent to external review,
 * with a couple of reviewers already assigned in different states.
 *
 * Stage advance happens via the real `sendExternalReview` decision, which
 * auto-creates review round 1. The two reviewers land on that round: one
 * invited (no response yet), one accepted (reviewer clicked Accept but
 * hasn't submitted the review).
 *
 * Use from a test:
 *   const spec = submissionInReview({tag});
 *   const {submission} = await pkpApi.createSubmission(spec);
 */

/**
 * @param {Object} opts
 * @param {string} opts.tag                  required; appended to every title locale for parallel isolation
 * @param {string} [opts.submitter='rvaca']  baseline user that submits
 * @param {string} [opts.editor='dbarnes']   baseline user that sends to review (used only when `participants` not provided)
 * @param {string} [opts.journal='publicknowledge']  journal urlPath; override for E0 scratch journals
 * @param {Array}  [opts.participants]       override default participant list; defaults to `[{user: editor, role: 'editor'}]`.
 *                                           Each item may include `recommendOnly` / `canChangeMetadata` flags.
 * @param {Array}  [opts.reviewers]          override default reviewer list
 * @returns {object} scenario spec payload
 */
module.exports = function submissionInReview({
	tag,
	submitter = 'rvaca',
	editor = 'dbarnes',
	journal = 'publicknowledge',
	participants,
	reviewers,
} = {}) {
	if (!tag) {
		throw new Error('submissionInReview: tag is required');
	}

	return {
		tag,
		journal,
		submitter,
		section: 'ART',
		locale: 'en',

		participants: participants ?? [{user: editor, role: 'editor'}],

		decisions: [{type: 'sendExternalReview', by: editor}],

		reviewRounds: [
			{
				reviewers: reviewers ?? [
					{user: 'phudson', method: 'anonymous', status: 'invited'},
					{user: 'jjanssen', method: 'anonymous', status: 'accepted'},
				],
			},
		],

		publications: [
			{
				versionStage: 'AO',
				metadata: {
					title: {en: 'Article in external review'},
					abstract: {
						en: '<p>A submission that has been sent to external review.</p>',
					},
					keywords: {en: ['testing', 'review-stage']},
				},
				published: false,
			},
		],
	};
};
