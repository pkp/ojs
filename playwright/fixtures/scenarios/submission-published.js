// @ts-check

/**
 * Scenario fixture — a fully-processed submission whose publication is
 * published to a specific issue. Exercises the longest realistic decision
 * chain: sendExternalReview → accept → sendToProduction, with one
 * completed review (recommendation + comments) along the way.
 *
 * By the time this fixture's scenario is built:
 *   - submission stageId = WORKFLOW_STAGE_ID_PRODUCTION (5)
 *   - one review round exists with one completed review
 *   - the publication has versionStage = VoR, status = STATUS_PUBLISHED,
 *     and is assigned to the issue referenced by `issue` (Vol 1, No 2,
 *     2014 is the default — that's the published issue Phase 1's
 *     bootstrap seeds).
 *
 * Use from a test:
 *   const spec = submissionPublished({tag});
 *   const {submission, publications} = await pkpApi.createSubmission(spec);
 */

/**
 * @param {Object} opts
 * @param {string} opts.tag                  required; appended to every title locale for parallel isolation
 * @param {string} [opts.submitter='rvaca']  baseline user that submits
 * @param {string} [opts.editor='dbarnes']   baseline user that makes the editorial decisions (used only when `participants` not provided)
 * @param {string} [opts.journal='publicknowledge']  journal urlPath; override for E0 scratch journals
 * @param {Array}  [opts.participants]       override default participant list; defaults to `[{user: editor, role: 'editor'}]`.
 *                                           Each item may include `recommendOnly` / `canChangeMetadata` flags.
 * @param {object|string} [opts.issue]       issue reference — defaults to the bootstrap's published Vol 1, No 2, 2014
 * @returns {object} scenario spec payload
 */
module.exports = function submissionPublished({
	tag,
	submitter = 'rvaca',
	editor = 'dbarnes',
	journal = 'publicknowledge',
	participants,
	issue = {volume: 1, number: 2, year: 2014},
} = {}) {
	if (!tag) {
		throw new Error('submissionPublished: tag is required');
	}

	return {
		tag,
		journal,
		submitter,
		section: 'ART',
		locale: 'en',

		participants: participants ?? [{user: editor, role: 'editor'}],

		decisions: [
			{type: 'sendExternalReview', by: editor},
			{type: 'accept', by: editor},
			{type: 'sendToProduction', by: editor},
		],

		reviewRounds: [
			{
				reviewers: [
					{
						user: 'phudson',
						method: 'anonymous',
						status: 'completed',
						recommendation: 'accept',
						comments: {
							toEditor: 'Solid submission — accepting.',
							toAuthor: 'Well-argued; consider adding more examples.',
						},
					},
				],
			},
		],

		publications: [
			{
				versionStage: 'VoR',
				jatsPublicVisibility: true,
				metadata: {
					title: {en: 'Published article'},
					abstract: {
						en: '<p>A fully-processed, published article in scenario form.</p>',
					},
					keywords: {en: ['testing', 'published']},
					copyrightHolder: {en: 'The Author'},
					copyrightYear: 2026,
					licenseUrl: 'https://creativecommons.org/licenses/by/4.0/',
					pages: '1-10',
				},
				issue,
				published: true,
			},
		],
	};
};
