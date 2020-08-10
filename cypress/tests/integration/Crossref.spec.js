/**
 * @file cypress/tests/integration/Crossref.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('DOI/Crossref tests', function() {
	const issueDescription = "Vol. 1 No. 2 (2014)";
	const submissionId = 1;

	it('Check DOI Configuration', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("Website")').click();

		cy.waitJQuery();
		cy.get('button#plugins-button').click();

		// DOI is or can be enabled
		cy.get('input[id^=select-cell-doipubidplugin]').check();
		cy.get('input[id^=select-cell-doipubidplugin]').should('be.checked');

		// Go to DOI settings
		cy.get('tr#component-grid-settings-plugins-settingsplugingrid-category-pubIds-row-doipubidplugin a.show_extras').click();
		cy.get('a[id^=component-grid-settings-plugins-settingsplugingrid-category-pubIds-row-doipubidplugin-settings-button]').click();

		// Check all content
		cy.get('input#enableIssueDoi').check();
		cy.get('input#enablePublicationDoi').check();
		cy.get('input#enableRepresentationDoi').check();
		
		// Declare DOI Prefix
		cy.get('input[name=doiPrefix]').focus().clear().type('10.1234');

		// Save
		cy.get('form#doiSettingsForm button:contains("Save")').click();
		cy.get('div:contains("Your changes have been saved.")');
	});

	it('Check Issue DOI Configuration', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("Issues")').click();

		cy.waitJQuery();
		cy.get('button#back-button').click();

		// Select an issue
		cy.get('a:contains("' + issueDescription + '")').click();
		cy.get('div#editIssueTabs a').contains('Identifiers').click();

		// Check Save DOI
		cy.get('form#publicIdentifiersForm button:contains("Save")').click();
		cy.waitJQuery();
		
		// Go to see if the DOI is assigned
		cy.get('a:contains("' + issueDescription + '")').click();
		cy.get('div#editIssueTabs a').contains('Identifiers').click();

		cy.get('fieldset#pubIdDOIFormArea p').contains('The DOI is assigned to this issue.');
	});

	it('Check Issue DOI Visible', function() {
		cy.visit('');
		cy.get('a:contains("Archives")').click();
		cy.get('a:contains("' + issueDescription + '")').click();
		cy.get('div.pub_id').should('have.class', 'doi');
		cy.get('div.doi span.id a').contains('https://doi.org/10.1234/')
	});

	it('Check Submission DOI Configuration', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.visit('/index.php/publicknowledge/workflow/access/' + submissionId);	
		
		cy.get('button#publication-button').click();
		cy.get('button#identifiers-button').click();
		
		cy.get('input[name="pub-id::doi"]')
			.invoke('val')
			.then(sometext => {
				if (sometext == null || sometext.trim() == '') {
					cy.get('input[name="pub-id::doi"]')
						.parent()
						.find('button').contains('Assign').click();

					cy.get('div#identifiers')
						.find('button:contains("Save")').click();	

					cy.get('span').contains('Saved')
				} else {
					expect(sometext).to.contain('10.1234');
				}
			});

		cy.visit('/index.php/publicknowledge/workflow/access/' + submissionId);	
		cy.get('button#publication-button').click();

		cy.get("body").then($body => {
			if ($body.find("button.pkpButton:contains('Publish')").length > 0) {
				cy.get('button.pkpButton').contains('Publish').click();
				cy.contains('All publication requirements have been met.');
					cy.get('.pkpWorkflow__publishModal button').contains('Publish').click();
			}
		});
	});

	it('Check Submission DOI Visible', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		// Select a submission
		cy.visit('/index.php/publicknowledge/article/view/' + submissionId);

		cy.get('section.item.doi')
			.find('span.value').contains('https://doi.org/10.1234/');
	});

	it('Check Submission Crossref Export', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.visit('/index.php/publicknowledge/management/importexport/plugin/CrossRefExportPlugin');
		cy.waitJQuery();

		cy.get('input[name=depositorName]').focus().clear().type('admin');
		cy.get('input[name=depositorEmail]').focus().clear().type('pkpadmin@mailinator.com');

		cy.get('form#crossrefSettingsForm button:contains("Save")').click();
		cy.get('div:contains("Your changes have been saved.")');

		cy.scrollTo(0, 0);

		cy.get('a#ui-id-2').click();
		cy.get('input#select-1').check();
		cy.get('input#onlyValidateExport').check();
		cy.get('button#export').click();

		cy.contains('Validation successful!');
	});
	
})
