/**
 * @file cypress/support/commands.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

import '../../lib/pkp/cypress/support/commands';


Cypress.Commands.add('publish', (issueId, issueTitle) => {
	cy.get('button[id="publication-button"]').click();
	cy.get('div#publication button:contains("Schedule For Publication")').click();
	cy.wait(1000);
	cy.get('select[id="assignToIssue-issueId-control"]').select(issueId);
	cy.get('div[id^="assign-"] button:contains("Save")').click();
	cy.get('div:contains("All publication requirements have been met. This will be published immediately in ' + issueTitle + '. Are you sure you want to publish this?")');
	cy.get('div.pkpWorkflow__publishModal button:contains("Publish")').click();
});

Cypress.Commands.add('isInIssue', (submissionTitle, issueTitle) => {
	cy.visit('');
	cy.get('a:contains("Archives")').click();
	cy.get('a:contains("' + issueTitle + '")').click();
	cy.get('a:contains("' + submissionTitle + '")');
});

Cypress.Commands.add('checkViewableGalley', (galleyTitle) => {
	cy.get('[class^="obj_galley_link"]').contains(galleyTitle).click();
	cy.wait(1000); // Wait for JS to populate iframe src attribute (https://github.com/pkp/pkp-lib/issues/6246)
	cy.get('iframe')
		.should('have.attr', 'src')
		.then((src) => {
			cy.request(src);
		});
});
