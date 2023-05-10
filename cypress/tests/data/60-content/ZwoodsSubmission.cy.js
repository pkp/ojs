/**
 * @file cypress/tests/data/60-content/ZwoodsSubmission.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	var title = 'Finocchiaro: Arguments About Arguments';
	var submission = {
		'section': 'Reviews',
		sectionId: 2,
		'title': title,
		'abstract': 'None.',
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
			'username': 'zwoods',
			'givenName': 'Zita',
			'familyName': 'Woods',
			'affiliation': 'CUNY',
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

	it('Sends the submission to review and copyediting, and assigns reviewers and a copyeditor.', function() {
		let authorNames = [
			'Zita Woods',
		];
		cy.findSubmissionAsEditor('dbarnes', null, 'Woods');
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', authorNames, ['Finocchiaro: Arguments About Arguments']);
		cy.isActiveStageTab('Review');
		cy.assignReviewer('Paul Hudson');
		cy.assignReviewer('Aisla McCrae');
		cy.clickDecision('Accept Submission');
		cy.recordDecisionAcceptSubmission(authorNames, [], []);
		cy.isActiveStageTab('Copyediting');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
	});
});
