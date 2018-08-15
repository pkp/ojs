<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginSimDocTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginSimDocTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the "similar documents" feature of
 * the lucene plug-in.
 *
 * FEATURE: similar documents
 */

import('plugins.generic.lucene.tests.functional.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalLucenePluginSimDocTest extends FunctionalLucenePluginBaseTestCase {

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
	 * BACKGROUND:
	 *   GIVEN I enabled the "similar documents" feature
	 *
	 * SCENARIO: propose similar documents
	 *    WHEN I execute a simple search that returns at
	 *         least one result
	 *    THEN The result list will contain a button behind
	 *         each item of the result list: "similar documents"
	 *
	 * SCENARIO: find similar documents
	 *   GIVEN I executed a simple search that returned at
	 *         least one result
	 *     AND I see a "similar documents" button behind each item
	 *         of the result list
	 *    WHEN I click the "similar documents" button of an item
	 *    THEN I'll see a result set containing articles containing
	 *         similar keywords as defined by solr's default
	 *         similarity algorithm.
	 */
	public function testSimilarDocuments() {
		// Enable the "similar documents" feature.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'simdocs', true);

		// Execute a simple search that returns at least one result.
		$this->simpleSearch('lucene');

		// Check that the link "similar documents" is present.
		$this->assertElementPresent('link=similar documents');

		// Click the "similar documents" button.
		$this->clickAndWait('link=similar documents');

		// Check that a search for similar articles has been executed.
		$this->waitForLocation('*lucene-test/search/search*');
		$this->waitForElementPresent('name=query');
		$this->assertValue('name=query', '*article*');
		$this->assertValue('name=query', '*test*');
	}
}

