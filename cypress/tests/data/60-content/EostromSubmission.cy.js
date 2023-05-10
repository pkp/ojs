/**
 * @file cypress/tests/data/60-content/EostromSubmission.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {

	var title = 'Traditions and Trends in the Study of the Commons';
	var submission = {
		'section': 'Articles',
		sectionId: 1,
		title,
		'abstract': 'The study of the commons has expe- rienced substantial growth and development over the past decades.1 Distinguished scholars in many disciplines had long studied how specific resources were managed or mismanaged at particular times and places (Coward 1980; De los Reyes 1980; MacKenzie 1979; Wittfogel 1957), but researchers who studied specific commons before the mid-1980s were, however, less likely than their contemporary colleagues to be well informed about the work of scholars in other disciplines, about other sec- tors in their own region of interest, or in other regions of the world. ',
		'keywords': [
			'Common pool resource',
			'common property',
			'intellectual developments'
		],
		'additionalAuthors': [
			{
				givenName: {en: 'Frank'},
				familyName: {en: 'van Laerhoven'},
				affiliation: {en: 'Indiana University'},
				email: 'fvanlaerhoven@mailinator.com',
				country: 'US',
				userGroupId: Cypress.env('authorUserGroupId')
			}
		],
		files: [
			{
				'file': 'dummy.pdf',
				'fileName': title + '.pdf',
				'mimeType': 'application/pdf',
				'genre': Cypress.env('defaultGenre')
			}
		]
	};

	it('Create a submission', function() {
		cy.register({
			'username': 'eostrom',
			'givenName': 'Elinor',
			'familyName': 'Ostrom',
			'affiliation': 'Indiana University',
			'country': 'United States',
		});

		cy.getCsrfToken();
		cy.window()
			.then(() => {
				return cy.createSubmissionWithApi(submission, this.csrfToken);
			})
			.then(xhr => {
				return cy.submitSubmissionWithApi(submission.id, this.csrfToken);
			});
	});
});
