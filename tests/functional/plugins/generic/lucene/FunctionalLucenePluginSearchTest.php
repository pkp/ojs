<?php

/**
 * @file tests/functional/plugins/lucene/FunctionalLucenePluginSearchTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginSearchTest
 * @ingroup tests_functional_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies.
 */


import('tests.functional.plugins.generic.lucene.FunctionalLucenePluginBaseTestCase');

class FunctionalLucenePluginSearchTest extends FunctionalLucenePluginBaseTestCase {

	/**
	 * SCENARIO: Search across all journals of an installation
	 *   GIVEN I am on an OJS installation's home page
	 *     AND the OJS installation contains at least two
	 *         journals
	 *     AND at least two journals contain an article with
	 *         the word "test" in its title
	 *    WHEN I enter the word "test" into the simple
	 *         search box
	 *     AND I click the "Search" button
	 *    THEN I receive a search result with articles from
	 *         both journals in the result set.
	 */
	public function testSearchAcrossJournals() {
		// Open the test installation's home page.
		// The test installation contains at least
		// two journals with articles that contain
		// the word "test" in their title.
		$homePage = $this->baseUrl . '/index.php';
		$this->verifyAndOpen($homePage);

		// Enter the word "test" into the simple search box.
		$this->type($this->simpleSearchForm . 'input[@id="query"]', 'test');

		// Click the "Search" button.
		$this->clickAndWait($this->simpleSearchForm . 'input[@type="submit"]');

		// Make sure that the search result contains articles
		// from both test journals.
		$this->assertElementPresent('//table[@class="listing"]//a[contains(@href, "index.php/lucene-test")]');
		$this->assertElementPresent('//table[@class="listing"]//a[contains(@href, "index.php/test")]');
	}


	/**
	 * SCENARIO OUTLINE: Simple search
	 *   GIVEN I am on an OJS journal's home page
	 *     AND this journal contains an article with
	 *         id {id} and the keyword {keyword} in
	 *         its {search field}
	 *    WHEN I enter the {keyword} into the simple
	 *         search box
	 *     AND I select the {search field} to search in
	 *     AND I click the "Search" button
	 *    THEN I receive a search result with the article
	 *         id {id} in the result set.
	 *
	 * EXAMPLES:
	 *   search field | keyword              | id
	 *   =========================================
	 *   all fields   | Pizza                | 3
	 *   author       | Author               | 3
	 *   title        | Article              | 3
	 *   abstract     | "Article 2 Abstract" | 4
	 *   index terms  | Food                 | 3
	 *   full text    | Nutella              | 3
	 */
	public function testSimpleSearch() {
		// Set up the examples.
		$examples = array(
			array('', 'Pizza', 3),
			array(ARTICLE_SEARCH_AUTHOR, 'Author', 3),
			array(ARTICLE_SEARCH_TITLE, 'Article', 3),
			array(ARTICLE_SEARCH_ABSTRACT, '"Article 2 Abstract"', 4),
			array(ARTICLE_SEARCH_INDEX_TERMS, 'Food', 3),
			array(ARTICLE_SEARCH_GALLEY_FILE, 'Nutella', 3),
		);

		foreach ($examples as $example) {
			// Read the example.
			list($searchField, $keyword, $id) = $example;

			// Execute a simple search.
			$this->simpleSearch($keyword, $id, $searchField);
		}
	}


	/**
	 * SCENARIO OUTLINE: Advanced search
	 *   GIVEN I am on an OJS journal's search page
	 *     AND the journal contains an article with
	 *         id {id} and the keyword {keyword} in
	 *         its {search field}
	 *    WHEN I enter the word {keyword} into the advanced
	 *         search input field {search field}
	 *     AND I click the "Search" button
	 *    THEN I receive a search result with the article
	 *         id {id} in the result set.
	 *
	 * EXAMPLES:
	 *   search field | keyword          | id
	 *   ====================================
	 *   all fields   | Mango            | 3
	 *   author       | Another          | 4
	 *   title        | Lucene Test      | 4
	 *   full text    | chicken feet     | 3
	 *   suppl. files | Pizza            | 3
	 *   date from/to | * / today        | 4
	 *   discipline   | dietary research | 4
	 *   subject      | lunchtime        | 4
	 *   type         | personal         | 4
	 *   coverage     | the 21st century | 4
	 */
	public function testAdvancedSearch() {
		// Set up the examples.
		$examples = array(
			array('advancedQuery', 'Mango', 3),
			array('author', 'Another', 4),
			array('title', 'Lucene Test', 4),
			array('fullText', 'chicken feet', 3),
			array('supplementaryFiles', 'Pizza', 3),
			array('date', '--- see code below ---', 4),
			array('discipline', 'dietary research', 4),
			array('subject', 'lunchtime', 4),
			array('type', 'personal', 4),
			array('coverage', 'the 21st century', 4),
		);

		$searchPage = $this->baseUrl . '/index.php/lucene-test/search';
		foreach ($examples as $example) {
			// Read the example.
			list($searchField, $keyword, $id) = $example;

			try {
				// Open the "lucene-test" journal search page.
				$this->verifyAndOpen($searchPage);

				// Enter the keyword into the advanced search field.
				if ($searchField == 'date') {
					$this->type('advancedQuery', 'test');
					$this->select('dateToDay', 'value=20');
					$this->select('dateToMonth', 'value=07');
					$this->select('dateToYear', 'value=2012');
				} else {
					$this->type($searchField, $keyword);
				}

				// Click the "Search" button.
				$this->clickAndWait('css=.defaultButton');

				// Check whether the result set contains the
				// sample article.
				$this->assertElementPresent('//table[@class="listing"]//a[contains(@href, "index.php/lucene-test/article/view/' . $id . '")]');
			} catch(Exception $e) {
				throw $this->improveException($e, "example $searchField: $keyword");
			}
		}
	}


