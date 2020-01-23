/**
 * @file cypress/tests/integration/Statistics.spec.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 */

describe('Statistics Tests', function() {
	it('Generates usage statistics', function() {
		cy.exec('php lib/pkp/tools/generateTestMetrics.php');
	});

	it('Check statistics', function() {
		cy.login('dbarnes', null, 'publicknowledge');
		cy.get('ul[id="navigationPrimary"] a:contains("Statistics")').click();
		cy.get('ul[id="navigationPrimary"] a:contains("Articles")').click();
		cy.checkGraph(
			'Total abstract views by date',
			'Abstract Views',
			'Files',
			'Total file views by date',
			'File Views'
		);
		cy.checkTable(
			'Article Details',
			'articles',
			['Mwandenga', 'Karbasizaed']
		);
		cy.checkFilters([
			'Articles',
			'Reviews',
		]);
	});
});
