/**
 * @file cypress/tests/integration/API.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('API tests', function() {
	it('Sets an API secret', function() {
		// Before API keys will function, an API key secret must be set in the configuration file.
		// This test is used to ensure one is set. (The default configuration file has an empty secret.)
		cy.readFile('config.inc.php').then((text) => {
			cy.writeFile('config.inc.php',
				text.replace("api_key_secret = \"\"", "api_key_secret = \"Api_Key_Secret_For_Testing_Purposes_Only\"")
			);
		});
	});

	it("Configures an author's API key", function() {
		cy.login('ccorino', null, 'publicknowledge');
		cy.get('.app__userNav button').click();
		cy.get('a:contains("Edit Profile")').click();
		cy.get('a[name="apiSettings"]').click();
		cy.get("body").then($body => {
			if ($body.find("button:contains(\"Delete\")").length > 0) {   
				cy.get('form[id="apiProfileForm"] button:contains("Delete")').click();
				cy.waitJQuery();
				cy.on('window:confirm', (text) => {
					return true;
				});
				cy.waitJQuery();
			}
		});
		cy.get('form[id="apiProfileForm"] button:contains("Create API Key")').click();
		cy.waitJQuery();
		cy.get('span:contains("Your changes have been saved.")');
		cy.get('input[id^="apiKey-"]').invoke('val').as('apiKey').then(function() {
			cy.log(this.apiKey);
		});
		cy.logout();
	});

	it("Lists submissions using an author's API key", function() {
		cy.request('index.php/publicknowledge/api/v1/submissions?apiToken=' + this.apiKey).then(response => {
			// The author only has a single submission; submissions from other users should not be included.
			expect(response.body.items.length).eq(1);
		});
	});

	it("Deletes an author's API key", function() {
		cy.login('ccorino', null, 'publicknowledge');
		cy.get('.app__userNav button').click();
		cy.get('a:contains("Edit Profile")').click();
		cy.get('a[name="apiSettings"]').click();
		cy.get('form[id="apiProfileForm"] button:contains("Delete")').click();
		cy.waitJQuery();
		cy.on('window:confirm', (text) => {
			return true;
		});
		cy.waitJQuery();
		cy.get('span:contains("Your changes have been saved.")');
		cy.get('input[id^="apiKey-"]').invoke('val').should('eq', 'None');
		cy.logout();
	});
})
