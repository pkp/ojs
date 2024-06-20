/**
 * @file cypress/tests/integration/NativeXmlImportExportIssue.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	const downloadedIssuePath = Cypress.config('downloadsFolder') + "/native-issue.xml";
	it('Exports an issue to XML', function() {
		var username = 'admin';
		cy.login(username, 'admin');

		cy.get('li.profile a:contains("' + username + '")').click();
		cy.get('li.profile a:contains("Dashboard")').click();
		cy.get('.app__nav a').contains('Tools').click();
		cy.get('a:contains("Native XML Plugin")').click();
		cy.get('a:contains("Export Issues")').click();
		cy.waitJQuery({timeout:20000});

		// Export first 2 issues
		cy.get('input[name="selectedIssues[]"]:lt(2)').check();
		cy.get('form#exportIssuesXmlForm button[type="submit"]').click();
		cy.contains('The export completed successfully.', {timeout:20000});

		cy.intercept({method: 'POST'}, (req) => {
			req.redirect('/');
		}).as('download');
		cy.contains('Download Exported File').parents('form').first().submit();
		cy.wait('@download').its('request').then((req) => {
			cy.request(req).then((res) => {
				expect(res).to.have.property('status', 200);
				expect(res.headers).to.have.property('content-type', 'text/xml;charset=utf-8');
				cy.writeFile(downloadedIssuePath, res.body, 'utf8');
			});
		});
	});
	it.skip('Imports an issue from XML', function() {
		var username = 'admin';
		cy.login(username, 'admin');

		cy.get('li.profile a:contains("' + username + '")').click();
		cy.get('li.profile a:contains("Dashboard")').click();
		cy.get('.app__nav a').contains('Tools').click();
		// The a:contains(...) syntax ensures that it will wait for the
		// tab to load. Do not convert to cy.get('a').contains('Native XML Plugin')
		cy.get('a:contains("Native XML Plugin")').click();

		cy.waitJQuery({timeout:20000});
		const issueYear = new Date().getFullYear() + 1;
		cy.readFile(downloadedIssuePath).then(fileContent => {
			// Setup year in the future to avoid conflicts
			fileContent = fileContent.replace(/<year>\d+<\/year>/g, `<year>${issueYear}</year>`);
			cy.get('input[type=file]').attachFile({fileContent, filePath: downloadedIssuePath, mimeType: 'text/xml', encoding: 'utf8'});
		});

		cy.get('input[name="temporaryFileId"][value!=""]', {timeout:20000});
		cy.get('form#importXmlForm button[type="submit"]').click();
		cy.contains('The import completed successfully.', {timeout:20000});
		cy.contains(`Vol. 1 No. 2 (${issueYear})`);
		cy.contains(`Vol. 2 No. 1 (${issueYear})`);
	});
});
