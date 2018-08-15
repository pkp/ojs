<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginHighlightingTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginHighlightingTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the "highlighting" feature of
 * the lucene plug-in.
 *
 * FEATURE: highlighting
 */

import('plugins.generic.lucene.tests.functionalFunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalLucenePluginHighlightingTest extends FunctionalLucenePluginBaseTestCase {

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
	 *   GIVEN I enabled the highlighting feature
	 *
	 * SCENARIO: highlighting
	 *    WHEN I execute a simple search that returns at
	 *         least one result
	 *    THEN I'll see a short excerpt of each article's abstract
	 *         or full text containing my search keywords
	 *     AND my search keywords are visually emphasized.
	 */
	public function testHighlighting() {
		// Enable the "highlighting" feature.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'highligthing', true);

		// Execute a simple search that returns at least one result.
		$this->simpleSearch('abstract');

		// Check whether we get highlighting from the abstract.
		$this->assertText('css=.plugins_generic_lucene_highlighting', '*abstract*');

		// Check whether we get highlighting from the full text, too.
		$this->simpleSearch('nutella');
		$this->assertText(
			'css=.plugins_generic_lucene_highlighting',
			'"... This is a galley used for solr indexing tests. Keywords in this galley are: nutella. ..."'
		);

		// Check that the keyword is visually emphasized.
		$this->assertText(
			'css=.plugins_generic_lucene_highlighting em',
			'exact:nutella'
		);
	}
}

