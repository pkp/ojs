/**
 * @file cypress/tests/integration/Orcid.cy.js
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */
describe('ORCID tests', function() {
	before(() => {
		cy.enableOrcid();
	});

	it('Sends ORCID verification request to author', function() {
		cy.login('dbarnes');
		cy.visit(
			'index.php/publicknowledge/en/dashboard/editorial?currentViewId=assigned-to-me'
		);
		cy.wait(10000);

		// Select a submission in submission list
		cy.findSubmissionAsEditor('dbarnes', null, 'Diouf');

		cy.get('a').contains('Contributors').click();

		cy.get('div.listPanel__itemActions')
			.contains('Primary Contact')
			.parents('div.listPanel__itemActions')
			.within(() => {
				cy.contains('button', 'Edit').click();
			})
			.then(() => {
				// Ensure side modal is opened before continuing
				cy.wait(10000);

				cy.get('[data-cy="sidemodal-header"]').should('be.visible');
				cy.contains('Request verification').click();
				cy.get('button').contains('Yes').click();
				cy.contains(
					'ORCID Verification has been requested! Resend Verification Email'
				).should('be.visible');
			});
	});
});
