/**
 * @file cypress/tests/data/50-CreateSections.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */
describe('Data suite tests', function() {
	it('Creates/configures categories', function() {
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();
		cy.get('nav').contains('Settings').click();
		// Ensure submenu item click despite animation
		cy.get('nav').contains('Journal').click({ force: true });
		cy.get('button[id="categories-button"]').click();

		// Create an Applied Science category
		cy.addCategory('Applied Science', 'applied-science');

		// Create a Computer Science subcategory
		cy.addCategory('Computer Science', 'comp-sci', 'Applied Science');

		// Create a Computer Vision subcategory within Computer Science
		cy.addCategory('Computer Vision', 'computer-vision', 'Computer Science');


		// Create an Engineering subcategory
		cy.addCategory('Engineering', 'eng', 'Applied Science');

		// Create a Social Sciences category
		cy.addCategory('Social Sciences', 'social-sciences');

		// Create a Sociology subcategory
		cy.addCategory('Sociology', 'sociology', 'Social Sciences');

		// Create a Anthropology subcategory
		cy.addCategory('Anthropology', 'anthropology', 'Social Sciences');
	});
})
