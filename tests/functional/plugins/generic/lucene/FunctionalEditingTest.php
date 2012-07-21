<?php

/**
 * @file tests/functional/plugins/lucene/FunctionalEditingTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalEditingTest
 * @ingroup tests_functional_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the OJS submission and editing process.
 */


import('tests.functional.plugins.generic.lucene.FunctionalLucenePluginBaseTestCase');

class FunctionalEditingTest extends FunctionalLucenePluginBaseTestCase {

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
}
?>