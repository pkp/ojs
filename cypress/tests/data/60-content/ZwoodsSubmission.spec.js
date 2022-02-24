/**
 * @file cypress/tests/data/60-content/ZwoodsSubmission.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		cy.register({
			'username': 'zwoods',
			'givenName': 'Zita',
			'familyName': 'Woods',
			'affiliation': 'CUNY',
			'country': 'United States',
		});

		cy.createSubmission({
			'section': 'Reviews',
			'title': 'Finocchiaro: Arguments About Arguments',
			'abstract': 'None.'
		});

		let authors = [
			'Zita Woods',
		];

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, 'Woods');
		cy.clickDecision('Send for Review');
		cy.recordDecisionSendToReview('Send for Review', authors, ['Finocchiaro: Arguments About Arguments']);
		cy.isActiveStageTab('Review');
		cy.assignReviewer('Paul Hudson');
		cy.assignReviewer('Aisla McCrae');
		cy.clickDecision('Accept Submission');
		cy.recordDecisionAcceptSubmission(authors, [], []);
		cy.isActiveStageTab('Copyediting');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
	});
});
