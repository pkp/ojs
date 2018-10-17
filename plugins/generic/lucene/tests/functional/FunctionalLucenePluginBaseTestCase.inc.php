<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginBaseTestCase.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginBaseTestCase
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies (base class with common functionality).
 */

import('lib.pkp.tests.WebTestCase');

class FunctionalLucenePluginBaseTestCase extends WebTestCase {

	protected $simpleSearchForm = '//form[@id="simpleSearchForm"]//';


	//
	// Implement template methods from PKPTestCase
	//
	protected function tearDown() {
		parent::tearDown();
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->_getCache(0, 'luceneplugin')->flush();
	}


	//
	// Protected helper methods
	//
	/**
	 * Execute a simple search.
	 *
	 * @param $searchPhrase string
	 * @param $searchField
	 * @param $articles integer|array a list of article
	 *  ids that must appear in the result set
	 * @param $notArticles integer|array a list of article
	 *  ids that must not appear in the result. Can be '*'
	 *  to exclude any additional result.
	 * @param $locale string
	 * @param $journal string the context path of the journal to test
	 */
	protected function simpleSearch($searchPhrase, $searchField = 'query', $articles = array(), $notArticles = array(), $locale = 'en_US', $journal = 'lucene-test') {
		// Translate scalars to arrays.
		if (!is_array($articles)) $articles = array($articles);
		if ($notArticles !== '*' && !is_array($notArticles)) $notArticles = array($notArticles);

		try {
			// Open the "lucene-test" journal home page.
			$testJournal = $this->baseUrl . '/index.php/' . $journal;
			$this->verifyAndOpen($testJournal);

			// Select the locale.
			$selectedValue = $this->getSelectedValue('name=locale');
			if ($selectedValue != $locale) {
				$this->selectAndWait('name=locale', 'value=' . $locale);
			}

			// Hack to work around timing problems in phpunit 3.4...
			$this->waitForElementPresent($this->simpleSearchForm . 'input[@id="simpleQuery"]');
			$this->waitForElementPresent('name=searchField');

			// Enter the search phrase into the simple search field.
			$this->type($this->simpleSearchForm . 'input[@id="simpleQuery"]', $searchPhrase);

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
			if ($notArticles === '*') {

			} else {
				foreach($notArticles as $id) {
					$this->assertElementNotPresent('//table[@class="listing"]//a[contains(@href, "index.php/lucene-test/article/view/' . $id . '")]');
				}
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
	protected function simpleSearchAcrossJournals($searchTerm, $locale = 'en_US') {
		// Open the test installation's home page.
		$homePage = $this->baseUrl . '/index.php';
		$this->verifyAndOpen($homePage);

		// Select the locale.
		$selectedValue = $this->getSelectedValue('name=locale');
		if ($selectedValue != $locale) {
			$this->selectAndWait('name=locale', 'value=' . $locale);
		}

		// Hack to work around timing problems in phpunit 3.4...
		$this->waitForElementPresent($this->simpleSearchForm . 'input[@id="simpleQuery"]');
		$this->waitForElementPresent('name=searchField');

		// Enter the search term into the simple search box.
		$this->type($this->simpleSearchForm . 'input[@id="simpleQuery"]', $searchTerm);

		// Click the "Search" button.
		$this->clickAndWait($this->simpleSearchForm . 'input[@type="submit"]');
	}
}

