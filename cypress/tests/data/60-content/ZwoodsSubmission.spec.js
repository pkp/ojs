/**
 * @file cypress/tests/data/60-content/ZwoodsSubmission.spec.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		var title = 'Finocchiaro: Arguments About Arguments';
		cy.register({
			'username': 'zwoods',
			'givenName': 'Zita',
			'familyName': 'Woods',
			'affiliation': 'CUNY',
			'country': 'United States',
		});

		cy.createSubmission({
			'section': 'Reviews',
			title,
			'abstract': 'None.'
		});

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, title);
		cy.sendToReview();
		cy.assignReviewer('Paul Hudson');
		cy.assignReviewer('Aisla McCrae');
		cy.recordEditorialDecision('Accept Submission');
		cy.get('li.ui-state-active a:contains("Copyediting")');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
	});
});
