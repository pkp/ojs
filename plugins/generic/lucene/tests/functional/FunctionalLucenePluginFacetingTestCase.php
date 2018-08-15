<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginFacetingTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginFacetingTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the "faceting" feature of
 * the lucene plug-in.
 *
 * FEATURE: faceting
 */


import('plugins.generic.lucene.tests.functional.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalLucenePluginFacetingTest extends FunctionalLucenePluginBaseTestCase {

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
		$this->enableFaceting();
	}


	//
	// Tests
	//
	/**
	 * BACKGROUND:
	 *   GIVEN I enabled all facet categories
	 */
	private function enableFaceting() {
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		// Enable all available facet categories.
		foreach($this->getAvailableFacetCategories() as $facetCategory) {
			$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'facetCategory' . ucfirst($facetCategory), true);
		}
	}

	/**
	 * SCENARIO: faceting filter navigation
	 *    WHEN I execute a cross-journal search that produces results
	 *         with articles that contain meta-data for all facet categories
	 *         (e.g. "lucene doi")
	 *    THEN I see a faceting block plugin wich offers the categories
	 *         "Discipline", "Keyword", "Method/Approach",
	 *         "Coverage", "Publication Month", "Journal" and "Author"
	 *     AND I'll see one or more clickable faceting filters
	 *         below each category.
	 *
	 * SCENARIO: disabled categories
	 *   GIVEN I disable one of the keyword categories in the journal
	 *         setup (e.g. 'discipline')
	 *    WHEN I execute a cross-journal search that produces results
	 *         with articles that contain meta-data for all facet categories
	 *         (e.g. "lucene doi")
	 *    THEN I see a faceting block plugin which does not show
	 *         the disabled category.
	 */
	function testFacetingFilterNavigation() {
		// Execute the scenario with all facet categories enabled.
		$this->simpleSearchAcrossJournals('lucene doi');
		$this->assertFacetCategoriesPresent($this->getAvailableFacetCategories());

		// Disable one category.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'facetCategoryDiscipline', false);

		// Repeat the same search query but now the discipline category should
		// be missing.
		$this->simpleSearchAcrossJournals('lucene doi');
		$expectedCategories = array_diff($this->getAvailableFacetCategories(), array('discipline'));
		$this->assertFacetCategoriesPresent($expectedCategories);
	}

	/**
	 * SCENARIO: hide empty categories and non-selective facets
	 *    WHEN I execute a cross-journal search that produces results
	 *         with articles that do not contain meta-data for all facet
	 *         categories or contain the same meta-data for all articles
	 *         in the result set (e.g. "+(ranking some) test")
	 *    THEN I see a faceting block plugin wich offers the categories
	 *         that contain selective facets (e.g. "Discipline" and "Author")
	 *     BUT the categories that contain no choices (e.g. "Keyword",
	 *         "Method/Approach" and "Coverage")
	 *     AND facets that would produce the same result set if selected
	 *         (e.g. the non-selective facet "lucene-test") will not be
	 *         listed.
	 */
	function testHideEmptyCategoriesAndNonSelectiveFacets() {
		// Execute a cross-journal search that produces results
		// with articles that do not contain meta-data for all facet
		// categories or contain the same meta-data for all articles.
		$this->simpleSearchAcrossJournals('+(ranking some) test');

		// Check that the faceting block contains facets for
		// 'discipline' and 'authors'.
		foreach(array('discipline', 'authors') as $facetCategory) {
			$categoryId = $facetCategory . 'Category';
			$this->assertElementPresent("css=#luceneFacets.block #$categoryId");
		}

		// Check that the faceting block contains neither empty facets, i.e.
		// 'subject' (keyword), 'type' (method/approach) and 'coverage',
		// nor non-selective facets, i.e. 'journalTitle' (journal).
		foreach(array('subject', 'type', 'coverage', 'journalTitle') as $facetCategory) {
			$categoryId = $facetCategory . 'Category';
			$this->assertElementNotPresent("css=#luceneFacets.block #$categoryId");
		}
	}


	/**
	 * SCENARIO OUTLINE: only show facets in the UI language
	 *    WHEN I select {locale} as my UI language
	 *     AND I execute a search that produces results with articles
	 *         that contain selective keyword meta-data (e.g. "lucene doi")
	 *    THEN I see a faceting block plugin wich offers {facets}
	 *         only for the selected {locale}
	 *
	 * EXAMPLES:
	 *   locale | facets
	 *   =============================================
	 *   de_DE  | 22A10 generelle gopologische gruppen
	 *   en_US  | 22A10 general topological groups
	 */
	function testOnlyShowFacetsInTheUiLanguage() {
		$testCases = array(
			'de_DE' => '22a10 generelle topologische gruppen',
			'en_US' => '22a10 general topological groups'
		);

		foreach($testCases as $locale => $facet) {
			// Set the locale and execute a search.
			$this->simpleSearchAcrossJournals('lucene doi', $locale);

			// Check that the faceting block offers the expected facet (and no other).
			$availableFacets = $this->getText("css=#luceneFacets.block #subjectCategory a");
			$this->assertContains($facet, $availableFacets);
			foreach($testCases as $otherPossibleFacet) {
				if ($otherPossibleFacet != $facet) {
					$this->assertNotContains($otherPossibleFacet, $availableFacets);
				}
			}
		}
	}


	/**
	 * SCENARIO: empty result set
	 *    WHEN I execute a search query that produces no results
	 *         (e.g. "+nonexistent keyword")
	 *    THEN I do not see a faceting block plugin.
	 */
	function testEmptyResultSet() {
		// Execute a search query that produces no results.
		$this->simpleSearchAcrossJournals('+nonexistent keyword');

		// Check that the faceting block disappears.
		$this->assertElementNotPresent('css=#luceneFacets.block');
	}


	/**
	 * SCENARIO: filtered categories
	 *    WHEN I execute a search query that filters on one of the
	 *         enabled facet categories (e.g. publication date)
	 *    THEN the filtered category will not show up in the
	 *         facets block plugin
	 *     BUT all other categories are still available.
	 */
	function testFilteredCategories() {
		// Execute a search query that filters on a facet category
		// (e.g. publication date).
		$searchPage = $this->baseUrl . '/index.php/index/search?query=lucene doi&dateFromYear=2009';
		$this->open($searchPage);

		// Check that the filtered category will not show up in the
		// facets block plugin while other categories are still available.
		$expectedCategories = array_diff($this->getAvailableFacetCategories(), array('publicationDate'));
		$this->assertFacetCategoriesPresent($expectedCategories);
	}

	/**
	 * SCENARIO OUTLINE: facet filter selection
	 *   GIVEN I executed a search query returning the articles with {id 1}
	 *         and {id 2} (e.g. "lucene doi")
	 *    WHEN I click on {facet filter}
	 *    THEN I'll see a refined result list containing the article
	 *         with {id 1}
	 *     BUT I'll no longer see the article with {id 2} in the result
	 *     AND I'll see the selected facet as a filter above the result set
	 *         with the text "{facet filter}"
	 *     AND the category of {facet filter} no longer appears in the
	 *         facet filter navigation block.
	 *
	 * EXAMPLES:
	 *   id 1 | id 2 | facet filter
	 *   ============================================
	 *   3    | 4    | discipline: "exotic food"
	 *   1    | 3    | keyword: "22a10 general topological groups"
	 *   4    | 3    | method/approach: "personal experience"
	 *   4    | 3    | coverage: "the 21st century"
	 *   3    | 4    | publication date: "2012"
	 *   3    | 1    | journal: "lucene-test"
	 *   3    | 4    | author: "authorname, second a"
	 */
	function testFacetFilterSelection() {
		$testCases = array(
			array(3, 4, 'discipline', 'exotic food'),
			array(1, 3, 'subject', '22a10 general topological groups'), // keyword
			array(4, 3, 'type', 'personal experience'), // method/approach
			array(4, 3, 'coverage', 'the 21st century'),
			array(3, 4, 'publicationDate', '2012'),
			array(3, 1, 'journalTitle', 'lucene-test'),
			array(3, 4, 'authors', 'authorname, second a')
		);

		foreach($testCases as $testCase) {
			// Extract the test case.
			list($id1, $id2, $facetCategory, $facet) = $testCase;

			// Execute a search query returning articles 1, 3 and 4.
			$this->simpleSearchAcrossJournals('lucene doi');

			// Make sure that the expected facet filter is present and
			// click on it.
			$this->assertElementPresent("css=#luceneFacets.block #${facetCategory}Category");
			$facetFilter = ($facetCategory == 'publicationDate' ? 'dateFromYear' : $facetCategory);
			$facetInUrl = str_replace(array(' ', ','), array('%20', '%2C'), $facet);
			if (!in_array($facetCategory, array('publicationDate', 'journalTitle'))) {
				$facetInUrl = '%22' . $facetInUrl . '%22'; // Expect a phrase search!
			}
			$facetLocator = "//div[@id='${facetCategory}Category']//a[contains(@href, '${facetFilter}=${facetInUrl}')]";
			$this->assertElementPresent($facetLocator);
			$this->clickAndWait($facetLocator);

			// Make sure that the refined result contains the article
			// with id 1 but not the article with id 2.
			$this->waitForElementPresent('results');
			$articleLocator = "//div[@id='results']//a[@class='file' and contains(@href, 'article/view/${id1}')]";
			$this->assertElementPresent($articleLocator);
			$articleLocator = "//div[@id='results']//a[@class='file' and contains(@href, 'article/view/${id2}')]";
			$this->assertElementNotPresent($articleLocator);

			// Check that the filter appears in the search form.
			switch($facetCategory) {
				case 'journalTitle':
					$filterLabelLocator = "css=#searchForm label[for='searchJournal']";
					$this->assertElementPresent($filterLabelLocator);
					$filterLocator = "css=#searchForm #searchJournal";
					$this->assertSelected($filterLocator, $facet);
					break;

				case 'publicationDate':
					$filterLabelLocator = "css=#searchForm label[for='dateFrom']";
					$this->assertElementPresent($filterLabelLocator);
					$filterLocator = "css=#searchForm select[name=dateFromYear]";
					$this->assertSelected($filterLocator, $facet);
					break;

				default:
					$filterLabelLocator = "css=#searchForm label[for='${facetFilter}']";
					$this->assertElementPresent($filterLabelLocator);
					$filterLocator = "css=#searchForm #${facetFilter}";
					$this->assertValue($filterLocator, '"' . $facet . '"');
			}

			// Check that the selected category no longer appears
			// in the faceting block.
			$this->assertElementNotPresent("css=#luceneFacets.block #${facetCategory}Category");
		}
	}

	/**
	 * SCENARIO: multiple facet filter selection
	 *   GIVEN I executed a search originally containing the
	 *         articles with the ids 1, 3 and 4
	 *     BUT I selected a publication date facet "2011" so
	 *         that the article with id 3 is no longer in
	 *         the result set
	 *    WHEN I select an additional journal facet "lucene-test"
	 *    THEN I will only see the article with id 4 in the
	 *         remaining result set.
	 *     AND I'll see the selected facets above the result set with the
	 *         text "filtered by: publ. date: ... [X], journal: ... [X]"
	 *     AND I'll no longer see the publ. date or journal categories
	 *         in the facet filter navigation block.
	 *
	 * SCENARIO: facet filter deletion
	 *   GIVEN I executed a faceted search with a publication date
	 *         and a journal facet
	 *     AND the result set contains the article with id 4 but
	 *         not articles 1 or 3
	 *    WHEN I click the "Delete"-buttons near to the publication date
	 *         filters above the result list
	 *    THEN I'll see articles 3 and 4 in the result set
	 *     AND I'll see the publication date category in the filter
	 *         navigation block.
	 */
	function testMultipleFacetFilterSelection() {
		// Execute a search query returning articles 1, 3 and 4.
		$this->simpleSearchAcrossJournals('lucene doi');

		// Select a publication date facet "2011".
		$facetLocator = "//div[@id='publicationDateCategory']//a[contains(@href, 'dateFromYear=2011')]";
		$this->assertElementPresent($facetLocator);
		$this->clickAndWait($facetLocator);

		// Select the additional "lucene-test" journal filter.
		$facetLocator = "//div[@id='journalTitleCategory']//a[contains(@href, 'journalTitle=lucene-test')]";
		$this->assertElementPresent($facetLocator);
		$this->clickAndWait($facetLocator);

		// Make sure that the refined result contains the article
		// 4 but not 1 or 3.
		$this->waitForElementPresent('results');
		$this->assertElementNotPresent("//div[@id='results']//a[@class='file' and contains(@href, 'article/view/1')]");
		$this->assertElementNotPresent("//div[@id='results']//a[@class='file' and contains(@href, 'article/view/3')]");
		$this->assertElementPresent("//div[@id='results']//a[@class='file' and contains(@href, 'article/view/4')]");

		// Check that the filters appears in the search form.
		$filterLabelLocator = "css=#searchForm label[for='searchJournal']";
		$this->assertElementPresent($filterLabelLocator);
		$filterLocator = "css=#searchForm #searchJournal";
		$this->assertSelected($filterLocator, 'lucene-test');
		$filterLabelLocator = "css=#searchForm label[for='dateFrom']";
		$this->assertElementPresent($filterLabelLocator);
		$filterLocator = "css=#searchForm select[name=dateFromYear]";
		$this->assertSelected($filterLocator, '2011');

		// Check that the selected categories no longer appears
		// in the faceting block.
		$this->assertElementNotPresent("css=#luceneFacets.block #publicationDateCategory");
		$this->assertElementNotPresent("css=#luceneFacets.block #journalTitleCategory");

		// Delete the publication date filters.
		$this->clickAndWait('link=Delete');
		$this->clickAndWait('link=Delete');

		// Make sure that the refined result contains the article
		// 3 and 4 but not 1.
		$this->waitForElementPresent('results');
		$this->assertElementNotPresent("//div[@id='results']//a[@class='file' and contains(@href, 'article/view/1')]");
		$this->assertElementPresent("//div[@id='results']//a[@class='file' and contains(@href, 'article/view/3')]");
		$this->assertElementPresent("//div[@id='results']//a[@class='file' and contains(@href, 'article/view/4')]");

		// The publication date facet category should be visible again.
		$this->assertElementPresent("css=#luceneFacets.block #publicationDateCategory");
		$this->assertElementNotPresent("css=#luceneFacets.block #journalTitleCategory");
	}


	//
	// Private helper methods
	//
	/**
	 * Return all available facet categories.
	 */
	private function getAvailableFacetCategories() {
		return array(
			'discipline', 'subject', 'type', 'coverage',
			'journalTitle', 'authors', 'publicationDate'
		);
	}

	/**
	 * Assert that the given categories are present after executing
	 * a search query with results that contain meta-data for all
	 * facet categories.
	 * @param $expectedCategories array
	 */
	private function assertFacetCategoriesPresent($expectedCategories) {
		// Check that the faceting block appears.
		$this->assertElementPresent('css=#luceneFacets.block');

		// Check that the faceting block contains the expected
		// facet categories.
		foreach($expectedCategories as $facetCategory) {
			$categoryId = $facetCategory . 'Category';
			$this->assertElementPresent("css=#luceneFacets.block #$categoryId");

			// Check that there is a list of one or more clickable faceting
			// filters below each category.
			$this->assertElementPresent("css=#luceneFacets.block #$categoryId ul li a");
		}

		// Check that the faceting block does not contain any other
		// facet categories.
		$availableCategories = $this->getAvailableFacetCategories();
		$missingCategories = array_diff($availableCategories, $expectedCategories);
		foreach($missingCategories as $facetCategory) {
			$categoryId = $facetCategory . 'Category';
			$this->assertElementNotPresent("css=#luceneFacets.block #$categoryId");
		}
	}
}

