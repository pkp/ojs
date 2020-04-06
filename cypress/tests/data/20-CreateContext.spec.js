/**
 * @file cypress/tests/data/20-CreateContext.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Creates a context', function() {
		cy.login('admin', 'admin');

		// Create a new context
		cy.get('div[id=contextGridContainer]').find('a').contains('Create').click();

		// Fill in various details
		cy.wait(1000); // https://github.com/tinymce/tinymce/issues/4355
		cy.get('div[id=editContext]').find('button[label="Français (Canada)"]').click();
		cy.get('input[name="name-fr_CA"]').type(Cypress.env('contextTitles')['fr_CA'], {delay: 0});
		cy.get('button').contains('Save').click()
		cy.get('div[id=context-name-error-en_US]').find('span').contains('This field is required.');
		cy.get('div[id=context-acronym-error-en_US]').find('span').contains('This field is required.');
		cy.get('div[id=context-urlPath-error]').find('span').contains('This field is required.');
		cy.get('input[name="name-en_US"]').type(Cypress.env('contextTitles')['en_US'], {delay: 0});
		cy.get('input[name=acronym-en_US]').type('JPK', {delay: 0});
		cy.get('span').contains('Enable this journal').siblings('input').check();

		// Test invalid path characters
		cy.get('input[name=urlPath]').type('public&-)knowledge', {delay: 0});
		cy.get('button').contains('Save').click()
		cy.get('div[id=context-urlPath-error]').find('span').contains('The path can only include letters');
		cy.get('input[name=urlPath]').clear().type('publicknowledge', {delay: 0});

		// Context descriptions
		cy.setTinyMceContent('context-description-control-en_US', Cypress.env('contextDescriptions')['en_US']);
		cy.setTinyMceContent('context-description-control-fr_CA', Cypress.env('contextDescriptions')['fr_CA']);
		cy.get('button').contains('Save').click();

		// Wait for it to finish up before moving on
		cy.contains('Settings Wizard', {timeout: 30000});
	});

	it('Tests the settings wizard', function() {
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();
		cy.get('a').contains('Administration').click();
		cy.get('a').contains('Hosted Journals').click();
		cy.get('a[class=show_extras]').click();
		cy.contains('Settings wizard').click();

		cy.get('button[id="appearance-button"]').click();
		cy.get('div[id=appearance]').find('button').contains('Save').click();
		cy.contains('The theme has been updated.');

		cy.get('button[id="languages-button"]').click();
		cy.get('input[id^=select-cell-fr_CA-submissionLocale]').click();
		cy.contains('Locale settings saved.');

		cy.get('button[id="indexing-button"]').click();
		cy.get('input[name="searchDescription-en_US"]').type(Cypress.env('contextDescriptions')['en_US'], {delay: 0});
		cy.get('textarea[name="customHeaders-en_US"]').type('<meta name="pkp" content="Test metatag.">', {delay: 0});
		cy.get('div[id=indexing]').find('button').contains('Save').click();

		cy.get('label[for="searchIndexing-searchDescription-control-en_US"] ~ button.tooltipButton').click();
		cy.get('div').contains('Provide a brief description');
		cy.get('label[for="searchIndexing-searchDescription-control-en_US"] ~ button.tooltipButton').click();

		// OJS-specific tasks
		cy.get('button[id="context-button"]').click();
		cy.get('input[name="abbreviation-en_US"]').type('publicknowledge', {delay: 0});
		cy.get('div[id=context]').find('button').contains('Save').click();
		cy.contains('was edited successfully');
	});

	it('Tests context settings form', function() {
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();
		cy.get('a').contains('Settings').click();
		cy.get('a').contains('Journal').click();

		cy.get('input[name="abbreviation-en_US"]').type('J Pub Know', {delay: 0});
		cy.get('input[name="acronym-en_US"]').type(Cypress.env('contextAcronyms')['en_US'], {delay: 0});
		cy.get('input[name="publisherInstitution"]').type('Public Knowledge Project', {delay: 0});

		// Invalid ISSN
		cy.get('input[name="onlineIssn"]').type('0378-5955x', {delay: 0});
		cy.get('div[id=masthead]').find('button').contains('Save').click();
		cy.contains('This is not a valid ISSN.');
		cy.get('input[name="onlineIssn"]').clear().type('0378-5955', {delay: 0});

		cy.get('input[name="printIssn"]').type('03785955', {delay: 0});
		cy.get('div[id=masthead]').find('button').contains('Save').click();
		cy.contains('This is not a valid ISSN.');
		cy.get('input[name="printIssn"]').clear().type('0378-5955', {delay: 0});

		cy.get('div[id=masthead]').find('button').contains('Save').click();
		cy.contains('The masthead details for this journal have been updated.');
	});

	it('Tests contact settings form', function() {
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();
		cy.get('a').contains('Settings').click();
		cy.get('a').contains('Journal').click();
		cy.get('button[id="contact-button"]').click();

		// Submit the form with required fields missing.
		cy.get('div[id=contact').find('button').contains('Save').click();
		cy.get('div[id="contact-contactName-error"]').contains('This field is required.');
		cy.get('div[id="contact-contactEmail-error"]').contains('This field is required.');
		cy.get('div[id="contact-mailingAddress-error"]').contains('This field is required.');
		cy.get('div[id="contact-supportName-error"]').contains('This field is required.');
		cy.get('div[id="contact-supportEmail-error"]').contains('This field is required.');

		cy.get('input[name=contactName]').type('Ramiro Vaca', {delay: 0});
		cy.get('textarea[name=mailingAddress]').type("123 456th Street\nBurnaby, British Columbia\nCanada", {delay: 0});
		cy.get('input[name=supportName]').type('Ramiro Vaca', {delay: 0});

		// Test invalid emails
		cy.get('input[name=contactEmail').type('rvacamailinator.com', {delay: 0});
		cy.get('input[name=supportEmail').type('rvacamailinator.com', {delay: 0});
		cy.get('div[id=contact').find('button').contains('Save').click();
		cy.get('div[id="contact-contactEmail-error"]').contains('This is not a valid email address.');
		cy.get('div[id="contact-supportEmail-error"]').contains('This is not a valid email address.');

		cy.get('input[name=contactEmail').clear().type('rvaca@mailinator.com', {delay: 0});
		cy.get('input[name=supportEmail').clear().type('rvaca@mailinator.com', {delay: 0});
		cy.get('div[id=contact').find('button').contains('Save').click();
		cy.contains('The contact details for this');
	});

	it ('Activates plugin for testing locale keys', function() {
		cy.login('admin', 'admin');
		cy.visit('index.php/' + context + '/management/settings/website');
		cy.get('button[id="plugins-button"]').click();
		cy.get('input[id^="select-cell-missinglocaleexceptionplugin"').check();
		cy.contains('The plugin "Missing Locale Exception" has been enabled.');
	});
})
