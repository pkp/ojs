// @ts-check
/**
 * @file playwright/fixtures/bootstrap.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The OJS base seed, as data.
 *
 * `POST /api/v1/_test/bootstrap` walks the application to this state through its
 * real services, so what follows is the whole definition of the world every OJS
 * test starts in. Two rules govern it:
 *
 * 1. **The base journal is READ-ONLY.** No test may change its settings,
 *    sections, categories, issues or the 18 seeded users — a test that needs
 *    journal-level mutations creates a scratch journal instead. That is what
 *    makes parallel workers safe.
 * 2. **Richer defaults are deliberate.** The seed enables what most real
 *    journals enable, so tests exercise representative configuration rather than
 *    an empty install. A change here means re-checking every implemented spec
 *    against the new defaults.
 */

const {users, byUsername} = require('../../lib/pkp/playwright/data/users.js');

/**
 * How each seeded user is enrolled in `publicknowledge`.
 *
 * Keys are OJS user-group name keys (`registry/userGroups.xml`, minus the
 * `default.groups.name.` prefix) — resolution is by that key, not by role id or
 * translated name.
 *
 * CAUTION: `editor` resolves to the "Journal editor" group, which carries
 * ROLE_ID_MANAGER — NOT sub-editor. A test that needs a non-manager editorial
 * actor wants `sectionEditor`.
 *
 * `sections` assigns the user as a section editor of those sections, exactly as
 * the Sections settings form does; it is what makes them a participant on new
 * submissions in that section.
 *
 * `admin` is absent on purpose: the installer creates it, and creating the
 * journal enrols the creating user as its manager.
 *
 * @type {Record<string, {roles: string[], sections?: string[]}>}
 */
const enrolments = {
	'manager.maya': {roles: ['manager']},
	'editor.diana': {roles: ['editor'], sections: ['ART', 'REV']},
	'sectioneditor.ana': {roles: ['sectionEditor'], sections: ['ART']},
	'sectioneditor.ravi': {roles: ['sectionEditor'], sections: ['REV']},
	'sectioneditor.omar': {roles: ['sectionEditor'], sections: ['ART']},
	'reviewer.julia': {roles: ['externalReviewer']},
	'reviewer.paul': {roles: ['externalReviewer']},
	'reviewer.amara': {roles: ['externalReviewer']},
	'reviewer.adam': {roles: ['externalReviewer']},
	'copyeditor.carla': {roles: ['copyeditor']},
	'copyeditor.sam': {roles: ['copyeditor']},
	'layouteditor.leo': {roles: ['layoutEditor']},
	'proofreader.pia': {roles: ['proofreader']},
	'author.alex': {roles: ['author']},
	'author.bea': {roles: ['author']},
	// The Funding coordinator group is the one default assistant group with
	// review-stage access (stages 1 and 3), which is the whole point of this
	// account.
	'assistant.rita': {roles: ['funding']},
	'reader.rosa': {roles: ['reader']},
};

/** The 17 seeded accounts, in roster order. */
function bootstrapUsers() {
	return users
		.filter((user) => enrolments[user.username])
		.map((user) => ({
			username: user.username,
			givenName: user.givenName,
			familyName: user.familyName,
			email: user.email,
			affiliation: 'Public Knowledge Project',
			country: 'CA',
			...enrolments[user.username],
		}));
}

/**
 * The full bootstrap payload.
 *
 * @returns {object}
 */
function bootstrapPayload() {
	return {
		context: {
			urlPath: 'publicknowledge',
			name: 'Journal of Public Knowledge',
			acronym: 'JPK',
			description:
				'The Journal of Public Knowledge is the test fixture journal every OJS end-to-end test starts from.',
			primaryLocale: 'en',
			// Multilingual on purpose: a bare front-end URL 302s to the
			// locale-prefixed form only on a multi-locale journal, and that
			// difference has bitten enough probes to be worth having in the base.
			supportedLocales: ['en', 'fr_CA'],

			// A journal with no contact address cannot accept a submission: the
			// acknowledgement mail fails for want of a From header AFTER the
			// submission is marked submitted.
			contactName: 'Ramiro Vaca',
			contactEmail: 'rvaca@mailinator.com',
			supportName: 'Ramiro Vaca',
			supportEmail: 'rvaca@mailinator.com',
			mailingAddress: '123 456th Street\nBurnaby, British Columbia\nCanada',

			onlineIssn: '0378-5955',
			printIssn: '0378-5946',
			copyrightNotice:
				'Authors who publish with this journal agree to the terms of the test fixture licence.',

			enableAnnouncements: true,
			enablePublicComments: true,
			disableSubmissions: false,

			// Double-anonymous review with the default deadlines most journals
			// configure, so review-stage tests see realistic dates.
			defaultReviewMode: 2,
			numWeeksPerResponse: 4,
			numWeeksPerReview: 4,

			keywords: 'request',
			citations: 'request',

			sections: [
				{
					title: 'Articles',
					abbrev: 'ART',
					policy: 'Section default policy for articles.',
					editorRestricted: false,
					abstractsNotRequired: false,
					wordCount: 500,
				},
				{
					title: 'Reviews',
					abbrev: 'REV',
					policy: 'Section default policy for reviews.',
					editorRestricted: false,
					abstractsNotRequired: true,
					identifyType: 'Review Article',
				},
			],

			// Parents first: a child names its parent by path.
			categories: [
				{
					path: 'applied-science',
					title: 'Applied Science',
					description: 'Applied science research.',
				},
				{
					path: 'comp-sci',
					title: 'Computer Science',
					parentPath: 'applied-science',
				},
				{
					path: 'computer-vision',
					title: 'Computer Vision',
					parentPath: 'comp-sci',
				},
				{
					path: 'eng',
					title: 'Engineering',
					parentPath: 'applied-science',
				},
				{
					path: 'social-sciences',
					title: 'Social Sciences',
					description: 'Social science research.',
				},
				{
					path: 'sociology',
					title: 'Sociology',
					parentPath: 'social-sciences',
				},
				{
					path: 'anthropology',
					title: 'Anthropology',
					parentPath: 'social-sciences',
				},
			],

			issues: [
				{
					volume: 1,
					number: '2',
					year: 2014,
					description: 'The published back issue of the fixture journal.',
					published: true,
					current: true,
				},
				{
					// The one to publish INTO: an upcoming issue that no test has to
					// create first.
					volume: 2,
					number: '1',
					year: 2015,
					published: false,
					current: false,
				},
			],
		},

		users: bootstrapUsers(),
	};
}

module.exports = {bootstrapPayload, bootstrapUsers, enrolments, byUsername};
