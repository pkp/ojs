<?php

/**
 * @file tests/functional/plugins/generic/lucene/FunctionalLucenePluginBaseTestCase.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginBaseTestCase
 * @ingroup tests_functional_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies (base class with common functionality).
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalLucenePluginBaseTestCase extends WebTestCase {

	protected $simpleSearchForm = '//form[@id="simpleSearchForm"]//';


	//
	// Protected helper methods
	//
	/**
	 * Execute a simple search.
	 *
	 * @param $searchPhrase string
	 * @param $searchField
	 * @param $articles integer|array
	 * @param $notArticles integer|array
	 * @param $locale string
	 */
	protected function simpleSearch($searchPhrase, $searchField = '', $articles = array(), $notArticles = array(), $locale = 'en_US') {
		// Translate scalars to arrays.
		if (!is_array($articles)) $articles = array($articles);
		if (!is_array($notArticles)) $notArticles = array($notArticles);

		try {
			// Open the "lucene-test" journal home page.
			$testJournal = $this->baseUrl . '/index.php/lucene-test';
			$this->verifyAndOpen($testJournal);

			// Select the locale.
			$selectedValue = $this->getSelectedValue('name=locale');
			if ($selectedValue != $locale) {
					$this->select('name=locale', 'value=' . $locale);
					$this->waitForLocation($testJournal);
			}

			// Enter the search phrase into the simple search field.
			$this->type($this->simpleSearchForm . 'input[@id="query"]', $searchPhrase);

			// Select the search field.
			$this->select('name=searchField', 'value=' . $searchField);

			// Click the "Search" button.
			$this->clickAndWait($this->simpleSearchForm . 'input[@type="submit"]');

			// Check whether the result set contains the
			// sample articles.
			foreach($articles as $id) {
				$this->assertElementPresent('//table[@class="listing"]//a[contains(@href, "index.php/lucene-test/article/view/' . $id . '")]');
			}

			// Make sure that the result set does not contain
			// the articles in the "not article" list.
			foreach($notArticles as $id) {
				$this->assertElementNotPresent('//table[@class="listing"]//a[contains(@href, "index.php/lucene-test/article/view/' . $id . '")]');
			}
		} catch(Exception $e) {
			throw $this->improveException($e, "example $searchPhrase ($locale)");
		}
	}

	/**
	 * Execute a simple search across journals.
	 *
	 * @param $searchTerm string
	 */
	protected function simpleSearchAcrossJournals($searchTerm) {
		// Open the test installation's home page.
		$homePage = $this->baseUrl . '/index.php';
		$this->verifyAndOpen($homePage);

		// Enter the search term into the simple search box.
		$this->type($this->simpleSearchForm . 'input[@id="query"]', $searchTerm);

		// Click the "Search" button.
		$this->clickAndWait($this->simpleSearchForm . 'input[@type="submit"]');
	}
}
?>