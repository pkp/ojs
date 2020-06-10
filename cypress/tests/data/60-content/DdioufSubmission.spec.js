/**
 * @file cypress/tests/data/60-content/DdioufSubmission.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
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

		cy.createSubmission({
			'section': 'Articles',
			'title': 'Genetic transformation of forest trees',
			'abstract': 'In this review, the recent progress on genetic transformation of forest trees were discussed. Its described also, different applications of genetic engineering for improving forest trees or understanding the mechanisms governing genes expression in woody plants.',
		});

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, 'Diouf');
		cy.sendToReview();
		cy.assignReviewer('Paul Hudson');
		cy.assignReviewer('Adela Gallego');
		cy.recordEditorialDecision('Accept Submission');
		cy.get('li.ui-state-active a:contains("Copyediting")');
		cy.assignParticipant('Copyeditor', 'Maria Fritz');
		cy.recordEditorialDecision('Send To Production');
		cy.get('li.ui-state-active a:contains("Production")');
		cy.assignParticipant('Layout Editor', 'Graham Cox');
		cy.assignParticipant('Proofreader', 'Catherine Turner');
	});
});
