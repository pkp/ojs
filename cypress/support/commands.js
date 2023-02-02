/**
 * @file cypress/support/commands.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

import Api from '../../lib/pkp/cypress/support/api.js';
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

Cypress.Commands.add('createSubmissionWithApi', (data, csrfToken) => {
	const api = new Api(Cypress.env('baseUrl') + '/index.php/publicknowledge/api/v1');

	return cy.beginSubmissionWithApi(api, data, csrfToken)
		.putMetadataWithApi(data, csrfToken)
		.get('@submissionId').then((submissionId) => {
			if (typeof data.files === 'undefined' || !data.files.length) {
				return;
			}
			cy.visit('/index.php/publicknowledge/submission?id=' + submissionId);
			cy.get('button:contains("Continue")').click();

			// Must use the UI to upload files until we upgrade Cypress
			// to 7.4.0 or higher.
			// @see https://github.com/cypress-io/cypress/issues/1647
			cy.uploadSubmissionFiles(data.files);
		})
		.addSubmissionAuthorsWithApi(api, data, csrfToken);
});

