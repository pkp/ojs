/**
 * @file cypress/tests/data/60-content/JnovakSubmission.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {

	var title = 'Condensing Water Availability Models to Focus on Specific Water Management Systems';
	var submission = {
		'section': 'Articles',
		sectionId: 1,
		'title': title,
		'abstract': 'The Texas Water Availability Modeling System is routinely applied in administration of the water rights permit system, regional and statewide planning, and an expanding variety of other endeavors. Modeling water management in the 23 river basins of the state reflects about 8,000 water right permits and 3,400 reservoirs. Datasets are necessarily large and complex to provide the decision-support capabilities for which the modeling system was developed. New modeling features are being added, and the different types of applications are growing. Certain applications are enhanced by simplifying the simulation input datasets to focus on particular water management systems. A methodology is presented for developing a condensed dataset for a selected reservoir system that reflects the impacts of all the water rights and accompanying reservoirs removed from the original complete dataset. A set of streamflows is developed that represents flows available to the selected system considering the effects of all the other water rights in the river basin contained in the original complete model input dataset that are not included in the condensed dataset. The methodology is applied to develop a condensed model of the Brazos River Authority reservoir system based on modifying the Texas Water Availability Modeling System dataset for the Brazos River Basin.',
		'keywords': [
			'water'
		],
		'authorNames': ['John Novak'],
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
			'username': 'jnovak',
			'givenName': 'John',
			'familyName': 'Novak',
			'affiliation': 'Aalborg University',
			'country': 'Denmark',
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

	it('Sends to review and performs two reviews', function() {
		cy.findSubmissionAsEditor('dbarnes', null, 'Novak');
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', submission.authorNames, [submission.title]);
		cy.isActiveStageTab('Review');
		cy.assignReviewer('Aisla McCrae');
		cy.assignReviewer('Adela Gallego');
		cy.logout();
		cy.performReview('amccrae', null, 'Condensing Water Availability Models to Focus on Specific Water Management Systems', 'Revisions Required');
		cy.performReview('agallego', null, 'Condensing Water Availability Models to Focus on Specific Water Management Systems', 'Resubmit for Review');
	});
});
