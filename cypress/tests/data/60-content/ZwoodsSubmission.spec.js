/**
 * @file cypress/tests/data/60-content/ZwoodsSubmission.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
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

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, 'Woods');
		cy.sendToReview();
		cy.assignReviewer('Paul Hudson');
		cy.assignReviewer('Aisla McCrae');
		cy.recordEditorialDecision('Accept Submission');
		cy.get('li.ui-state-active a:contains("Copyediting")');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
	});
});
