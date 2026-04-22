// @ts-check

/**
 * Bootstrap spec for the OJS test session. POSTed to /api/v1/_test/bootstrap
 * by bootstrap.setup.js. Assembled in JS (not JSON) so we can import the
 * shared baseline user catalog and avoid duplicating role/username data.
 *
 * Mirrors the `publicknowledge` journal the Cypress suite has used for years
 * (cypress/tests/data/10-ApplicationSetup/*) so existing muscle memory — user
 * names, journal path, section abbreviations — carries over.
 */

const {baselineUsers} = require('../../lib/pkp/playwright/data/users.js');

/**
 * Bootstrap consumes only non-admin users; admin is created by the installer.
 * Drop the admin entry (and any other siteAdmin placeholder) before sending.
 */
const bootstrapUsers = baselineUsers
	.filter((u) => !u.siteAdmin)
	.map((u) => ({
		username: u.username,
		givenName: u.givenName,
		familyName: u.familyName,
		email: u.email,
		country: u.country,
		affiliation: u.affiliation,
		journal: u.journal,
		roles: u.roles,
		...(u.mustChangePassword ? {mustChangePassword: true} : {}),
	}));

module.exports = {
	journals: [
		{
			path: 'publicknowledge',
			name: {
				en: 'Journal of Public Knowledge',
				fr_CA: 'Journal de la connaissance du public',
			},
			description: {
				en:
					'The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.',
				fr_CA:
					"Le Journal de Public Knowledge est une publication trimestrielle évaluée par les pairs sur le thème de l'accès du public à la science.",
			},
			acronym: {en: 'JPK'},
			abbreviation: {en: 'J Pub Know'},
			publisherInstitution: 'Public Knowledge Project',
			primaryLocale: 'en',
			supportedLocales: ['en', 'fr_CA'],
			country: 'IS',
			contact: {name: 'Ramiro Vaca', email: 'rvaca@mailinator.com'},
			onlineIssn: '0378-5955',
			printIssn: '0378-5955',

			sections: [
				{
					abbrev: {en: 'ART'},
					title: {en: 'Articles'},
					wordCount: 500,
					sectionEditors: ['dbarnes', 'dbuskins', 'sberardo'],
				},
				{
					abbrev: {en: 'REV'},
					title: {en: 'Reviews'},
					identifyType: {en: 'Review Article'},
					abstractsNotRequired: true,
					sectionEditors: ['dbarnes', 'minoue'],
				},
			],

			categories: [
				{
					path: 'applied-science',
					title: {en: 'Applied Science'},
					children: [
						{
							path: 'comp-sci',
							title: {en: 'Computer Science'},
							children: [
								{
									path: 'computer-vision',
									title: {en: 'Computer Vision'},
								},
							],
						},
						{path: 'eng', title: {en: 'Engineering'}},
					],
				},
				{
					path: 'social-sciences',
					title: {en: 'Social Sciences'},
					children: [
						{path: 'sociology', title: {en: 'Sociology'}},
						{path: 'anthropology', title: {en: 'Anthropology'}},
					],
				},
			],

			issues: [
				{volume: 1, number: 2, year: 2014, published: true, showTitle: false},
				{volume: 2, number: 1, year: 2015, published: false, showTitle: false},
			],
		},
	],
	users: bootstrapUsers,
};
