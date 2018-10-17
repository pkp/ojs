<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginRankingTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginRankingTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies (result set pagination, ordering and ranking).
 */

import('plugins.generic.lucene.tests.functional.FunctionalLucenePluginBaseTestCase');

class FunctionalLucenePluginRankingTest extends FunctionalLucenePluginBaseTestCase {

	/**
	 * SCENARIO: Pagination: page links
	 *   GIVEN I have executed a search that returns more than
	 *         25 articles in its result set
	 *     AND I am looking at the result page
	 *    THEN I see a set of paging links below the result set
	 *
	 * SCENARIO: Pagination: turn page
	 *   GIVEN I have executed a search that returns more than
	 *         25 articles in its result set
	 *     AND I am looking at the result page
	 *    WHEN I click on one of the paging links below the result set
	 *    THEN I will see a different page of the same result list
	 */
	public function testPagination() {
		// NB: The following code has been used to generate
		// test articles for ranking.
		//
		//  for($a = 1; $a <= 30; $a ++) {
		//  	$articleId = $this->submitArticle("Ranking Test Article $a");
		//  	$this->publishArticle($articleId);
		//  }
		//
		// We keep those articles in the testserver dump, so we do not
		// have to re-generate them for every test.

		//
		// Test page links
		//
		$pageLinks = 'css=table.listing tr:last-child td:last-child';

		// Execute a search that returns all 30 ranking test
		// articles and open the result page.
		$this->simpleSearch('ranking test article');

		// Check the set of paging links below the result set.
		$this->assertText($pageLinks, 'regexp:^1 2 > >>');

		//
		// Test turning the page
		//
		// Click the link for the next page.
		$this->clickAndWait('link=>');

		// Check the set of paging links of the second page.
		$this->assertText($pageLinks, 'regexp:^<< < 1 2');
	}


	/**
	 * SCENARIO OUTLINE: Result ordering
	 *   GIVEN I am looking at the result page of a {search type}-journal
	 *         result set for the search phrase {keywords}
	 *    WHEN I select {order criterium} and {order direction}
	 *    THEN I will see a different result list re-ordered by the
	 *         changed criterium and in the given direction. This
	 *         can be seen by looking at the first {article id} in
	 *         the result set.
	 *
	 * EXAMPLES:
	 *   search type | keywords                    | order criterium  | order direction      | article id
	 *   ================================================================================================
	 *   single      | chicken AND (wings OR feet) | relevance        | descending (default) | 5
	 *   single      | chicken AND (wings OR feet) | relevance        | ascending            | 4
	 *   single      | chicken AND (wings OR feet) | author           | ascending (default)  | 4
	 *   single      | chicken AND (wings OR feet) | author           | descending           | 5
	 *   single      | chicken AND (wings OR feet) | publication date | descending (default) | 5
	 *   single      | chicken AND (wings OR feet) | article title    | ascending (default)  | 5
	 *   single      | chicken AND (wings OR feet) | article title    | descending           | 4
	 *   multi       | test NOT ranking            | issue publ. date | descending (default) | 3
	 *   multi       | test NOT ranking            | journal title    | ascending (default)  | 3
	 *   multi       | test NOT ranking            | journal title    | descending           | 1
	 *
	 * SCENARIO: Journal title ordering: single-journal search
	 *    WHEN I am doing a single-journal search
	 *    THEN I will not be able to order the result
	 *         set by journal title.
	 *
	 * SCENARIO: Journal title ordering: multi-journal search
	 *    WHEN I am doing a multi-journal search
	 *    THEN I can order the result set by journal
	 *         title.
	 */
	function testResultOrdering() {
		// Test ordering of a single-journal search.
		$singleJournalExamples = array(
			array('luceneSearchOrder', 'score', 4), // Default: descending
			array('luceneSearchDirection', 'asc', 3),
			array('luceneSearchOrder', 'authors', 4), // Default: ascending
			array('luceneSearchDirection', 'desc', 5),
			array('luceneSearchOrder', 'publicationDate', 5), // Default: descending
			array('luceneSearchOrder', 'title', 5), // Default: ascending
			array('luceneSearchDirection', 'desc', 4)
		);
		// Execute a query that produces a different score for all
		// articles in the result set.
		$this->simpleSearch('+chicken +(wings feet) 2');
		foreach($singleJournalExamples as $example) {
			$this->_checkResultOrderingExample($example);
		}

		// Make sure that there is no journal-title ordering.
		$singleJournalOrderingOptions = $this->getSelectOptions('luceneSearchOrder');
		$this->assertFalse(in_array('Journal Title', $singleJournalOrderingOptions));


		// Test ordering of a multi-journal search.
		$multiJournalExamples = array(
			array('luceneSearchOrder', 'issuePublicationDate', 3), // Default: descending
			array('luceneSearchOrder', 'journalTitle', 3), // Default: ascending
			array('luceneSearchDirection', 'desc', 1)
		);
		$this->simpleSearchAcrossJournals('test NOT ranking');
		foreach($multiJournalExamples as $example) {
			$this->_checkResultOrderingExample($example);
		}

		// Make sure that journal-title ordering is allowed.
		$multiJournalOrderingOptions = $this->getSelectOptions('luceneSearchOrder');
		$this->assertTrue(in_array('Journal Title', $multiJournalOrderingOptions));
	}


	//
	// Private helper methods
	//
	/**
	 * Check result ordering example.
	 * @param $example array
	 */
	private function _checkResultOrderingExample($example) {
		// Expand the example.
		list($selectField, $value, $expectedFirstResult) = $example;

		// Save order and direction for debugging.
		static $luceneSearchOrder, $luceneSearchDirection;
		$$selectField = $value;

		try {
			// Select the next order criterium or direction.
			if ($this->getSelectedValue($selectField) != $value) {
				$this->selectAndWait($selectField, "value=$value");
			}

			// Check the first result to make sure ordering works
			// correctly.
			$this->assertAttribute(
				'css=table.listing a.file:first-child@href',
				'*/article/view/' . $expectedFirstResult
			);
		} catch (Exception $e) {
			throw $this->improveException($e, "example $luceneSearchOrder - $luceneSearchDirection");
		}
	}
}

