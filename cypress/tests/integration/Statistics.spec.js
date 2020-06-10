/**
 * @file cypress/tests/integration/Statistics.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Statistics Tests', function() {
	it('Generates usage statistics', function() {
		cy.exec('php lib/pkp/tools/generateTestMetrics.php');
	});

	it('Check statistics', function() {
		cy.login('dbarnes', null, 'publicknowledge');
		cy.get('.app__nav a:contains("Articles")').click();
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
