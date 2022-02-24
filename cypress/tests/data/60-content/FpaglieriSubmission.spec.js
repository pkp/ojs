/**
 * @file cypress/tests/data/60-content/FpaglieriSubmission.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		cy.register({
			'username': 'fpaglieri',
			'givenName': 'Fabio',
			'familyName': 'Paglieri',
			'affiliation': 'University of Rome',
			'country': 'Italy',
		});

		var submission = {
			'section': 'Reviews',
			'title': 'Hansen & Pinto: Reason Reclaimed',
			'abstract': 'None.',
			'authors': ['Fabio Paglieri'],
		};
		cy.createSubmission(submission);

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, 'Paglieri');
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', submission.authors, [submission.title]);
		cy.isActiveStageTab('Review');
		cy.assignReviewer('Julie Janssen');
		cy.assignReviewer('Adela Gallego');
		cy.clickDecision('Accept Submission');
		cy.recordDecisionAcceptSubmission(submission.authors, [], []);
		cy.isActiveStageTab('Copyediting');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
		cy.clickDecision('Send To Production');
		cy.recordDecisionSendToProduction(submission.authors, []);
		cy.isActiveStageTab('Production');
		cy.assignParticipant('Layout Editor', 'Stephen Hellier');
		cy.assignParticipant('Proofreader', 'Sabine Kumar');
	});
});
