/**
 * @file cypress/support/commands.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 */

import '../../lib/pkp/cypress/support/commands';


Cypress.Commands.add('publish', (issueId, issueTitle) => {
	cy.get('button[id="publication-button"]').click();
	cy.get('button[id="issue-button"]').click();
	cy.get('select[id="issueEntry-issueId-control"]').select(issueId);
	cy.get('div[id="issue"] button:contains("Save")').click();
	cy.get('div:contains("The publication\'s issue details have been updated.")');
	cy.get('div[id="publication"] button:contains("Schedule For Publication")').click();
	cy.get('div:contains("All publication requirements have been met. This will be published immediately in ' + issueTitle + '. Are you sure you want to publish this?")');
	cy.get('div.pkpWorkflow__publishModal button:contains("Publish")').click();
});

Cypress.Commands.add('isInIssue', (submissionTitle, issueTitle) => {
	cy.visit('');
	cy.get('a:contains("Archives")').click();
	cy.get('a:contains("' + issueTitle + '")').click();
	cy.get('a:contains("' + submissionTitle + '")');
});
