<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginInstantSearchTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginInstantSearchTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the instant search feature of
 * the lucene plug-in.
 *
 * FEATURE: instant search
 */

import('plugins.generic.lucene.tests.functional.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalLucenePluginInstantSearchTest extends FunctionalLucenePluginBaseTestCase {


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
	// Implement template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$this->enableInstantSearch();
	}


	//
	// Tests
	//
	/**
	 * BACKGROUND:
	 *   GIVEN I enabled the instant search feature
	 */
	private function enableInstantSearch() {
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		// Enable the search feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'instantSearch', true);
	}


	/**
	 * SCENARIO: instant search
	 *   GIVEN I am on the search results page
	 *    WHEN I start entering a search query into the
	 *         search box
	 *    THEN I'll see the result set changing immediately
	 *         to correspond to the query terms I'm entering
	 *         in the search box.
	 */
	public function testInstantSearch() {
		// Open journal context.
		$this->open($this->baseUrl . '/index.php/lucene-test/search/search');

		// Make sure that no results appear.
		$this->waitForElementPresent('css=#results .nodata');

		// Enter 'wings'.
		$searchBox = "//form[@id='searchForm']//input[@id='query_input']";
		$this->typeText($searchBox, 'wings');

		// Check whether the search returned instant results
		// without reloading the page.
		$this->waitForElementNotPresent('css=#results .nodata');
	}
}

