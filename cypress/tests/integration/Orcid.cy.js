/**
 * @file cypress/tests/integration/Orcid.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Orcid tests', function () {

	const submissionId = 1;

	/*it('Enable Orcid Plugin', function () {

		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("Website")').click();

		cy.waitJQuery();
		cy.get('button#plugins-button').click();

		// Goto settings
		cy.get('tr#component-grid-settings-plugins-settingsplugingrid-category-generic-row-orcidprofileplugin a.show_extras').click();
		cy.get('a[id^=component-grid-settings-plugins-settingsplugingrid-category-generic-row-orcidprofileplugin-settings-button]').click();

		// Add settings
		cy.get('form[id=orcidProfileSettingsForm]').within(() => {

			cy.get('select#orcidProfileAPIPath').select('Member Sandbox');
			cy.get('input[id^=orcidClientId]').click();
			cy.get('input[id^=orcidClientId]').clear().type('APP-T0KMLIZMQ8FMWNVL');
			cy.get('input[id^=orcidClientSecret]').click();
			cy.get('input[id^=orcidClientSecret]').clear().type('288de1f5-2b3a-4223-9783-c7e76c96f6c1');
			cy.get('input[id^=city]').click();
			cy.get('input[id^=city]').clear().type('Kabul');
			cy.get('select[id^=logLevel]').select('All');

			cy.get('button[id^=submitFormButton]').click();
		});
		// After settings, plugin is enabled
		cy.get('input[id^=select-cell-orcidprofileplugin]').check();
		cy.get('input[id^=select-cell-orcidprofileplugin]').should('be.checked');


	})*/

	it('Add co author', function () {
		cy.login('dbarnes', null, 'publicknowledge');
		cy.visit('/index.php/publicknowledge/workflow/access/' + submissionId);
		cy.get('button#publication-button').click();
		cy.get('button#contributors-button').click();

		cy.get('tr[id^=component-grid-users-author-authorgrid-row-1]').within(() => {
				cy.get('.show_extras').click();
				cy.get('a[id^=component-grid-users-author-authorgrid-row-1-editAuthor-button]').click();
		})

		cy.get('.pkp_modal_panel').within(() => {
			cy.get('#requestOrcidAuthorization').click();
			cy.get('button[id^=submitFormButton]').click();

		})

;

	})
})
