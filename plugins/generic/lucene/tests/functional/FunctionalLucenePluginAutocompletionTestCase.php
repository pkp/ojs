<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginAutocompletionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginAutocompletionTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the auto-completion feature of
 * the lucene plug-in.
 *
 * NB: We are testing the UI-part of the auto-suggest feature here, not the
 * intricacies of faceting vs. suggester behaviour. We use the faceting implementation
 * here so that we can comply with the more complicated scope-specific requirements.
 * Please see the SolrWebServiceTest for such tests.
 *
 * FEATURE: auto-completion
 */

import('plugins.generic.lucene.tests.functional.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalLucenePluginAutocompletionTest extends FunctionalLucenePluginBaseTestCase {

	private $menuPath = '//ul[contains(@style, "display: block")]';


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
		$this->enableAutocompletion();
	}


	//
	// Tests
	//
	/**
	 * BACKGROUND:
	 *   GIVEN I enabled the auto-completion feature
	 */
	private function enableAutocompletion() {
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		// Enable the search feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'autosuggest', true);
		// Use the faceting implementation so that our scope tests will work.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'autosuggestType', SOLR_AUTOSUGGEST_FACETING);
	}


	/**
	 * SCENARIO OUTLINE: auto-completion for simple search
	 *   GIVEN I am on a page in {search scope}
	 *    WHEN I enter select a {search field}
	 *     AND I enter {letters} into the simple search field
	 *    THEN I'll see an auto-completion drop down with at
	 *         least {proposals} for auto-completion from {search
	 *         scope}
	 *     AND I'll not see proposals that are {not in scope}.
	 *
	 * EXAMPLES:
	 *   search scope | search field | letters | proposals                     | not in scope
	 *   =============================================================================================
	 *   all journals | query        | ar      | article, arthur, are, artikel |
	 *   lucene-test  | query        | Ar      | Article, Arthur, Are          | artikel
	 *   lucene-test  | authors      | ar      | arthur                        | article, artikel, are
	 */
	public function testAutocompletionForSimpleSearch() {
		$examples = array(
			array('index', 'query', 'ar', array('article', 'arthur', 'are', 'artikel')),
			array('lucene-test', 'query', 'Ar', array('Article', 'Arthur', 'Are')),
			array('lucene-test', 'authors', 'ar', array('arthur'))
		);

		foreach($examples as $example) {
			// Read the example.
			list($searchScope, $searchField, $letters, $proposals) = $example;

			// Open home page.
			$homePage = $this->baseUrl . '/index.php/' . $searchScope;
			$this->verifyAndOpen($homePage);

			// Select the search field.
			$this->select('searchField', "value=$searchField");

			// Enter the letters.
			$searchBox = $this->simpleSearchForm . 'input[@id="simpleQuery_input"]';
			$this->typeText($searchBox, $letters);

			try {
				// Check whether the expected proposals appear.
				// NB: The following command implicitly checks that out-of-scope
				// proposals are not contained in the concatenated string.
				$this->waitForText($this->menuPath, 'exact:' . implode('', $proposals));
			} catch(Exception $e) {
				throw $this->improveException($e, "example $searchScope, $searchField: " . implode(',', $proposals));
			}
		}
	}


	/**
	 * SCENARIO OUTLINE: auto-completion for advanced search
	 *   GIVEN I am on the advanced search page in {search scope}
	 *         context
	 *    WHEN I enter a {letter} combination into a {search field}
	 *    THEN I'll see an auto-completion drop down with at
	 *         least {proposals} for auto-completion from {search
	 *         scope}
	 *     AND I'll not see proposals that are {not in scope}.
	 *
	 * EXAMPLES:
	 *   search scope | search field   | letter | proposals                           | not in scope
	 *   ===========================================================================================
	 *   all journals | all categories | te     | test, tester, tests, testen, tester |
	 *   lucene-test  | all categories | te     | test, tests                         | tester
	 *   lucene-test  | authors        | au     | author, authorname                  |
	 *   all journals | title          | te     | test, testartikel                   |
	 *   lucene-test  | title          | te     | test                                | testartikel
	 *   lucene-test  | full text      | nu     | nutella                             |
	 *   lucene-test  | suppl. files   | ma     | mango                               |
	 *   lucene-test  | discipline     | die    | dietary                             |
	 *   all journals | keywords       | top    | topology, topologische              |
	 *   lucene-test  | keywords       | lun    | lunch, lunchtime                    |
	 *   lucene-test  | type           | ex     | experience                          | exotic
	 *   lucene-test  | coverage       | ce     | century                             | chicken
	 */
	public function testAutocompletionForAdvancedSearch() {
		// Examples w/o explicit out-of-scope test which will be implicit, see
		// comment below in the code.
		$examples = array(
			array('index', 'query', 'te', array('test', 'tests', 'testartikel', 'testen', 'tester')),
			array('lucene-test', 'query', 'te', array('test', 'tests')),
			array('lucene-test', 'authors', 'au', array('author', 'authorname')),
			array('index', 'title', 'te', array('test', 'testartikel')),
			array('lucene-test', 'title', 'te', array('test')),
			array('lucene-test', 'galleyFullText', 'nu', array('nutella')),
			array('lucene-test', 'discipline', 'die', array('dietary')),
			array('index', 'subject', 'top', array('topological', 'topologische')),
			array('lucene-test', 'subject', 'lun', array('lunch', 'lunchtime')),
			array('lucene-test', 'type', 'ex', array('experience')),
			array('lucene-test', 'coverage', 'ce', array('century'))
		);

		foreach($examples as $example) {
			// Read the example.
			list($searchScope, $searchField, $letters, $proposals) = $example;

			// Open home page.
			$homePage = $this->baseUrl . '/index.php/' . $searchScope . '/search/search';
			$this->open($homePage);

			// Open the advanced search fields.
			if ($searchField != 'query') {
				$this->click('css=#emptyFilters .toggleExtras-inactive');
				$this->waitForElementPresent('//div[@class="extrasContainer" and contains(@style, "display: block")]');
			}

			// Enter the letters.
			$searchBox = "//form[@id='searchForm']//input[@id='${searchField}_input']";
			$this->typeText($searchBox, $letters);

			try {
				// Check whether the expected proposals appear.
				// NB: The following command implicitly checks that out-of-scope
				// proposals are not contained in the concatenated string.
				$this->waitForText($this->menuPath, 'exact:' . implode('', $proposals));
			} catch(Exception $e) {
				throw $this->improveException($e, "example $searchScope, $searchField: " . implode(',', $proposals));
			}
		}
	}


	/**
	 * SCENARIO OUTLINE: pre-filtered auto-suggestion
	 *   GIVEN I am on the search page in journal scope
	 *     AND I have entered {authorname} in the authors box
	 *    WHEN I enter type 'lu' into the main search box
	 *    THEN I'll see a pre-filtered auto-completion drop down with
	 *         {proposals} for auto-completion
	 *     AND I'll not see proposals that are {not in scope}.
	 *
	 * EXAMPLES:
	 *   authorname | proposals                | not in scope
	 *   ========================================================
	 *   authorname | lucene                   | lunch, lunchtime
	 *   author     | lucene, lunch, lunchtime |
	 */
	public function testPrefilteredAutosuggestion() {
		$examples = array(
			array('authorname', array('lucene')),
			array('author', array('lucene', 'lunch', 'lunchtime'))
		);

		$mainBox = "//form[@id='searchForm']//input[@id='query_input']";
		$authorBoxInput = "//form[@id='searchForm']//input[@id='authors_input']";
		$authorBox = "//form[@id='searchForm']//input[@id='authors']";
		foreach($examples as $example) {
			// Read the example.
			list($authorname, $proposals) = $example;

			// Open journal context.
			$this->open($this->baseUrl . '/index.php/lucene-test/search/search');

			// Open the advanced search fields and enter the author.
			$this->click('css=#emptyFilters .toggleExtras-inactive');
			$this->waitForElementPresent('//div[@class="extrasContainer" and contains(@style, "display: block")]');
			$this->type($authorBoxInput, $authorname);
			$this->type($authorBox, $authorname);
			sleep(1);

			// Type the letters 'lu' into the main search box.
			$this->typeText($mainBox, 'lu');

			// Wait for the autocomplete drop-down with the expected proposals.
			$this->waitForText($this->menuPath, 'exact:' . implode('', $proposals));
		}
	}


	/**
	 * SCENARIO: auto-completion selection
	 *   GIVEN I have entered the letter combination 'win' in
	 *         journal context
	 *     AND I am seeing an auto-completion drop down with
	 *         'wings' as its first entry
	 *    WHEN I press the 'down arrow' and then the 'enter' key
	 *    THEN the word 'chicken' will automatically be
	 *         entered into the search field
	 */
	public function testAutocompletionSelection() {
		// Open journal context.
		$this->open($this->baseUrl . '/index.php/lucene-test/search/search');

		// Enter 'win'.
		$searchBox = "//form[@id='searchForm']//input[@id='query_input']";
		$this->typeText($searchBox, 'win');

		// Wait for the autocomplete drop-down with the 'wings' entry.
		$this->waitForText($this->menuPath, 'wings');

		// Simpulate pressing 'Enter'.
		$this->keyDown($searchBox, 40);
		$this->keyPress($this->menuPath . '//a', 13);

		// Make sure that the suggestion has been inserted into
		// the input box.
		$this->assertValue($searchBox, 'wings');
	}
}

