/**
 * @file cypress/tests/integration/plugins/generic/WebFeed.spec.js
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Web Feed plugin tests', () => {
	const feedSize = 2;
	it('The side bar and the feeds are displayed properly', () => {
		cy.login('admin', 'admin', 'publicknowledge');

		cy.get('.app__nav a').contains('Website').click();
		cy.get('button[id="plugins-button"]').click();

		// Access the settings and setup some options
		cy.get('a[id^="component-grid-settings-plugins-settingsplugingrid-category-generic-row-webfeedplugin-settings-button-"]', {timeout: 20_000}).as('settings');
		cy.waitJQuery();
		cy.get('@settings').click({force: true});
		cy.get('#displayPage-all').check();
		cy.get('#displayItems-recent').check();
		cy.get('input[id^="recentItems"]').clear().type(feedSize, {delay: 0});
		cy.get('#includeIdentifiers').check();
		cy.get('form[id="webFeedSettingsForm"] button[id^="submitFormButton"]').click();
		cy.waitJQuery();

		// Enable the wed feed plugin's sidebar
		cy.reload();
		cy.get('.app__nav a').contains('Website').click();
		cy.get('button[id="appearance-button"]').click();

		cy.get('#appearance-setup-button').click();
		cy.contains('Web Feed Plugin').click();
		cy.contains('Web Feed Plugin').parents('form').find('button:contains("Save")').click();

		// Visit homepage
		cy.wait(2000);

		cy.visit('/');
		const feeds = {
			'atom': {mimeType: 'application/atom+xml'},
			'rss': {mimeType: 'application/rdf+xml'},
			'rss2': {mimeType: 'application/rss+xml'}
		};
		for (const feed in feeds) {
			// Find the web feeds at the side bar
			cy.get('.block_web_feed').find(`a[href$="WebFeedGatewayPlugin/${feed}"]`).then(link => feeds[feed].url = link.attr('href'));

			// Find the linked feeds at the homepage
			cy.get(`link[href$="WebFeedGatewayPlugin/${feed}"][type="${feeds[feed].mimeType}"]`);
		}
		// The validation depends on querying the API, which needs an ID, but it might not be possible to extract IDs from the feed due to custom URLs
		cy.then(() => {
			validateAtom(feeds.atom);
			validateRss(feeds.rss);
			validateRss2(feeds.rss2);
		});
	});

	function validateAtom(feed) {
		cy.request(feed.url).then(response => {
			expect(response.headers['content-type']).to.contain(feed.mimeType);
			const $xml = cy.$$(Cypress.$.parseXML(response.body));
			const $entries = $xml.find('entry');
			expect($entries.length).to.equal(feedSize);
			$entries.each((index, entry) => {
				const $entry = cy.$$(entry);
				const id = $entry.find('id').text().match(/\/(\d+)\/?$/)?.[1];
				if (!id) {
					return;
				}
				getSubmission(id).then(response => {
					const publication = response.body.publications.pop();
					expect($entry.find('title').text()).to.contain(publication.title.en);
					$entry.find('author name').each((index, name) => expect(publication.authorsString).to.contain(cy.$$(name).text()));
				});
			});
		});
	}

	function validateRss(feed) {
		cy.request(feed.url).then(response => {
			expect(response.headers['content-type']).to.contain(feed.mimeType);
			const $xml = cy.$$(Cypress.$.parseXML(response.body));
			const $entries = $xml.find('item');
			expect($entries.length).to.equal(feedSize);
			$entries.each((index, entry) => {
				const $entry = cy.$$(entry);
				const id = $entry.find('id').text().match(/\/(\d+)\/?$/)?.[1];
				if (!id) {
					return;
				}
				getSubmission(id).then(response => {
					const publication = response.body.publications.pop();
					expect($entry.find('title').text()).to.contain(publication.title.en);
					$entry.find('dc:creator').each((index, name) => expect(publication.authorsString).to.contain(cy.$$(name).text()));
				});
			});
		});
	}

	function validateRss2(feed) {
		cy.request(feed.url).then(response => {
			expect(response.headers['content-type']).to.contain(feed.mimeType);
			const $xml = cy.$$(Cypress.$.parseXML(response.body));
			const $entries = $xml.find('item');
			expect($entries.length).to.equal(feedSize);
			$entries.each((index, entry) => {
				const $entry = cy.$$(entry);
				const id = $entry.find('id').text().match(/\/(\d+)\/?$/)?.[1];
				if (!id) {
					return;
				}
				getSubmission(id).then(response => {
					const publication = response.body.publications.pop();
					expect($entry.find('title').text()).to.contain(publication.title.en);
					$entry.find('dc:creator').each((index, name) => expect(publication.authorsString).to.contain(cy.$$(name).text()));
				});
			});
		});
	}

	function getSubmission(id) {
		return cy.request(`index.php/publicknowledge/api/v1/submissions/${id}`);
	}
});
