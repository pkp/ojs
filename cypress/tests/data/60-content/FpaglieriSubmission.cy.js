/**
 * @file cypress/tests/data/60-content/FpaglieriSubmission.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {

	var title = 'Hansen & Pinto: Reason Reclaimed';
	var submission = {
		'section': 'Reviews',
		sectionId: 2,
		'title': title,
		'abstract': 'None.',
		'authorNames': ['Fabio Paglieri'],
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
			'username': 'fpaglieri',
			'givenName': 'Fabio',
			'familyName': 'Paglieri',
			'affiliation': 'University of Rome',
			'country': 'Italy',
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

	it('Sends to review, copyediting and production, and assigns reviewers, copyeditor, layout editor and proofreader', function() {
		cy.findSubmissionAsEditor('dbarnes', null, 'Paglieri');
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', submission.authorNames, [submission.title]);
		cy.isActiveStageTab('Review');
		cy.assignReviewer('Julie Janssen');
		cy.assignReviewer('Adela Gallego');
		cy.clickDecision('Accept Submission');
		cy.recordDecisionAcceptSubmission(submission.authorNames, [], []);
		cy.isActiveStageTab('Copyediting');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
		cy.clickDecision('Send To Production');
		cy.recordDecisionSendToProduction(submission.authorNames, []);
		cy.isActiveStageTab('Production');
		cy.assignParticipant('Layout Editor', 'Stephen Hellier');
		cy.assignParticipant('Proofreader', 'Sabine Kumar');
	});
});
