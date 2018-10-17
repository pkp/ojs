<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginSpellingTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginSpellingTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the "alternative spellings" feature of
 * the lucene plug-in.
 *
 * FEATURE: alternative spelling suggestions
 */

import('plugins.generic.lucene.tests.functional.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalLucenePluginSpellingTest extends FunctionalLucenePluginBaseTestCase {

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
		$this->enableSpellcheck();
	}


	//
	// Tests
	//
	/**
	 * BACKGROUND:
	 *   GIVEN I enabled the alternative spelling feature
	 */
	private function enableSpellcheck() {
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		// Enable the alternative spelling feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'spellcheck', true);
	}


	/**
	 * SCENARIO: alternative spelling proposal
	 *    WHEN I set the UI locale to "German"
	 *     AND I execute a simple search with the search
	 *         phrase "tEsts UND Nutela"
	 *    THEN I'll see an additional link "Meinten Sie:
	 *         'tests UND nutella'" above the result list.
	 *
	 * SCENARIO: alternative spelling search
	 *   GIVEN I have executed a simple search with the
	 *         search phrase "tEsts UND Nutela"
	 *     AND I see a link "Meinten Sie: 'test UND nutella'"
	 *         above the result list
	 *    WHEN I click this link
	 *    THEN I'll see the result set corresponding to the
	 *         search phrase "test UND nutella".
	 */
	public function testAlternativeSpelling() {
		// Execute a simple search. It's important that we check
		// a foreign locale to see whether search keywords will
		// be correctly translated back to the UI language.
		$this->simpleSearch('tEsts UND Nutela', 'query', array(), array(), 'de_DE');

		// Check the additional spelling suggestion.
		$this->assertText('css=.plugins_generic_lucene_preResults_spelling', 'Meinten Sie: test UND nutella');
		$this->assertText('css=.plugins_generic_lucene_preResults_spelling a', 'test UND nutella');

		// Click the link.
		$this->clickAndWait('css=.plugins_generic_lucene_preResults_spelling a');

		// Make sure that the correct query has been executed.
		$this->assertValue('query', 'test UND nutella');
	}


	/**
	 * SCENARIO: alternative spelling proposal (non-default field)
	 *    WHEN I select the "Authors" search field
	 *     AND I execute a simple search with the search
	 *         phrase "autor"
	 *    THEN I'll see an additional link "Did you mean:
	 *         'author'" above the result list.
	 *
	 * SCENARIO: alternative spelling search (non-default field)
	 *   GIVEN I have executed a simple search on the "Authors"
	 *         search field with the search phrase "author"
	 *     AND I see a link "Did you mean: 'author'"
	 *         above the result list
	 *    WHEN I click this link
	 *    THEN I'll see the result set corresponding to the
	 *         search phrase "author" within an advanced
	 *         author filter.
	 */
	public function testAlternativeSpellingWithNonDefaultField() {
		// Execute a simple search. It's important that we check
		// a foreign locale to see whether search keywords will
		// be correctly translated back to the UI language.
		$this->simpleSearch('autor', 'authors');

		// Check the additional spelling suggestion.
		$this->assertText('css=.plugins_generic_lucene_preResults_spelling', 'Did you mean: author');
		$this->assertText('css=.plugins_generic_lucene_preResults_spelling a', 'author');

		// Click the link.
		$this->clickAndWait('css=.plugins_generic_lucene_preResults_spelling a');

		// Make sure that the correct query has been executed.
		$this->assertValue('query', '');
		$this->assertValue('authors', 'author');
	}
}

