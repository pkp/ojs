/**
 * @file cypress/tests/integration/MultipleContexts.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Sites with more than one context', function() {

	it('Tests disabled context is not publicly visible', function() {
		cy.login('admin', 'admin');
		cy.visit('/index.php/publicknowledge/admin/contexts');
		cy.get('.show_extras').click();
		cy.get('#contextGridContainer a').contains('Edit').eq(0).click();
		cy.wait(1000);
		cy.get('span').contains('Enable this journal').siblings('input').uncheck();
		cy.get('button').contains('Save').click();
		cy.contains('Journal of Public Knowledge was edited successfully.');
		cy.logout();
		cy.visit('/index.php/publicknowledge');
		cy.get('h1').contains('Login');
		cy.login('admin', 'admin');
		cy.visit('/index.php/publicknowledge/admin/contexts');
		cy.get('.show_extras').click();
		cy.get('#contextGridContainer a').contains('Edit').eq(0).click();
		cy.wait(1000);
		cy.get('span').contains('Enable this journal').siblings('input').check();
		cy.get('button').contains('Save').click();
		cy.contains('Journal of Public Knowledge was edited successfully.');
	});
});