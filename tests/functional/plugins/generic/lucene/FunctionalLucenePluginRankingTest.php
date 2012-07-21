<?php

/**
 * @file tests/functional/plugins/lucene/FunctionalLucenePluginRankingTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginRankingTest
 * @ingroup tests_functional_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies (result set pagination, ordering and ranking).
 */


import('tests.functional.plugins.generic.lucene.FunctionalLucenePluginBaseTestCase');

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
	 *
	 * @group current
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
	}

	/**
	 * SCENARIO OUTLINE: Result ordering
	 *   GIVEN I am looking at a result page
	 *    WHEN I select {order criterium} and {order direction}
	 *    THEN I will see a different result list re-ordered by the
	 *         changed criterium and in the given direction.
	 *
	 * EXAMPLES:
	 *   order criterium  | order direction
	 *   ==================================
	 *   relevance        | descending
	 *   author           | ascending
	 *   issue date       | ascending
	 *   issue date       | descending
	 *   publication date | descending
	 *   journal title    | ascending
	 *   journal title    | descending
	 *   article title    | ascending
	 */
}
?>