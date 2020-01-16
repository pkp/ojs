/**
 * @file cypress/tests/data/60-content/FpaglieriSubmission.spec.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		var title = 'Hansen & Pinto: Reason Reclaimed';
		cy.register({
			'username': 'fpaglieri',
			'givenName': 'Fabio',
			'familyName': 'Paglieri',
			'affiliation': 'University of Rome',
			'country': 'Italy',
		});

		cy.createSubmission({
			'section': 'Reviews',
			title,
			'abstract': 'None.',
		});

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, title);
		cy.sendToReview();
		cy.assignReviewer('Julie Janssen');
		cy.assignReviewer('Adela Gallego');
		cy.recordEditorialDecision('Accept Submission');
		cy.get('li.ui-state-active a:contains("Copyediting")');
		cy.assignParticipant('Copyeditor', 'Sarah Vogt');
		cy.recordEditorialDecision('Send To Production');
		cy.get('li.ui-state-active a:contains("Production")');
		cy.assignParticipant('Layout Editor', 'Stephen Hellier');
		cy.assignParticipant('Proofreader', 'Sabine Kumar');
	});
});
