/**
 * @file cypress/tests/integration/Pubmed.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Pubmed tests', function () {
	const submissionId = 1;

	it('Check Pubmed Export', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		// Submit export submission DOI XML request
		cy.window()
		.then((win) => {
			const csrfToken = win.pkp.currentUser.csrfToken;
			cy.request({
					url: '/index.php/publicknowledge/management/importexport/plugin/PubMedExportPlugin/exportSubmissions',
					method: 'POST',
					headers: {
						'X-Csrf-Token': csrfToken
					},
					form: true,
					body: {
						selectedSubmissions: [submissionId]
					}
				})
		})
		.then((response) => {
			expect(response.status).to.equal(200);
			expect(Cypress.$(response.body).find('Article > Journal > Issn').text()).to.equal('0378-5955');
			expect(Cypress.$(response.body).find('Article > ArticleTitle').text()).to.equal('The Signalling Theory Dividends');
		});
	});
});
