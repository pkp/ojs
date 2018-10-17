<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginRankingByMetricTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginRankingByMetricTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the "ranking-by-metric" feature of
 * the lucene plug-in.
 *
 * FEATURE: ranking by metric
 */

import('plugins.generic.lucene.tests.functional.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalLucenePluginRankingByMetricTest extends FunctionalLucenePluginBaseTestCase {
	private $tempDir, $extFilesDir;

	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('plugin_settings', 'metrics');
	}

	/**
	 * @see WebTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// Make sure that pull indexing is disabled by default.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'pullIndexing', false);

		// Move existing external field files to a temporary directory.
		$this->tempDir = tempnam(sys_get_temp_dir(), 'pkp');
		unlink($this->tempDir);
		mkdir($this->tempDir);
		$this->tempDir .= DIRECTORY_SEPARATOR;
		$this->extFilesDir = 'files' . DIRECTORY_SEPARATOR . 'lucene' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
		foreach (glob($this->extFilesDir . 'external_usageMetric*') as $source) {
			rename($source, $this->tempDir . basename($source));
		}
	}

	/**
	 * @see WebTestCase::tearDown()
	 */
	protected function tearDown() {
		// Delete external field files left over from tests.
		foreach (glob($this->extFilesDir . 'external_usageMetric*') as $source) {
			unlink($source);
		}

		// Restore external field files.
		foreach (glob($this->tempDir . 'external_usageMetric*') as $source) {
			rename($source, $this->extFilesDir . basename($source));
		}
		rmdir($this->tempDir);

		parent::tearDown();
	}


	//
	// Tests
	//
	/**
	 * SCENARIO: generate external boost file (disabled)
	 *   GIVEN I disabled the ranking-by-metric or pull indexing feature
	 *    WHEN I access the .../index/lucene/usageMetricBoost endpoint
	 *    THEN I download an empty text file.
	 *
	 * SCENARIO: generate external boost file for pull indexing
	 *   GIVEN I enabled the ranking-by-metric and the pull indexing feature
	 *     AND I collected the following usage data for the main metric:
	 *           article 1: no usage data
	 *           article 2: 10 usage events
	 *           article 3: 15 usage events
	 *           article 4: 30 usage events
	 *    WHEN I access the .../index/lucene/usageMetricBoost endpoint
	 *    THEN I download a text file with boost data normalized by the formula
	 *         2 ^ ((2 * value / max-value) - 1), i.e. the file should be
	 *         indexed by the combination of the installation ID and the article
	 *         ID and contain the following boost values:
	 *           test-inst-1: no entry (i.e. defaults to 0.5 in Solr)
	 *           test-inst-2=0.7937
	 *           test-inst-3=1
	 *           test-inst-4=2
	 *     AND the file is ordered by installation and article ID
	 */
	function testGenerateExternalBoostFile() {
		// Prepare the metrics table.
		// NB: We actually map the Gherkin article IDs to "real" article IDs
		// to make sure that the metrics DAO can handle them.
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$metricsDao->retrieve('TRUNCATE TABLE metrics');
		$records = array(                            // article 1: no record
			array('assoc_id' => 10, 'metric' => 10), // article 2: 10 usage events
			array('assoc_id' => 11, 'metric' => 15), // article 3: 15 usage events
			array('assoc_id' => 12, 'metric' => 30)  // article 4: 30 usage events
		);
		foreach ($records as $record) {
			$record['load_id'] = 'functional test data';
			$record['assoc_type'] = ASSOC_TYPE_ARTICLE;
			$record['day'] = '20130415';
			$record['metric_type'] = 'oas::counter';
			$metricsDao->insertRecord($record);
		}

		// Disable the ranking-by-metric feature.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', false);

		// Check that the boost file is empty.
		$handlerUrl = $this->baseUrl . '/index.php/index/lucene/usageMetricBoost';
		$curlCh = curl_init();
		curl_setopt($curlCh, CURLOPT_URL, $handlerUrl);
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curlCh);
		$this->assertEquals('', $response);

		// Enable the ranking-by-metric and pull indexing feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', true);
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'pullIndexing', true);

		// Check the boost file.
		$response = curl_exec($curlCh);
		$this->assertEquals("test-inst-10=0.7937\ntest-inst-11=1\ntest-inst-12=2\n", $response);
	}

	/**
	 * SCENARIO: update boost file (button)
	 *   GIVEN I enabled the ranking-by-metric feature
	 *    WHEN I open the plugin settings page
	 *    THEN I'll see a button "Update Ranking Data"
	 *
	 * SCENARIO: update boost file (execute)
	 *   GIVEN I enabled the ranking-by-metric feature
	 *     AND I am on the plugin settings page
	 *    WHEN I click on the "Update Ranking Data" button
	 *    THEN current usage statistics will be copied to the index
	 *     AND I'll see the effect immediately in the search results.
	 */
	function testUpdateBoostFile() {
		// Disable the ranking-by-metric feature.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', false);

		// Open the plugin settings page.
		$this->logIn();
		$pluginSettings = $this->baseUrl . '/index.php/lucene-test/manager/plugin/generic/luceneplugin/settings';
		$this->verifyAndOpen($pluginSettings);

		// Check that there is no "Update Ranking Data" button.
		$this->assertElementNotPresent('name=updateBoostFile');

		// Enable the ranking-by-metric feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', true);

		// Refresh the plugin settings page.
		$this->refreshAndWait();

		// Check that the "Update Ranking Data" button is now present.
		$this->waitForElementPresent('name=updateBoostFile');

		// Copy "old" test ranking data to the index.
		$this->copyTestRankingFile();

		// Check that the ranking corresponds to the "old" ranking data.
		$this->checkRanking(array(4, 3, 2, 1));

		// Prepare "new" test ranking data in the metrics table.
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$metricsDao->retrieve('TRUNCATE TABLE metrics');
		$records = array(                            // article 3: no record, defaults to boost 1.0
				array('assoc_id' => 9, 'metric' => 15), // article 1: 10 usage events
				array('assoc_id' => 10, 'metric' => 5), // article 2: 15 usage events
				array('assoc_id' => 12, 'metric' => 30)  // article 4: 30 usage events
		);
		foreach ($records as $record) {
			$record['load_id'] = 'functional test data';
			$record['assoc_type'] = ASSOC_TYPE_ARTICLE;
			$record['day'] = '20130415';
			$record['metric_type'] = 'oas::counter';
			$metricsDao->insertRecord($record);
		}

		// Click the "Update Ranking Data" button.
		$this->verifyAndOpen($pluginSettings);
		$this->waitForElementPresent('name=updateBoostFile');
		$this->clickAndWait('name=updateBoostFile');

		// Check that the new ranking data immediately affects search results.
		$this->checkRanking(array(4, 1, 3, 2));
	}


	/**
	 * SCENARIO: ranking-by-metric effect
	 *   GIVEN I disabled the ranking-by-metric feature
	 *     AND I executed a search that shows four articles with ranking
	 *         weights such that their ranking is uniquely defined as
	 *         1) "article 1", 2) "article 2", 3) "article 3", 4) "article 4"
	 *         [e.g. '+ranking +("article 1"^1.5 "article 2"^1.3 "article 3"^1.1
	 *         "article 4")']
	 *     AND I place a external ranking file into the lucene index folder
	 *         with the following metric boost data:
	 *           article 1: 0.5
	 *           article 2: 1 (no explicit data - via default value)
	 *           article 3: 1.2
	 *           article 4: 2
	 *    WHEN I enable the ranking-by-metric feature
	 *     AND I re-execute the exact same search
	 *    THEN I'll see the ranking order of the articles reversed.
	 */
	function testRankingByMetricEffect() {
		// Activate an external ranking file.
		$this->copyTestRankingFile();

		// Disable the ranking-by-metric feature.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', false);

		// Check the initial ranking.
		$this->checkRanking(array(1, 2, 3, 4));

		// Enable the ranking-by-metric feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', true);

		// Check that the ranking order of the articles was reversed.
		$this->checkRanking(array(4, 3, 2, 1));
	}


	//
	// Private helper methods.
	//
	/**
	 * Copy an external ranking file to the Solr server and
	 * delete the external file cache.
	 */
	private function copyTestRankingFile() {
		// Copy the external ranking test file into the lucene data folder.
		copy(
			dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'external_usageMetric.00000000',
			$this->extFilesDir . 'external_usageMetric.00000000'
		);

		// Make the Lucene server aware of the new file.
		$this->verifyAndOpen('http://localhost:8983/solr/ojs/reloadExternalFiles');
	}

	/**
	 * Check the ranking of four test articles.
	 * @param $expectedRanking array
	 */
	private function checkRanking($expectedRanking) {
		// Execute a search that shows four articles and check that
		// they are presented in the expected order.
		$weightedSearch = '+ranking +("article 1"^1.5 "article 2"^1.3 "article 3" "article 4")';
		$this->simpleSearch($weightedSearch);
		$row = 3; // The first table row containing an article.
		foreach ($expectedRanking as $currentArticle) {
			$articleTitle = $this->getTable("css=table.listing.$row.1");
			self::assertEquals("Ranking Test Article $currentArticle", $articleTitle);
			$row += 3; // One result takes up three table rows.
		}
	}
}