	/**
	 * SCENARIO OUTLINE: Search syntax
	 *   GIVEN I am on an OJS journal's home page
	 *     AND the journal contains an article A
	 *         with the exact phrase "chicken have wings"
	 *         in its title
	 *     AND the journal contains an article B
	 *         with the words "chicken" and "eggs"
	 *         in its title
	 *     AND the journal contains an article C
	 *         with the words "wings" and "eggs"
	 *         in its title
	 *     AND the journal contains an article D
	 *         with the words "chickenwings" and "eggs"
	 *         in its title
	 *     AND I select the "title" search field
	 *    WHEN I set the current {GUI locale}
	 *     AND I enter a {search phrase}
	 *         into the simple search box
	 *     AND I click the "Search" button
	 *    THEN I receive a result set that contains {article}
	 *         but {not article}.
	 *
	 * EXAMPLES:
	 *   GUI locale | search phrase                | article | not article
	 *   =================================================================
	 *   en_US      | chicken wings                | A       | B, C, D       // implicit AND
	 *   en_US      | chicken AND wings            | A       | B, C, D       // explicit conjunction
	 *   en_US      | chicken OR wings             | A, B, C | D             // disjunction
	 *   en_US      | chicken NOT wings            | B       | A, C, D       // negation
	 *   en_US      | ((wings OR egg) NOT chicken) | C, D    | A, B          // bracketed search phrase
	 *   en_US      | chicken NICHT wings          |         | A, B, C, D    // search syntax localization
	 *   de_DE      | chicken NICHT wings          | B       | A, C, D       //      - " -
	 *   en_US      | "chicken wings"              |         | A, B, C, D    // phrase search
	 *   en_US      | "chicken have wings"         | A       | B, C, D       //      - " -
	 *   en_US      | chicken*                     | A, B, D | C             // wildcard search
	 *   en_US      | ChiCkeN Wings                | A       | B, C, D       // case insensitive search
	 */
	public function testSearchSyntax() {
		// Set up the examples.
		$examples = array(
			array('en_US', 'chicken wings', 'A', 'BCD'),
			array('en_US', 'chicken AND wings', 'A', 'BCD'),
			array('en_US', 'chicken OR wings', 'ABC', 'D'),
			array('en_US', 'chicken NOT wings', 'B', 'ACD'),
			array('en_US', '((wings OR egg) NOT chicken)', 'CD', 'AB'),
			array('en_US', 'chicken NICHT wings', '', 'ABCD'),
			array('de_DE', 'chicken NICHT wings', 'B', 'ACD'),
			array('en_US', '"chicken wings"', '', 'ABCD'),
			array('en_US', '"chicken have wings"', 'A', 'BCD'),
			array('en_US', 'chicken*', 'ABD', 'C'),
			array('en_US', 'ChiCkeN Wings', 'A', 'BCD')
		);

		// Assign article letters to ids.
		$articleIds = array('A' => 5, 'B' => 6, 'C' => 7, 'D' => 8);

		foreach ($examples as $example) {
			// Read the example.
			list($locale, $searchPhrase, $articles, $notArticles) = $example;

			// Translate letters representing articles to article ids.
			$ids = array();
			if (!empty($articles)) {
				foreach(str_split($articles) as $article) {
					$ids[] = $articleIds[$article];
				}
			}
			$notIds = array();
			if (!empty($notArticles)) {
				foreach(str_split($notArticles) as $article) {
					$notIds[] = $articleIds[$article];
				}
			}

			// Execute a simple search.
			$this->simpleSearch($searchPhrase, $ids, ARTICLE_SEARCH_TITLE, $notIds, $locale);
		}
	}


	/**
	 * SCENARIO OUTLINE: Multilingual search
	 *   GIVEN I am on an OJS journal's home page
	 *     AND the journal contains an article with the word
	 *         "chicken" in its "en_US" title
	 *     AND the same article has the word "Hühnchen" in
	 *         its "de_DE" title
	 *    WHEN I enter {search phrase} into the simple
	 *         search box
	 *     AND I click the "Search" button
	 *    THEN I receive a search result with this article
	 *         in its result set.
	 *
	 * EXAMPLES:
	 *   search phrase
	 *   =============
	 *   chicken
	 *   Hühnchen
	 */
	public function testMultilingualSearch() {
		// Set up the examples.
		$examples = array('chicken', 'Hühnchen');
		foreach ($examples as $keyword) {
			// Execute a simple search.
			$this->simpleSearch($keyword, 5, ARTICLE_SEARCH_TITLE);
		}
	}
}
?>