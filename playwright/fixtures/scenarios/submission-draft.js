// @ts-check

/**
 * Scenario fixture — a bare stage-1 submission with a handful of editors
 * attached as stage participants. No decisions, no review round, no
 * publish. Used as setup for tests that drive the Discussion Manager
 * (which lives inside the editorial workflow and only cares that the
 * submission and its participants exist).
 *
 * Use from a test:
 *   const spec = submissionDraft({tag, participants: [...]});
 *   const {submission} = await pkpApi.createSubmission(spec);
 */

/**
 * @param {Object} opts
 * @param {string} opts.tag                       required; appended to every title locale for parallel isolation
 * @param {string} [opts.submitter='rvaca']       baseline user that submits
 * @param {Array<{user: string, role: string}>} [opts.participants]
 *        Stage participants (defaults to dbarnes editor + dbuskins + minoue
 *        section editors — the cast the Discussion Manager tests reach for).
 * @returns {object} scenario spec payload
 */
module.exports = function submissionDraft({
	tag,
	submitter = 'rvaca',
	participants,
} = {}) {
	if (!tag) {
		throw new Error('submissionDraft: tag is required');
	}

	return {
		tag,
		journal: 'publicknowledge',
		submitter,
		section: 'ART',
		locale: 'en',

		participants: participants ?? [
			{user: 'dbarnes', role: 'editor'},
			{user: 'dbuskins', role: 'sectionEditor'},
			{user: 'minoue', role: 'sectionEditor'},
		],

		publications: [
			{
				versionStage: 'AO',
				metadata: {
					title: {en: 'Draft submission for discussions'},
					abstract: {
						en: '<p>A stage-1 submission used to test the Discussion Manager.</p>',
					},
					keywords: {en: ['testing', 'discussions']},
				},
				published: false,
			},
		],
	};
};
