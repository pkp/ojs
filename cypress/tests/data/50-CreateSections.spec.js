/**
 * @file cypress/tests/data/50-CreateSections.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Creates/configures sections', function() {
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();
		cy.get('a').contains('Settings').click();
		cy.get('a').contains('Journal').click();
		cy.get('button[id="sections-button"]').click();

		// Edit Articles section to add section editors
		cy.get('a[class=show_extras]').click();
		cy.get('a[id^=component-grid-settings-sections-sectiongrid-row-1-editSection-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[id^="wordCount-"]').type('500');
		cy.get('div.pkpListPanelItem').contains('David Buskins').click();
		cy.get('div.pkpListPanelItem').contains('Stephanie Berardo').click();
		cy.get('form[id=sectionForm]').contains('Save').click();

		// Create a Reviews section
		cy.get('a[id^=component-grid-settings-sections-sectiongrid-addSection-button-]').click();
		cy.wait(1000); // Avoid occasional failure due to form init taking time
		cy.get('input[id^="title-en_US-"]').type('Reviews', {delay: 0});
		cy.get('input[id^="abbrev-en_US-"]').type('REV', {delay: 0});
		cy.get('input[id^="identifyType-en_US-"]').type('Review Article', {delay: 0});
		cy.get('input[id=abstractsNotRequired]').click();
		cy.get('div.pkpListPanelItem').contains('Minoti Inoue').click();
		cy.get('form[id=sectionForm]').contains('Save').click();
	});
})
