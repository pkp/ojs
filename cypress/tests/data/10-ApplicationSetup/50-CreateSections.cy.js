/**
 * @file cypress/tests/data/50-CreateSections.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Creates/configures sections', function() {
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();
		cy.get('.app__nav a').contains('Journal').click();
		cy.get('button[id="sections-button"]').click();

		// Edit Articles section to add section editors
		cy.get('div#sections a[class=show_extras]').click();
		cy.get('a[id^=component-grid-settings-sections-sectiongrid-row-1-editSection-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time, waitJQuery is not sufficient
		cy.get('input[id^="wordCount-"]').type('500');
		cy.get('label').contains('Daniel Barnes').click();
		cy.get('label').contains('David Buskins').click();
		cy.get('label').contains('Stephanie Berardo').click();
		cy.get('form[id=sectionForm]').contains('Save').click();

		// Create a Reviews section
		cy.get('a[id^=component-grid-settings-sections-sectiongrid-addSection-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time, waitJQuery is not sufficient
		cy.get('input[id^="title-en-"]').type('Reviews', {delay: 0});
		cy.get('input[id^="abbrev-en-"]').type('REV', {delay: 0});
		cy.get('input[id^="identifyType-en-"]').type('Review Article', {delay: 0});
		cy.get('input[id=abstractsNotRequired]').click();
		cy.get('label').contains('Daniel Barnes').click();
		cy.get('label').contains('Minoti Inoue').click();
		cy.get('form[id=sectionForm]').contains('Save').click();
	});
})
