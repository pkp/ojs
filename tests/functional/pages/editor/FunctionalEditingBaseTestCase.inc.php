<?php

/**
 * @file tests/functional/pages/editor/FunctionalEditingBaseTestCase.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginBaseTestCase
 * @ingroup tests_functional_pages_editor
 * @see SubmissionEditHandler
 *
 * @brief Integration/Functional test for the OJS editing
 * process (base class with common functionality).
 */

import('lib.pkp.tests.WebTestCase');

class FunctionalEditingBaseTestCase extends WebTestCase {

	protected $editorBasePath;


	//
	// Implement protected template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'submissions', 'submission_settings', 'submission_files',
			'submission_galleys', 'submission_galley_settings',
			'article_supplementary_files', 'article_supp_file_settings',
			'authors', 'author_settings', 'edit_assignments',
			'event_log', 'event_log_settings', 'issues',
			'published_submissions', 'signoffs', 'notifications',
			'sessions'
		);
	}

	/**
	 * @see WebTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$this->editorBasePath = $this->baseUrl . '/index.php/lucene-test/editor/';
	}


	//
	// Protected helper methods
	//
	/**
	 * Submit a new test article.
	 * @param $title string
	 * @return integer the id of the new article
	 */
	protected function submitArticle($title = 'Editing test article') {
		// We need to be logged in to submit an article.
		$this->logIn();
		$submissionPage = $this->baseUrl . '/index.php/lucene-test/author/submit/';

		//
		// First submission page.
		//
		$this->verifyAndOpen($submissionPage . '1');
		$this->waitForElementPresent('css=input.defaultButton');

		// Set Section.
		$this->select('sectionId', 'value=3');

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
		$this->waitForLocation($submissionPage . '2*');
		$this->waitForElementPresent('css=input.defaultButton');

		// We should now have the article ID in the URL.
		$url = $this->getLocation();
		$matches = null;
		PKPString::regexp_match_get('/articleId=([0-9]+)/', $url, $matches);
		self::assertTrue(count($matches) == 2);
		$articleId = $matches[1];
		self::assertTrue(is_numeric($articleId));
		$articleId = (integer)$articleId;

		// Submit the second submission page.
		$this->clickAndWait('css=input.defaultButton');
		$this->waitForConfirmation('*Are you sure you wish to continue*');

		//
		// Third submission page.
		//
		$this->waitForLocation($submissionPage . '3*');
		$this->waitForElementPresent('css=input.defaultButton');

		// Fill in article metadata.
		$this->type('authors-0-firstName', 'Arthur');
		$this->type('authors-0-lastName', 'McAutomatic');
		$this->type('title', $title);
		$this->verifyElementPresent('id=abstract_ifr');
		if ($this->verified()) {
			// TinyMCE hack.
			$jsScript = "selenium.browserbot.getCurrentWindow().document".
			            ".getElementById('abstract_ifr').contentDocument.body.innerHTML = ".
			            "'$title abstract'";
			$this->getEval($jsScript);
		} else {
			$this->type('abstract', $title . ' abstract');
		}

		// Submit metadata.
		$this->clickAndWait('css=input.defaultButton');

		//
		// Fourth and fifth submission page.
		//
		$this->waitForLocation($submissionPage . '4*');
		$this->waitForElementPresent('css=input.defaultButton');
		// Do not upload any supplementary file and continue.
		$this->clickAndWait('css=input.defaultButton');
		$this->waitForLocation($submissionPage . '5*');
		$this->waitForElementPresent('css=input.defaultButton');
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

		// Go to the summary page of the article.
		$summaryPage = $this->editorBasePath . 'submission/' . $articleId;
		$this->verifyAndOpen($summaryPage);

		// If no editor is assigned: Add ourselves as an editor.
		if ($this->isElementPresent('link=Add Self')) {
			$this->clickAndWait('link=Add Self');
		}

		// Go to the editing page of the article
		$editingPage = $this->editorBasePath . 'submissionEditing/' . $articleId;
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

	/**
	 * Unpublish the given article
	 * @param $articleId integer
	 */
	protected function unpublishArticle($articleId) {
		// Editing an article requires us to be logged in.
		$this->logIn();

		// Go to the editing page of the article
		$editingPage = $this->editorBasePath . 'submissionEditing/' . $articleId;
		$this->verifyAndOpen($editingPage);

		$issueId = $this->getSelectedValue('issueId');
		if (is_numeric($issueId) && $issueId > 0) {
			// Select "To be assigned"
			$this->select('issueId', 'value=');
			$this->clickAndWait('//div[@id="scheduling"]//tr[1]//input[@type="submit"]');
		}
	}

	/**
	 * Edit the meta-data of an article.
	 * @param $articleId integer
	 * @param $newTitle string
	 */
	protected function editMetadata($articleId, $newTitle) {
		// Editing an article requires us to be logged in.
		$this->logIn();

		// Go to the meta-data editing page.
		$metadataForm = $this->editorBasePath . 'viewMetadata/' . $articleId;
		$this->verifyAndOpen($metadataForm);

		// Input the new title.
		$this->type('css=input[id^=title-en_US]', $newTitle);

		// Save the meta-data.
		$this->clickAndWait('css=input.defaultButton');
	}

	/**
	 * Upload the given file as a galley.
	 * @param $articleId integer
	 * @param $galleyUri string
	 * @param $fileLabel string
	 */
	protected function uploadGalley($articleId, $galleyUri, $fileLabel) {
		// Open the editing page.
		$submissionEditingPage = $this->baseUrl . '/index.php/lucene-test/editor/submissionEditing/' . $articleId;
		$this->verifyAndOpen($submissionEditingPage);

		// Select galley upload radio option.
		$this->click('layoutFileTypeGalley');

		// Set the galley file.
		$this->attachFile('name=layoutFile', $galleyUri);

		// Click the upload button.
		$this->clickAndWait('css=#layout form input.button');
		$this->waitForLocation('*index.php/lucene-test/editor/editGalley*');

		// Type the file label.
		$this->type('name=label', strtoupper($fileLabel));

		// Save the galley.
		$this->clickAndWait('css=input.defaultButton');
	}
}

