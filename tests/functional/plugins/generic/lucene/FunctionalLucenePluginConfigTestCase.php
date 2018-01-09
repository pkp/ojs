<?php

/**
 * @file tests/functional/plugins/generic/lucene/FunctionalLucenePluginConfigTest.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginConfigTest
 * @ingroup tests_functional_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies (configuration features).
 */


import('tests.functional.plugins.generic.lucene.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.EmbeddedServer');

class FunctionalLucenePluginConfigTest extends FunctionalLucenePluginBaseTestCase {

	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('plugin_settings');
	}


	//
	// Tests
	//
	/**
	 * SCENARIO: Plug-in disabled + solr server switched off
	 *   GIVEN The lucene plug-in is de-activated
	 *     AND the solr server is switched off
	 *    WHEN I execute a search
	 *    THEN I will see search results served by the OJS standard
	 *         search implementation.
	 *
	 * SCENARIO: Plug-in enabled + solr server switched off
	 *   GIVEN The lucene plug-in is de-activated
	 *     AND the solr server is switched off
	 *    WHEN I activate the lucene plug-in
	 *     AND I execute a search
	 *    THEN I will see an error message informing that the
	 *         solr server is not functioning.
	 *
	 * SCENARIO: Plug-in enabled + solr server switched on
	 *   GIVEN The lucene plug-in is activated
	 *     AND the solr server is switched off
	 *    WHEN I switch on the solr server
	 *     AND I execute a search
	 *    THEN I will see search results served by the solr server.
	 */
	public function testPluginActivation() {
		// Locators required for this test.
		$pluginsPage = $this->baseUrl . '/index.php/lucene-test/manager/plugins/generic';
		$disableLucene = '//a[contains(@href, "manager/plugin/generic/luceneplugin/disable")]';
		$enableLucene = '//a[contains(@href, "manager/plugin/generic/luceneplugin/enable")]';

		// Go to the generic plugins page.
		$this->logIn();
		$this->verifyAndOpen($pluginsPage);

		// Make sure that the plugin is disabled.
		$this->verifyElementPresent($disableLucene);
		if ($this->verified()) {
			$this->clickAndWait($disableLucene);
		}
		$this->waitForElementPresent($enableLucene);

		// Make sure that the solr server is switched off.
		$embeddedServer = new EmbeddedServer();
		$this->assertTrue($embeddedServer->stopAndWait());

		// Execute a simple search
		$this->simpleSearch('test');

		// Check whether we get search served by the OJS default implementation.
		// In our case this means:
		// 1) No results (because our test database is not indexed in the DB)
		// 2) No error message
		$this->assertElementPresent('css=table.listing td.nodata');
		$this->assertText('css=table.listing td.nodata', 'No Results');

		// Enable the plugin but leave the solr server switched off.
		$this->verifyAndOpen($pluginsPage);
		$this->clickAndWait($enableLucene);
		$this->waitForElementPresent($disableLucene);

		// Execute a simple search
		$this->simpleSearch('test');

		// Check whether we get an error message.
		$this->assertElementPresent('css=table.listing td.nodata');
		$this->assertText('css=table.listing td.nodata', 'the OJS search service is currently offline');

		// Activate the solr server and wait up to five seconds
		// for it to become available.
		$embeddedServer->start();
		import('plugins.generic.lucene.classes.SolrWebService');
		$solrWebService = new SolrWebService('http://localhost:8983/solr/ojs/search', 'admin', 'please change', 'test-inst');
		$try = 0;
		while($solrWebService->getServerStatus() == SOLR_STATUS_OFFLINE) {
			$try ++;
			if ($try > 5) break;
			sleep(1);
		}

		// Execute a simple search
		$this->simpleSearch('test');

		// Now we should get a result set.
		$this->assertElementNotPresent('css=table.listing td.nodata');
	}


	/**
	 * SCENARIO OUTLINE: Settings form - valid entries
	 *   GIVEN I opened the lucene plug-in settings page
	 *    WHEN I change {setting} to a {valid value}
	 *     AND I hit the "Save" button
	 *    THEN these configuration parameters
	 *         will be saved to the database.
	 *
	 * EXAMPLES
	 *   setting                | valid value
	 *   ===============================================================
	 *   Search Endpoint URL    | http://search-server/solr/all-journals
	 *   Username               | adminuser
	 *   Password               | changed
	 *   Unique Installation ID | fqs
	 *
	 *
	 * SCENARIO OUTLINE: Settings form - valid entries
	 *   GIVEN I opened the lucene plug-in settings page
	 *    WHEN I change {setting} to an {invalid value}
	 *     AND I hit the "Save" button
	 *    THEN I will see an error message for the invalid
	 *         setting
	 *     AND the prior configuration paramters will
	 *         remain unchanged in the database.
	 *
	 * EXAMPLES
	 *   setting                | invalid value
	 *   ==========================================
	 *   Search Endpoint URL    | this-is-not-a-url // Must be a valid URL.
	 *   Username               | admin:user        // Colons are disallowed (HTTP-Basic authentication).
	 *   Password               |                   // Required value.
	 *   Unique Installation ID |                   // Required value.
	 *
	 * NB: We do not explicitly check the effect of the
	 * configuration changes as this is sufficiently
	 * checked in the other configuration/search scenarios
	 * which wouldn't work without a valid configuration.
	 * Checking configuration effect would unnecessarily
	 * bloat test code here.
	 */
	function testPluginSettings() {
		$pluginSettings = $this->baseUrl . '/index.php/lucene-test/manager/plugin/generic/luceneplugin/settings';
		$this->verifyAndOpen($pluginSettings);

		// First test invalid values.
		$this->type('searchEndpoint', 'this-is-not-a-url');
		$this->type('username', 'admin:user');
		$this->type('password', '');
		$this->type('instId', '');
		$this->clickAndWait('css=input.defaultButton');

		$this->waitForTextPresent('Please enter a valid URL');
		$this->assertTextPresent('Please enter a valid username');
		$this->assertTextPresent('Please enter a valid password');
		$this->assertTextPresent('Please enter an ID that uniquely identifies this OJS installation');

		// Now test valid values.
		$this->type('searchEndpoint', 'http://some.search-server.com/solr/all-journals');
		$this->type('username', 'adminuser');
		$this->type('password', 'changed');
		$this->type('instId', 'fqs');
		$this->clickAndWait('css=input.defaultButton');
		$this->waitForLocation('exact:' . $this->baseUrl . '/index.php/lucene-test/manager/plugins/generic');
	}
}
?>
