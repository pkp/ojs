<?php

/**
 * @file tests/functional/plugins/block/mostRead/FunctionalMostReadBlockPluginTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalMostReadBlockPluginTest
 * @ingroup tests_functional_plugins_block_mostRead
 * @see MostReadBlockPlugin
 *
 * @brief Integration/Functional test for the "most read" block plugin.
 *
 * FEATURE: most read articles
 */


import('lib.pkp.tests.WebTestCase');
import('lib.pkp.classes.plugins.BlockPlugin');

class FunctionalMostReadBlockPluginTest extends WebTestCase {

	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('plugin_settings', 'metrics');
	}


	//
	// Implement template methods from PKPTestCase
	//
	/**
	 * BACKGROUND:
	 *   GIVEN I enabled the "most read articles" block plugin
	 *     AND I enabled a metric providing plugin.
	 *
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() : void {
		parent::setUp();
		// Enable the "most read articles" plugin.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(2, 'MostReadBlockPlugin', 'enabled', true);
		$pluginSettingsDao->updateSetting(2, 'MostReadBlockPlugin', 'seq', 1);
		
		// Enable a metric-providing plugin.
		$pluginSettingsDao->updateSetting(0, 'OasPlugin', 'enabled', true);
		
		// Generate test metric data.
		$dates = array(
			date('Ymd', strtotime('-15 days')),
			date('Ymd', strtotime('-45 days')),
			date('Ymd', strtotime('-2 years'))
		);
		$record = array('load_id' => 'most-read-test', 'assoc_type' => ASSOC_TYPE_GALLEY, 'metric_type' => 'oas::counter');
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		foreach($dates as $date) {
			$record['day'] = $date;
			foreach(array('4' => 5, '6' => 10) as $assocId => $metric) {
				$record['assoc_id'] = $assocId;
				$record['metric'] = $metric;
				$metricsDao->insertRecord($record);
			}
		}
	}



	//
	// Tests
	//
	/**
	 * SCENARIO: display most-read articles of a journal
	 *    WHEN I display a journal page
	 *    THEN I see a block plugin which lists the articles
	 *         ranking highest for the selected "main metric"
	 *         throughout the journal
	 *     AND I'll see the articles as title links with the
	 *         metric value in parentheses
	 *     AND the metric values will correspond to the
	 *         aggregate values of the last month by default.
	 */
	function testMostReadArticleList() {
		// Display a journal page.
		$this->verifyAndOpen($this->baseUrl . '/index.php/lucene-test');
		
		// Check that I see the block plugin.
		$this->assertText('css=#sidebarMostRead .title', 'Most-Read Articles');
		
		// Check that I see the articles:
		// - correctly ordered
		// - with the monthly values as default
		// - as title links
		$text = str_replace(array("\n", "<br />"), array('', ''), $this->getText('css=.mostReadArticleReport.selected'));
		$this->assertEquals("Previous Month: Lucene Test Article 2 (10) Lucene Test Article 1 (5)", $text);
		$this->clickAndWait('link=Lucene Test Article 1');
		$this->assertLocation($this->baseUrl . '/index.php/lucene-test/article/view/3');
	}
	
	/**
	 * SCENARIO OUTLINE:
	 *    WHEN I see the "most-read articles" block plugin
	 *     AND I select a {time span} from the drop down
	 *         in the plugin
	 *    THEN I'll see the {metric values} for the selected
	 *         {time span}.
	 *
	 * EXAMPLES:
	 *   time span         | metric values
	 *   ==================|==============================
	 *   Previous Month    | article 1: 10, article 2: 5
	 *   Previous Year     | article 1: 20, article 2: 10
	 *   All Times         | article 1: 30, article 2: 15
	 */
	function testSelectTimeSpan() {
		// Display the most-read articles plugin.
		$this->verifyAndOpen($this->baseUrl . '/index.php/lucene-test');

		$tests = array(
			'Previous Year' => array(20, 10),
			'All Times' => array(30, 15),
			'Previous Month' => array(10, 5)
		);
		foreach ($tests as $timeSpan => $metrics) {
			$this->select('timeSpans', $timeSpan);
			$text = str_replace(array("\n", "<br />"), array('', ''), $this->getText('css=.mostReadArticleReport.selected'));
			$this->assertEquals("$timeSpan: Lucene Test Article 2 ($metrics[0]) Lucene Test Article 1 ($metrics[1])", $text);
		}
	}
}

