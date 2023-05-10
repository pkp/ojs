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
		cy.get('.app__nav a').contains('Journal').click();
		cy.get('button[id="categories-button"]').click();

		// Create an Applied Science category
		cy.get('a[id^=component-grid-settings-category-categorycategorygrid-addCategory-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[id^="name-en-"]').type('Applied Science', {delay: 0});
		cy.get('input[id^="path-"]').type('applied-science', {delay: 0});
		cy.get('form[id=categoryForm]').contains('OK').click();
		cy.get('tr[id^="component-grid-settings-category-categorycategorygrid-category-"] a:contains("Applied Science")');

		// Create a Computer Science subcategory
		cy.get('a[id^=component-grid-settings-category-categorycategorygrid-addCategory-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[id^="name-en-"]').type('Computer Science', {delay: 0});
		cy.get('select[id="parentId"]').select('Applied Science');
		cy.get('input[id^="path-"]').type('comp-sci', {delay: 0});
		cy.get('form[id=categoryForm]').contains('OK').click();
		cy.get('tr[id^="component-grid-settings-category-categorycategorygrid-category-"] span:contains("Computer Science")');

		// Create an Engineering subcategory
		cy.get('a[id^=component-grid-settings-category-categorycategorygrid-addCategory-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[id^="name-en-"]').type('Engineering', {delay: 0});
		cy.get('select[id="parentId"]').select('Applied Science');
		cy.get('input[id^="path-"]').type('eng', {delay: 0});
		cy.get('form[id=categoryForm]').contains('OK').click();
		cy.get('tr[id^="component-grid-settings-category-categorycategorygrid-category-"] span:contains("Engineering")');

		// Create a Social Sciences category
		cy.get('a[id^=component-grid-settings-category-categorycategorygrid-addCategory-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[id^="name-en-"]').type('Social Sciences', {delay: 0});
		cy.get('input[id^="path-"]').type('social-sciences', {delay: 0});
		cy.get('form[id=categoryForm]').contains('OK').click();
		cy.get('tr[id^="component-grid-settings-category-categorycategorygrid-category-"] a:contains("Social Sciences")');

		// Create a Sociology subcategory
		cy.get('a[id^=component-grid-settings-category-categorycategorygrid-addCategory-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[id^="name-en-"]').type('Sociology', {delay: 0});
		cy.get('select[id="parentId"]').select('Social Sciences');
		cy.get('input[id^="path-"]').type('sociology', {delay: 0});
		cy.get('form[id=categoryForm]').contains('OK').click();
		cy.get('tr[id^="component-grid-settings-category-categorycategorygrid-category-"] span:contains("Sociology")');

		// Create a Anthropology subcategory
		cy.get('a[id^=component-grid-settings-category-categorycategorygrid-addCategory-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[id^="name-en-"]').type('Anthropology', {delay: 0});
		cy.get('select[id="parentId"]').select('Social Sciences');
		cy.get('input[id^="path-"]').type('anthropology', {delay: 0});
		cy.get('form[id=categoryForm]').contains('OK').click();
		cy.get('tr[id^="component-grid-settings-category-categorycategorygrid-category-"] span:contains("Anthropology")');
	});
})
