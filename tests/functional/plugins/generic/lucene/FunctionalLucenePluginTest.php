<?php

/**
 * @file tests/functional/plugins/lucene/FunctionalLucenePluginTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginTest
 * @ingroup tests_functional_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies.
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalLucenePluginTest extends WebTestCase {

	/**
	 * SCENARIO: Search across all journals of an installation
	 *   GIVEN I am on an OJS installation's home page
	 *     AND the OJS installation contains at least two
	 *         journals
	 *     AND at least two journals contain an article with
	 *         the word "chicken" in its title
	 *    WHEN I enter the word "chicken" into the simple
	 *         search box
	 *     AND I click the "Search" button
	 *    THEN I receive a search result with articles from
	 *         both journals in the result set.
	 */
	public function testSomething() {
	}


	/**
	 * SCENARIO OUTLINE: Simple search
	 *   GIVEN I am on an OJS journal's home page
	 *     AND this journal contains an article with
	 *         id {id} and the keyword {keyword} in
	 *         its {search field}
	 *    WHEN I enter the word {keyword} into the simple
	 *         search box
	 *     AND I select the {search field} to search in
	 *     AND I click the "Search" button
	 *    THEN I receive a search result with the article
	 *         id {id} in the result set.
	 *
	 * EXAMPLES:
	 *   search field | keyword | id
	 *   ===============================
	 *   all fields   |         |
	 *   author       |         |
	 *   title        |         |
	 *   abstract     |         |
	 *   index terms  |         |
	 *   full text    |         |
	 */


	/**
	 * SCENARIO OUTLINE: Advanced search
	 *   GIVEN I am on an OJS journal's home page
	 *     AND this journal contains an article with
	 *         id {id} and the keyword {keyword} in
	 *         its {search field}
	 *    WHEN I enter the word {keyword} into the advanced
	 *         search input field {search field}
	 *     AND I click the "Search" button
	 *    THEN I receive a search result with the article
	 *         id {id} in the result set.
	 *
	 * EXAMPLES:
	 *   search field | keyword | id
	 *   ===============================
	 *   all fields   |         |
	 *   author       |         |
	 *   title        |         |
	 *   full text    |         |
	 *   suppl. files |         |
	 *   date from/to |         |
	 *   discipline   |         |
	 *   subject      |         |
	 *   type         |         |
	 *   coverage     |         |
	 */


	/**
	 * SCENARIO OUTLINE: Search syntax
	 *   GIVEN I am on an OJS journal's home page
	 *     AND the journal contains an article A
	 *         with the exact phrase "chicken and wings"
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
	 *   ==================================================================
	 *   en_US      | chicken wings                | A       | B, C, D       // implicit conjunction
	 *   en_US      | chicken AND wings            | A       | B, C, D       // explicit conjunction
	 *   en_US      | chicken OR wings             | A, B, C | D             // disjunction
	 *   en_US      | chicken NOT wings            | B       | A, C, D       // negation
	 *   en_US      | ((wings OR egg) NOT chicken) | C, D    | A, B          // bracketed search phrase
	 *   en_US      | chicken NICHT wings          |         | A, B, C, D    // search syntax localization
	 *   de_DE      | chicken NICHT wings          | C, D    | A, B          //      - " -
	 *   en_US      | "chicken wings"              |         | A, B, C, D    // exact term search
	 *   en_US      | "chicken and wings"          | A       | B, C, D       //      - " -
	 *   en_US      | chicken*                     | A, B, D | C             // wildcard search
	 *   en_US      | ChiCkeN Wings                | A       | B, C, D       // case insensitive search
	 */


	/**
	 * SCENARIO OUTLINE: Multi-language search
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

	/**
	 * SCENARIO OUTLINE: Document upload: supported galley formats
	 *   GIVEN I am looking at the galley upload page
	 *    WHEN I upload a galley in {document format}
	 *    THEN the document is immediately available in the index.
	 *
	 * EXAMPLES:
	 *   document format
	 *   ===============
	 *   plain text
	 *   HTML
	 *   PDF
	 *   PS
	 *   Microsoft Word
	 */

	/**
	 * SCENARIO: Change document (push): publication
	 *   GIVEN An article contains the word "noodles" in its title
	 *     BUT is not currently published
	 *     AND the article does not currently appear in the search
	 *         result list for "noodles" in its title
	 *    WHEN I publish the article
	 *    THEN I will immediately see it appear in the result list
	 *         of a title search for "noodles".
	 *
	 * SCENARIO: Change document (push): unpublish article
	 *   GIVEN An article contains the word "noodles" in its title
	 *     AND is currently published
	 *     AND the article currently appears in the search
	 *         result list for "noodles" in its title
	 *    WHEN I unpublish the article
	 *    THEN I will immediately see it disappear from the result list
	 *         of a title search for "noodles".
	 *
	 * SCENARIO: Change document (push): meta-data
	 *   GIVEN An article does not contain the word "noodles" in its title
	 *     AND it does not appear in a title search for the word "noodles"
	 *    WHEN I change its title to contain the word "noodles"
	 *    THEN I will immediately see the article appear in the
	 *         result list of a title search for the word "noodles".
	 *
	 * SCENARIO: Change document (push): add galley
	 *     see document upload test cases above.
	 *
	 * SCENARIO: Change document (push): delete galley
	 *   GIVEN An article galley contains a word not contained in
	 *         any other galley of the article, say "noodles"
	 *     AND the article appears in the full-text search result
	 *         list for "noodles"
	 *    WHEN I delete this galley from the article
	 *    THEN I will immediately see the article disappear from the
	 *         "noodles" full-text search result list.
	 *
	 * SCENARIO: Change document (push): add supplementary file
	 *   GIVEN None of an article's supplementary files contains the
	 *         word "noodles"
	 *     AND a supplementary file search for the word "noodles" gives
	 *         no result
	 *    WHEN I add a supplementary file that contains the word "noodles"
	 *         to the article
	 *    THEN I will immediately see the article appear in the
	 *         "noodles" supplementary file search result list.
	 *
	 * SCENARIO: Change document (push): delete supplementary file
	 *   GIVEN An article's supplementary file contains a word not contained in
	 *         any other supplementary file of the article, say "noodles".
	 *     AND the article appears in the supplementary file search result
	 *         list for "noodles"
	 *    WHEN I delete this supplementary file from the article
	 *    THEN I will immediately see the article disappear from the
	 *         "noodles" supplementary file search result list.
	 */

	/**
	 * SCENARIO: Plug-in de-activated + solr server switched off
	 *   GIVEN The lucene plug-in is de-activated
	 *     AND the solr server is switched off
	 *    WHEN I execute a search
	 *    THEN I will see search results served by the OJS standard
	 *         search implementation.
	 *
	 * SCENARIO: Plug-in activated + solr server switched off
	 *   GIVEN The lucene plug-in is de-activated
	 *     AND the solr server is switched off
	 *    WHEN I activate the lucene plug-in
	 *     AND I execute a search
	 *    THEN I will see an error message informing that the
	 *         solr server is not functioning.
	 *
	 * SCENARIO: Plug-in activated + solr server switched on
	 *   GIVEN The lucene plug-in is activated
	 *     AND the solr server is switched off
	 *    WHEN I switch on the solr server
	 *     AND I execute a search
	 *    THEN I will see search results served by the solr server.
	 */
}
?>