/**
 * @file cypress/tests/data/60-content/DphillipsSubmission.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		cy.register({
			'username': 'dphillips',
			'givenName': 'Dana',
			'familyName': 'Phillips',
			'affiliation': 'University of Toronto',
			'country': 'Canada',
		});

		cy.createSubmission({
			'section': 'Articles',
			'title': 'Investigating the Shared Background Required for Argument: A Critique of Fogelin\'s Thesis on Deep Disagreement',
			'abstract': 'Robert Fogelin claims that interlocutors must share a framework of background beliefs and commitments in order to fruitfully pursue argument. I refute Fogelin’s claim by investigating more thoroughly the shared background required for productive argument. I find that this background consists not in any common beliefs regarding the topic at hand, but rather in certain shared pro-cedural commitments and competencies. I suggest that Fogelin and his supporters mistakenly view shared beliefs as part of the required background for productive argument because these procedural com-mitments become more difficult to uphold when people’s beliefs diverge widely regarding the topic at hand.',
		});

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, 'Phillips');
		cy.sendToReview();
		cy.assignReviewer('Julie Janssen');
		cy.assignReviewer('Adela Gallego');
		cy.recordEditorialDecision('Accept Submission');
		cy.get('li.ui-state-active a:contains("Copyediting")');
		cy.assignParticipant('Copyeditor', 'Maria Fritz');
		cy.recordEditorialDecision('Send To Production');
		cy.get('li.ui-state-active a:contains("Production")');
		cy.assignParticipant('Layout Editor', 'Graham Cox');
	});
});
