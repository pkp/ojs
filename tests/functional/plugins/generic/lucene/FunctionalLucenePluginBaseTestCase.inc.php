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

	/**
	 * Submit a new test article.
	 * @param $title string
	 * @return integer the id of the new article
	 */
	protected function submitArticle($title) {
		// We need to be logged in to submit an article.
		$this->logIn();
		$submissionPage = $this->baseUrl . '/index.php/lucene-test/author/submit/';

		//
		// First submission page.
		//
		$this->verifyAndOpen($submissionPage . '1');

		// Accept submission conditions.
		$checkboxId = 1;
		while ($this->isElementPresent("checklist-$checkboxId")) {
			$this->check("checklist-$checkboxId");
			$checkboxId++;
		}

		// Submit first submission page.
		$this->clickAndWait('css=input.defaultButton');

		//
		// Second submission page.
		//
		$this->waitForLocation($submissionPage . '2');

		// We should now have the article ID in the URL.
		$url = $this->getLocation();
		$matches = null;
		String::regexp_match_get('/articleId=([0-9]+)/', $url, $matches);
		self::assertTrue(count($matches) == 2);
		$articleId = $matches[1];
		self::assertTrue(is_numeric($articleId));
		$articleId = (integer)$articleId;

		// Submit the second submission page.
		$this->clickAndWait('css=input.defaultButton');
		$this->chooseOkOnNextConfirmation();

		//
		// Third submission page.
		//
		$this->waitForLocation($submissionPage . '3');

		// Fill in article metadata.
		$this->type('authors-0-firstName', 'Arthur');
		$this->type('authors-0-lastName', 'McAutomatic');
		$this->type('title', $title);
		$this->type('dom=document.getElementById("abstract_ifr").contentDocument.body', $title . ' abstract'); // TinyMCE hack.

		// Submit metadata.
		$this->clickAndWait('css=input.defaultButton');

		//
		// Fourth and fifth submission page.
		//
		$this->waitForLocation($submissionPage . '4');
		// Do not upload any supplementary file and continue.
		$this->clickAndWait('css=input.defaultButton');
		$this->waitForLocation($submissionPage . '5');
		// Confirm the submission.
		$this->clickAndWait('css=input.defaultButton');

		return $articleId;
	}

	/**
	 * Publish the given article
	 * @param $articleId integer
	 */
	protected function publishArticle($articleId) {
		// Editing an article requires us to be logged in.
		$this->logIn();
		$editorBasePath = $this->baseUrl . '/index.php/lucene-test/editor/';

		// Go to the summary page of the article.
		$summaryPage = $editorBasePath . 'submission/' . $articleId;
		$this->verifyAndOpen($summaryPage);

		// If no editor is assigned: Add ourselves as an editor.
		if ($this->isElementPresent('link=Add Self')) {
			$this->clickAndWait('link=Add Self');
		}

		// Go to the editing page of the article
		$editingPage = $editorBasePath . 'submissionEditing/' . $articleId;
		$this->verifyAndOpen($editingPage);

		$issueId = $this->getSelectedValue('issueId');
		if (!is_numeric($issueId) || $issueId < 0) {
			// Assign the article to issue id 2 (which is
			// "Vol 1" of the lucene-test journal). This will
			// implicitly publish the article.
			$this->select('issueId', 'value=2');
			$this->clickAndWait('//div[@id="scheduling"]//tr[1]//input[@type="submit"]');
		} else {
			// (Re-)Publish the article with the current date.
			$this->clickAndWait('//div[@id="scheduling"]//tr[2]//input[@type="submit"]');
		}
	}
}
?>