<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginConfigAndAdminTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginConfigAndAdminTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies (configuration features).
 *
 * FEATURE: search configuration and index administration
 */

require_mock_env('env1');

import('plugins.generic.lucene.tests.functional.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.EmbeddedServer');

class FunctionalLucenePluginConfigAndAdminTest extends FunctionalLucenePluginBaseTestCase {

	private $_pluginSettings, $_genericPluginsPage;

	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'plugin_settings', 'submission_search_keyword_list',
			'submission_search_object_keywords', 'submission_search_objects'
		);
	}

	/**
	 * @see WebTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$this->_pluginSettings = $this->baseUrl . '/index.php/lucene-test/manager/plugin/generic/luceneplugin/settings';
		$this->_genericPluginsPage = 'exact:' . $this->baseUrl . '/index.php/lucene-test/manager/plugins/generic';
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
		$disableLucene = 'xpath=//input[@id="select-cell-luceneplugin-enabled" and @checked]';
		$enableLucene = 'xpath=//input[@id="select-cell-luceneplugin-enabled" and not(@checked)]';

		// Go to the generic plugins page.
		$this->logIn();
		$this->verifyAndOpen($pluginsPage);

		// Make sure that the plugin is disabled.
		$this->waitForElementPresent('css=tr.elementluceneplugin');
		$this->verifyElementPresent($disableLucene);
		if ($this->verified()) {
			$this->click($disableLucene);
			$this->waitForElementPresent('css=div.ui-dialog');
			$this->click('css=.ui-dialog-buttonset .ui-button');
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
		$this->waitForElementPresent('css=tr.elementluceneplugin');
		$this->click($enableLucene);
		$this->waitForElementPresent($disableLucene);

		// Execute a simple search
		$this->simpleSearch('test');

		// Check whether we get an error message.
		$this->assertElementPresent('css=table.listing td.nodata');
		$this->assertText('css=table.listing td.nodata', '*the OJS search service is currently offline*');

		// Start the solr server.
		$this->startSolrServer($embeddedServer);

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
		$this->logIn();
		$this->verifyAndOpen($this->_pluginSettings);

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
		$this->waitForLocation($this->_genericPluginsPage);
	}


	/**
	 * SCENARIO OUTLINE: enable search feature
	 *    WHEN I check the checkbox near to {search feature}
	 *     AND I click the "Save" button
	 *    THEN The search feature will be switched on accordingly
	 *         in the database.
	 *
	 * SCENARIO OUTLINE: disable search feature
	 *    WHEN I uncheck the checkbox near to {search feature}
	 *     AND I click the "Save" button
	 *    THEN The search feature will be switched off accordingly
	 *         in the database.
	 *
	 * EXAMPLES:
	 *   search feature
	 *   ============================
	 *   auto-suggest
	 *   alternative spelling
	 *   similar documents
	 *   highlighting
	 *   custom ranking
	 *   instant search
	 *   pull indexing
	 *   facet category discipline
	 *   facet category subject
	 *   facet category type
	 *   facet category coverage
	 *   facet category journal title
	 *   facet category authors
	 *   facet category publ. date
	 *
	 * NB: We do not test the actual feature here, see the corresponding
	 * feature tests instead.
	 */
	function testSearchFeatureConfiguration() {
		// Test configuration.
		$searchFeatures = array(
			'autosuggest', 'spellcheck', 'simdocs', 'highlighting',
			'customRanking', 'instantSearch', 'pullIndexing',
			'facetCategoryDiscipline', 'facetCategorySubject',
			'facetCategoryType', 'facetCategoryCoverage',
			'facetCategoryJournalTitle', 'facetCategoryAuthors',
			'facetCategoryPublicationDate',
		);

		// Go to the settings page.
		$this->logIn();
		$this->verifyAndOpen($this->_pluginSettings);

		// Enable all search features.
		foreach($searchFeatures as $searchFeature) {
			$this->check($searchFeature);
		}

		// Hit the save button.
		$this->clickAndWait('css=input.defaultButton');
		$this->waitForLocation($this->_genericPluginsPage);

		// Check that all settings have been correctly saved.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->_getCache(0, 'luceneplugin')->flush();
		foreach($searchFeatures as $searchFeature) {
			self::assertTrue((boolean)$pluginSettingsDao->getSetting(0, 'luceneplugin', $searchFeature), "Testing enabling $searchFeature");
		}

		// Go to the settings page.
		$this->verifyAndOpen($this->_pluginSettings);

		// Disable all search features.
		foreach($searchFeatures as $searchFeature) {
			$this->uncheck($searchFeature);
		}

		// Hit the save button.
		$this->clickAndWait('css=input.defaultButton');
		$this->waitForLocation($this->_genericPluginsPage);

		// Check that all settings have been correctly saved.
		$pluginSettingsDao->_getCache(0, 'luceneplugin')->flush();
		foreach($searchFeatures as $searchFeature) {
			self::assertFalse((boolean)$pluginSettingsDao->getSetting(0, 'luceneplugin', $searchFeature), "Testing disabling $searchFeature");
		}
	}

	/**
	 * SCENARIO: change autosuggest type
	 *    WHEN I change the autosuggest type
	 *     AND I click the "Save" button
	 *    THEN The autosuggest type will be changed accordingly
	 *         in the database.
	 *
	 * NB: We do not test the actual feature here, see the corresponding
	 * feature tests instead.
	 */
	public function testAutosuggestTypeConfiguration() {
		// Go to the settings page.
		$this->logIn();
		$this->verifyAndOpen($this->_pluginSettings);

		// Change the auto-suggest type.
		$this->select('autosuggestType', 'value=1');

		// Hit the save button.
		$this->clickAndWait('css=input.defaultButton');
		$this->waitForLocation($this->_genericPluginsPage);

		// Check that the auto-suggest type has been correctly saved.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->_getCache(0, 'luceneplugin')->flush();
		self::assertEquals(1, $pluginSettingsDao->getSetting(0, 'luceneplugin', 'autosuggestType'));

		// Go to the settings page.
		$this->verifyAndOpen($this->_pluginSettings);

		// Change the auto-suggest type.
		$this->select('autosuggestType', 'value=2');

		// Hit the save button.
		$this->clickAndWait('css=input.defaultButton');
		$this->waitForLocation($this->_genericPluginsPage);

		// Check that the auto-suggest type has been correctly saved.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->_getCache(0, 'luceneplugin')->flush();
		self::assertEquals(2, $pluginSettingsDao->getSetting(0, 'luceneplugin', 'autosuggestType'));
	}

	/**
	 * SCENARIO: disable all facet categories at once
	 *   GIVEN The faceting feature is enabled.
	 *    WHEN I disable the faceting feature
	 *    THEN All facet categories will automatically
	 *         be unchecked.
	 *
	 * SCENARIO: enable all facet categories at once
	 *   GIVEN The faceting feature is disabled.
	 *    WHEN I enable the faceting feature
	 *    THEN All facet categories will automatically
	 *         be selected.
	 *
	 * SCENARIO: disable all facet categories one-by-one
	 *   GIVEN I enabled at least one facet category
	 *    WHEN I disable all facet categories one-by-one
	 *    THEN The faceting feature will be automatically
	 *         disabled.
	 *
	 * SCENARIO: enable a single facet category
	 *   GIVEN I disabled all facet categories
	 *    WHEN I enable one facet category
	 *    THEN The faceting feature will be automatically
	 *         enabled.
	 *
	 * NB: We do not test the actual feature here, see the corresponding
	 * feature tests instead.
	 */
	public function testFacetingConfiguration() {
		$facetCategories = array(
			'facetCategoryDiscipline', 'facetCategorySubject', 'facetCategoryType',
			'facetCategoryCoverage', 'facetCategoryJournalTitle', 'facetCategoryAuthors',
			'facetCategoryPublicationDate'
		);
		$allCheckboxes = array_merge(array('faceting'), $facetCategories);

		// Go to the settings page.
		$this->logIn();
		$this->verifyAndOpen($this->_pluginSettings);

		// Ensure that faceting is fully enabled.
		foreach($allCheckboxes as $checkbox) {
			$this->assertChecked($checkbox);
		}

		// Disable the faceting feature.
		$this->click('faceting');

		// Check whether all categories have been disabled automatically.
		foreach($facetCategories as $facetCategory) {
			$this->waitForNotChecked($facetCategory);
		}

		// Enable the faceting feature.
		$this->click('faceting');

		// Check whether all categories have been enabled automatically.
		foreach($facetCategories as $facetCategory) {
			$this->waitForChecked($facetCategory);
		}

		// Disable all facet categories one-by-one.
		foreach($facetCategories as $facetCategory) {
			$this->click($facetCategory);
		}

		// Check that the faceting feature has been automatically disabled.
		$this->assertNotChecked('faceting');

		// Enable a single facet category.
		$this->click('facetCategoryCoverage');

		// Check that the faceting feature has been automatically enabled.
		$this->assertChecked('faceting');
	}

	/**
	 * SCENARIO: re-index all journals (GUI)
	 *    WHEN I leave the "all journals" default option of the
	 *         re-indexing section unchanged
	 *     AND I click the "Rebuild Index" button
	 *    THEN all articles of all journals of the installation will
	 *         be deleted from the index and then re-indexed.
	 *
	 * SCENARIO: re-index one journal (GUI)
	 *    WHEN I select one journal from the journal selector
	 *         in the re-indexing section
	 *     AND I click the "Rebuild Index" button
	 *    THEN all articles of that journal will be deleted from
	 *         the index and then re-indexed.
	 */
	public function testReindexJournalsByGui() {
		// Go to the settings page.
		$this->logIn();
		$this->verifyAndOpen($this->_pluginSettings);

		// Leave default selection and click "Rebuild Index".
		$this->clickAndWait('name=rebuildIndex');
		$this->waitForConfirmation('*can take a long time*');
		$messages = $this->getText('rebuildIndexMessage');

		// Check whether the server confirms the re-indexing
		// of all journals.
		$this->assertContains('LucenePlugin: Clearing index ... done', $messages);
		$this->assertRegExp('/LucenePlugin: Indexing "lucene-test" \. [0-9]+ articles indexed/', $messages);
		$this->assertRegExp('/LucenePlugin: Indexing "test" \. [0-9]+ articles indexed/', $messages);
		$this->assertContains('LucenePlugin: Rebuilding dictionaries ... done', $messages);

		// Select a single journal from the list.
		$this->select('journalToReindex', 'value=1');

		// Click "Rebuild Index".
		$this->clickAndWait('name=rebuildIndex');
		$this->waitForConfirmation('*can take a long time*');
		$messages = $this->getText('rebuildIndexMessage');

		// Check whether the server confirms the re-indexing
		// of all journals.
		$this->assertContains('LucenePlugin: Clearing index ... done', $messages);
		$this->assertRegExp('/LucenePlugin: Indexing "test" \. [0-9]+ articles indexed/', $messages);
		$this->assertContains('LucenePlugin: Rebuilding dictionaries ... done', $messages);

		// This time, the second journal must not appear in the indexing output.
		$this->assertNotContains('"lucene-test"', $messages);

		// Click "Rebuild Dictionaries".
		$this->clickAndWait('name=rebuildDictionaries');
		$this->waitForConfirmation('*can take a long time*');
		$messages = $this->getText('rebuildIndexMessage');

		// Check whether the server confirms the re-indexing
		// of all journals.
		$this->assertContains('LucenePlugin: Rebuilding dictionaries ... done', $messages);

		// This time no indexing should be done.
		$this->assertNotContains('Indexing', $messages);
	}

	/**
	 * SCENARIO: re-index one journal (CLI)
	 *   GIVEN I am on the command line
	 *    WHEN I execute the tools/rebuildSearchIndex.php script
	 *     AND I enter the path of a journal as command line argument
	 *    THEN all articles of that journal will be deleted from
	 *         the index and then re-indexed.
	 *
	 * SCENARIO: re-index all journals (CLI)
	 *   GIVEN I am on the command line
	 *    WHEN I execute the tools/rebuildSearchIndex.php script
	 *         without parameters
	 *    THEN all articles of all journals of the installation will
	 *         be deleted from the index and then re-indexed.
	 */
	public function testReindexJournalsByCli() {
		// Delete all documents from the index. As we use the embedded server
		// for testint we can use the well-known service parameters.
		import('plugins/generic/lucene/classes/SolrWebService');
		$solrWebService = new SolrWebService(
			'http://localhost:8983/solr/ojs/search',
			'admin', 'please change', 'test-inst'
		);
		$solrWebService->deleteArticlesFromIndex(1);
		$solrWebService->deleteArticlesFromIndex(2);
		$searchRequest = new SolrSearchRequest();
		$searchRequest->addQueryFieldPhrase('query', '*:*');
		$totalResults = null;
		$solrWebService->retrieveResults($searchRequest, $totalResults);
		self::assertEquals(0, $totalResults);

		// Assemble the command line script name.
		$scriptName = Core::getBaseDir() . '/tools/rebuildSearchIndex.php -d';

		// Execute the script for one journal only.
		$output = null;
		exec("php $scriptName test", $output);

		// Check the script output.
		$expectedOutput = array(
			'LucenePlugin: Clearing index \.\.\. done',
			'LucenePlugin: Indexing "test" \. [0-9]+ articles indexed',
			'LucenePlugin: Rebuilding dictionaries \.\.\. done'
		);
		foreach($output as $outputLine) {
			$expectedRegex = array_shift($expectedOutput);
			self::assertRegExp("/$expectedRegex/", $outputLine);
		}

		// Check the index.
		$solrWebService->retrieveResults($searchRequest, $totalResults);
		self::assertGreaterThan(0, $totalResults);
		self::assertLessThan(37, $totalResults);

		// Execute the script for all journals.
		$output = null;
		exec("php $scriptName", $output);

		// Check the script output.
		$expectedOutput = array(
			'LucenePlugin: Clearing index \.\.\. done',
			'LucenePlugin: Indexing "lucene-test" \. [0-9]+ articles indexed',
			'LucenePlugin: Indexing "test" \. [0-9]+ articles indexed',
		);
		foreach($output as $outputLine) {
			$expectedRegex = array_shift($expectedOutput);
			self::assertRegExp("/$expectedRegex/", $outputLine);
		}

		// Rebuild the dictionaries only
		$output = null;
		exec("php $scriptName -d -n", $output);

		// Check the script output.
		$expectedRegex = 'LucenePlugin: Rebuilding dictionaries \.\.\. done';
		self::assertEquals(1, count($output));
		self::assertRegExp("/$expectedRegex/", array_shift($output));

		// Check the index.
		$solrWebService->retrieveResults($searchRequest, $totalResults);
		self::assertGreaterThanOrEqual(37, $totalResults);
	}


	/**
	 * SCENARIO: execution environment check
	 *   GIVEN I am in an environment that does not allow
	 *         script execution
	 *    WHEN I open up the lucene plugin settings page
	 *    THEN I'll see an explanatory text that lists all
	 *         measures users may take to enable server
	 *         administration from the web interface.
	 */
	public function testExecutionEnvironmentCheck() {
		// Assemble a command line script name.
		$scriptName = Core::getBaseDir() . '/plugins/generic/lucene/embedded/bin/start.sh';
		if (Core::isWindows()) {
			$scriptName = str_replace(array('/', '.sh'), array(DIRECTORY_SEPARATOR, '.bat'), $scriptName);
		}

		// Change the execution flags of the script.
		if (Core::isWindows()) {
			$targetName = $scriptName . '.bak';
			rename($scriptName, $targetName);
			self::assertFalse(is_readable($scriptName));
		} else {
			chmod($scriptName, 0664);
			self::assertFalse(is_executable($scriptName));
		}

		// Open the lucene plugin settings page.
		$this->logIn();
		$this->verifyAndOpen($this->_pluginSettings . '#indexAdmin');

		// Check that the explanatory text is shown.
		$this->waitForElementPresent('serverNotAvailable');

		// Change the execution flag back/rename back. (We should
		// probably do this in tearDown() but let's try
		// to limit the exposure of such a statement to
		// as little code as possible. We can still change
		// this if it turns out to produce errors in
		// practice.)
		if (Core::isWindows()) {
			rename($targetName, $scriptName);
		} else {
			chmod($scriptName, 0775);
		}
	}

	/**
	 * SCENARIO: solr process admin button (solr not running)
	 *   GIVEN I am in an environment that allows execution of solr server
	 *         process management shell scripts from within PHP
	 *     AND solr binaries have been installed within the plugin's
	 *         "lib" directory
	 *     AND no solr process is running on the local machine
	 *    WHEN I open up the lucene plugin settings page
	 *    THEN I'll see a button "Start Server".
	 *
	 * SCENARIO: start embedded solr server
	 *   GIVEN I see the button "Stop Server" on the lucene plugin
	 *         search settings page
	 *    WHEN I click on this button
	 *    THEN a solr server process will be started.
	 *
	 * SCENARIO: solr process admin button (solr running)
	 *   GIVEN I am in an environment that allows execution of solr server
	 *         process management shell scripts from within PHP
	 *     AND I configured a solr server endpoint on "localhost"
	 *     AND a solr process is running on the local machine
	 *     AND the PID-file of the process is in the installation's
	 *         files direcory
	 *    WHEN I open up the lucene plugin settings page
	 *    THEN I'll see a button "Stop Server".
	 *
	 * SCENARIO: stop embedded solr server
	 *   GIVEN I see the button "Stop Server" on the lucene plugin
	 *         search settings page
	 *    WHEN I click on this button
	 *    THEN the running solr process will be stopped.
	 */
	public function testSolrServerStartStopButtons() {
		// Stop the solr server. (This is part of the
		// test but will also implicitly make sure that
		// the server doesn't run under the wrong user
		// which would make it unavailable to the web
		// frontend.)
		import('plugins/generic/lucene/classes/EmbeddedServer');
		$embeddedServer = new EmbeddedServer();
		$embeddedServer->stopAndWait();

		// Make sure that the server really stopped.
		// NB: If this fails then make sure that the server is
		// not running under a user different from the test user.
		// If that's the case we cannot stop the running server
		// and you'll have to stop it manually.
		self::assertFalse($embeddedServer->isRunning(), 'Embedded server still running.');

		// Open the lucene plugin settings page.
		$this->logIn();
		$this->verifyAndOpen($this->_pluginSettings . '#indexAdmin');

		// Check that no availability warning is shown.
		$this->waitForElementNotPresent('serverNotAvailable');

		// Check that a start button appears.
		$this->assertElementPresent('name=startServer');
		$this->assertElementNotPresent('name=stopServer');

		// Click on the start button.
		$this->clickAndWait('name=startServer');

		// Check that an embedded solr process is now running.
		self::assertTrue($embeddedServer->isRunning());

		// Check that a stop button appears.
		$this->assertElementPresent('name=stopServer');
		$this->assertElementNotPresent('name=startServer');

		// Click on the stop button.
		$this->clickAndWait('name=stopServer');

		// Check that the embedded solr process was stopped.
		self::assertFalse($embeddedServer->isRunning());

		// Restart the server from the test user so that it
		// is available for other tests.
		$this->startSolrServer($embeddedServer);
	}


	//
	// Private helper methods
	//
	/**
	 * Start the solr server and wait for it to
	 * become online.
	 * @param $embeddedServer EmbeddedServer
	 */
	private function startSolrServer($embeddedServer) {
		$embeddedServer->start();
		import('plugins.generic.lucene.classes.SolrWebService');
		$solrWebService = new SolrWebService('http://localhost:8983/solr/ojs/search', 'admin', 'please change', 'test-inst');
		$try = 0;
		while($solrWebService->getServerStatus() == SOLR_STATUS_OFFLINE && $try <= 10) {
			sleep(1);
			$try ++;
		}
		self::assertEquals(SOLR_STATUS_ONLINE, $solrWebService->getServerStatus());
	}
}

