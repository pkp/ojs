<?php

/**
 * @file tests/functional/pages/editor/FunctionalEditingTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalEditingTest
 * @ingroup tests_functional_pages_editor
 * @see SubmissionEditHandler
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the OJS editing process.
 *
 * NB: Currently we use this only to test the effects of
 * editing on the Lucene index. But we place the test class here
 * so that it can be re-used in case parts of the editing process
 * itself should be tested later.
 */


import('tests.functional.pages.editor.FunctionalEditingBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalEditingTest extends FunctionalEditingBaseTestCase {

	private $_articleId, $_solr;


	/**
	 * Constructor
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
		$this->_solr = new SolrWebService(
			'http://localhost:8983/solr/ojs/search',
			'admin', 'please change', 'test-inst'
		);
	}


	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::tearDown()
	 */
	protected function tearDown() {
		parent::tearDown();

		// If we generated an article then make sure it's being
		// deleted from the solr index.
		if (!is_null($this->_articleId)) {
			$this->_solr->deleteArticleFromIndex($this->_articleId);
			$this->_articleId = null;
		}
	}


	/**
	 * SCENARIO OUTLINE: Document upload: supported galley formats
	 *   GIVEN I am looking at the galley upload page
	 *    WHEN I upload a galley in {document format}
	 *    THEN the document is immediately available in the index.
	 *
	 * EXAMPLES:
	 *   document format
	 *   ======================
	 *   plain text
	 *   HTML
	 *   PDF
	 *   MS Word 97 and later
	 *   MS Word 2010 and later
	 *   Open-/LibreOffice
	 *   ePub
	 *
	 * NB: solr cannot index PostScript files. We may implement
	 * PS to PDF conversion later on to close that gap.
	 */
	public function testDocumentUpload() {
		// Create and publish a test article.
		$this->_articleId = $this->submitArticle('Editing test article');
		$this->publishArticle($this->_articleId);

		$examples = array(
			'txt', 'html', 'pdf', 'doc', 'docx', 'odt', 'epub'
		);
		foreach($examples as $example) {
			// Identify the test galley in the sample
			// document format.
			try {
				$testFile = 'tests/functional/pages/editor/test-files/test-article.' . $example;
				self::assertTrue(file_exists($testFile));
				$testFile = realpath($testFile);

				// Go to the galley upload page and
				// upload a galley.
				$this->uploadGalley($this->_articleId, $testFile, $example);
			} catch (Exception $e) {
				throw $this->improveException($e, "example $example");
			}
		}

		// Check that the galleys have been indexed.
		// NB: We check this directly in the index as this
		// is much faster and searching itself is sufficiently
		// tested elsewhere.
		$indexDocument = $this->_solr->getArticleFromIndex($this->_articleId);
		foreach($examples as $example) {
			switch($example) {
				case 'doc':
				case 'docx':
				case 'odt':
					$docFormat = 'doc';
					break;

				case 'txt':
					$docFormat = 'plain';
					break;

				default:
					$docFormat = $example;
			}
			$fieldName = "galleyFullText_${docFormat}_en_US";
			try {
				$this->assertArrayHasKey($fieldName, $indexDocument);
				$this->assertContains("${example}testarticle", $indexDocument[$fieldName]);
			} catch (Exception $e) {
				throw $this->improveException($e, "checking indexed document for $example field");
			}
		}
	}

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
	 * SCENARIO: Change document (push): meta-data
	 *   GIVEN An article does not contain the word "peppermint" in its title
	 *     AND it does not appear in a title search for the word "peppermint"
	 *    WHEN I change its title to contain the word "peppermint"
	 *    THEN I will immediately see the article appear in the
	 *         result list of a title search for the word "peppermint".
	 *
	 * SCENARIO: Change document (push): add galley
	 *     see document upload test cases above.
	 *
	 * SCENARIO: Change document (push): delete galley
	 *   GIVEN An article galley contains a word not contained in
	 *         any other galley of the article, say "pdftestarticle"
	 *     AND the article appears in the full-text search result
	 *         list for "pdftestarticle"
	 *    WHEN I delete this galley from the article
	 *    THEN I will immediately see the article disappear from the
	 *         "pdftestarticle" full-text search result list.
	 *
	 * SCENARIO: Change document (push): add supplementary file
	 *   GIVEN None of an article's supplementary files contains the
	 *         word "pdftest" in its full text
	 *     AND none of an article's supplementary files contains the
	 *         word "spinach" in its title
	 *     AND a supplementary file search for the phrase "doctest
	 *         AND spinach" gives no result
	 *    WHEN I add a supplementary file to the article that contains
	 *         the word "pdftest" in its full text and "spinach" in
	 *         its title
	 *    THEN I will immediately see the article appear in the
	 *         "pdftest AND spinach" supplementary file search
	 *         result list.
	 *
	 * SCENARIO: Change document (push): delete supplementary file
	 *   GIVEN An article's supplementary file contains a word not contained in
	 *         any other supplementary file of the article, say "noodles".
	 *     AND the article appears in the supplementary file search result
	 *         list for "noodles"
	 *    WHEN I delete this supplementary file from the article
	 *    THEN I will immediately see the article disappear from the
	 *         "noodles" supplementary file search result list.
	 *
	 * SCENARIO: Change document (push): unpublish article
	 *   GIVEN An article contains the word "noodles" in its title
	 *     AND is currently published
	 *     AND the article currently appears in the search
	 *         result list for "noodles" in its title
	 *    WHEN I unpublish the article
	 *    THEN I will immediately see it disappear from the result list
	 *         of a title search for "noodles".
	 */
	public function testChangeDocument() {
		// Set a title with "noodles".
		$articleTitle = 'Noodles are better than rice';

		// Submit a test article.
		$this->_articleId = $this->submitArticle($articleTitle);

		// The article should not be indexed as long
		// as it is not public.
		$this->assertFalse($this->_solr->getArticleFromIndex($this->_articleId));

		// Publish the article.
		$this->publishArticle($this->_articleId);

		// Check whether the article has been indexed.
		$indexedArticle = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexedArticle), 'Publishing an article did not index it.');
		$this->assertArrayHasKey('title_en_US', $indexedArticle, 'Publishing an article did not index it (missing title).');
		$this->assertEquals($articleTitle, $indexedArticle['title_en_US'], 'Publishing an article did not index it (wrong title).');

		// Change the title of the article.
		$changedTitle = 'Peppermint is even better';
		$this->editMetadata($this->_articleId, $changedTitle);

		// Check whether the new title has been indexed.
		$indexedArticle = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexedArticle), 'Article disappeared from index after metadata update.');
		$this->assertArrayHasKey('title_en_US', $indexedArticle, 'Title no longer indexed after metadata update.');
		$this->assertEquals($changedTitle, $indexedArticle['title_en_US'], 'Title not updated in index after metadata update.');

		// Upload a galley.
		$testFile = realpath('tests/functional/pages/editor/test-files/test-article.pdf');
		$this->uploadGalley($this->_articleId, $testFile, 'PDF');

		// Check whether the galley has been indexed.
		$indexedArticle = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexedArticle), 'Article no longer indexed after galley upload.');
		$this->assertArrayHasKey('galleyFullText_pdf_en_US', $indexedArticle, 'Galley not indexed after galley upload.');

		// Delete the galley.
		$submissionEditingPage = $this->baseUrl . '/index.php/lucene-test/editor/submissionEditing/' . $this->_articleId;
		$this->verifyAndOpen($submissionEditingPage);
		$this->clickAndWait('//a[contains(@href, "deleteGalley")]');

		// Check that the galley is no longer indexed.
		$indexedArticle = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexedArticle), 'Article no longer indexed after deletion of galley.');
		$this->assertArrayNotHasKey('galleyFullText_pdf_en_US', $indexedArticle, 'Galley not properly deleted from index.');

		// Upload a supplementary file.
		$this->uploadSuppFile($this->_articleId, $testFile, 'spinach');

		// Check whether the supplementary file
		// has been indexed.
		$indexedArticle = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexedArticle), 'Article no longer indexed after supp file upload.');
		$this->assertArrayHasKey('suppFiles_en_US', $indexedArticle, 'Supp file not indexed after supp file upload.');
		$this->assertContains('pdftest', $indexedArticle['suppFiles_en_US'], 'Supp file full text not indexed.');
		$this->assertContains('spinach', $indexedArticle['suppFiles_en_US'], 'Supp file metadata not indexed.');

		// Delete the supplementary file.
		$submissionEditingPage = $this->baseUrl . '/index.php/lucene-test/editor/submissionEditing/' . $this->_articleId;
		$this->verifyAndOpen($submissionEditingPage);
		$this->clickAndWait('//a[contains(@href, "deleteSuppFile")]');

		// Check that the supp file is no longer indexed.
		$indexedArticle = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexedArticle), 'Article no longer indexed after deletion of supp file.');
		$this->assertArrayNotHasKey('suppFiles_en_US', $indexedArticle, 'Supp file not properly deleted from index.');

		// Unpublish the article.
		$this->unpublishArticle($this->_articleId);

		// The article should no longer be indexed.
		$this->assertFalse($this->_solr->getArticleFromIndex($this->_articleId));
	}
}
?>