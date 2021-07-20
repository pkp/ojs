/**
 * @file cypress/tests/data/60-content/ImportIssue.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Imports an issue from XML', function() {
		// TODO: Import/export is not yet compatible with versioning.
		// See: https://github.com/pkp/pkp-lib/issues/4880
		//
		// Because of this problem, the publish issue tests (amwandenga/vkarbasizaed) were
		// updated to put the articles in Vol. 1 No. 2, instead of Vol. 1 No. 1. This may
		// need to be corrected after import/export is fixed.
		var username = 'admin';
		cy.login(username, 'admin');

		cy.get('li.profile a:contains("' + username + '")').click();
		cy.get('li.profile a:contains("Dashboard")').click();
		cy.get('.app__nav a').contains('Tools').click();
		// The a:contains(...) syntax ensures that it will wait for the
		// tab to load. Do not convert to cy.get('a').contains('Native XML Plugin')
		cy.get('a:contains("Native XML Plugin")').click();

		cy.wait(250);
		cy.fixture('export-issues.xml', 'utf8').then(fileContent => {
			cy.get('input[type=file]').attachFile(
				{fileContent, 'filePath': 'uploadedFile.xml', 'mimeType': 'text/xml', 'encoding': 'utf8'},
			);
		});

		cy.get('input[name="temporaryFileId"][value!=""]', {timeout:20000});

		cy.get('form#importXmlForm button[type="submit"]').click();

		cy.contains('The import completed successfully.', {timeout:20000});
		cy.contains('Vol. 1 No. 1 (2020): test 1', {timeout:20000});
		cy.contains('Vol. 1 No. 2 (2020): Test Issue 2', {timeout:20000});

	});
});
