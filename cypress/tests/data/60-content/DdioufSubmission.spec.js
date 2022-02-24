/**
 * @file cypress/tests/data/60-content/DdioufSubmission.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		cy.register({
			'username': 'ddiouf',
			'givenName': 'Diaga',
			'familyName': 'Diouf',
			'affiliation': 'Alexandria University',
			'country': 'Egypt',
		});

		var submission = {
			'section': 'Articles',
			'title': 'Genetic transformation of forest trees',
			'abstract': 'In this review, the recent progress on genetic transformation of forest trees were discussed. Its described also, different applications of genetic engineering for improving forest trees or understanding the mechanisms governing genes expression in woody plants.',
			'authors': ['Diaga Diouf']
		};
		cy.createSubmission(submission);

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, 'Diouf');
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', submission.authors, [submission.title]);
		cy.isActiveStageTab('Review');
		cy.assignReviewer('Paul Hudson');
		cy.assignReviewer('Adela Gallego');
		cy.clickDecision('Accept Submission');
		cy.recordDecisionAcceptSubmission(submission.authors, [], []);
		cy.isActiveStageTab('Copyediting');
		cy.assignParticipant('Copyeditor', 'Maria Fritz');
		cy.clickDecision('Send To Production');
		cy.recordDecisionSendToProduction(submission.authors, []);
		cy.isActiveStageTab('Production');
		cy.assignParticipant('Layout Editor', 'Graham Cox');
		cy.assignParticipant('Proofreader', 'Catherine Turner');
	});
});
