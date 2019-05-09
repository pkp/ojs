<?php

/**
 * @file tests/functional/pages/editor/FunctionalEditingTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
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
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		$affectedTables = parent::getAffectedTables();
		$affectedTables[] = 'plugin_settings';
		return $affectedTables;
	}

	/**
	 * @see WebTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
	}

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
		$this->_enablePushProcessing();

		// Create and publish a test article.
		$this->_articleId = $this->submitArticle();
		$this->publishArticle($this->_articleId);

		$examples = array(
			'txt', 'html', 'pdf', 'doc', 'docx', 'odt', 'epub'
		);
		foreach($examples as $example) {
			// Identify the test galley in the sample
			// document format.
			// Go to the galley upload page and
			// upload a galley.
			$galleyUri = $this->_getTestFileUri($example);
			$this->uploadGalley($this->_articleId, $galleyUri, $example);
		}

		// Check that the galleys have been indexed.
		// NB: We check this directly in the index as this
		// is much faster and searching itself is sufficiently
		// tested elsewhere.
		$indexDocument = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexDocument));
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
			$this->assertArrayHasKey($fieldName, $indexDocument);
			$this->assertContains("${example}testarticle", $indexDocument[$fieldName]);
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
		$this->_enablePushProcessing();

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
		$fileUri = $this->_getTestFileUri('pdf');
		$this->uploadGalley($this->_articleId, $fileUri, 'PDF');

		// Check whether the galley has been indexed.
		$indexedArticle = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexedArticle), 'Article no longer indexed after galley upload.');
		$this->assertArrayHasKey('galleyFullText_pdf_en_US', $indexedArticle, 'Galley not indexed after galley upload.');

		// Delete the galley.
		$submissionEditingPage = $this->baseUrl . '/index.php/lucene-test/editor/submissionEditing/' . $this->_articleId;
		$this->verifyAndOpen($submissionEditingPage);
		$this->clickAndWait('//a[contains(@href, "deleteGalley")]');
		$this->waitForConfirmation('*Are you sure*');

		// Check that the galley is no longer indexed.
		$indexedArticle = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexedArticle), 'Article no longer indexed after deletion of galley.');
		$this->assertArrayNotHasKey('galleyFullText_pdf_en_US', $indexedArticle, 'Galley not properly deleted from index.');

		// Unpublish the article.
		$this->unpublishArticle($this->_articleId);

		// The article should no longer be indexed.
		$this->assertFalse($this->_solr->getArticleFromIndex($this->_articleId));
	}


	/**
	 * SCENARIO: Change document (push): delete article
	 *   GIVEN An article contains the word "noodles" in its title
	 *     AND is currently published
	 *     AND the article currently appears in the search
	 *         result list for "noodles" in its title
	 *    WHEN I delete the article
	 *    THEN I will immediately see it disappear from the result list
	 *         of a title search for "noodles".
	 */
	public function testDeleteDocument() {
		$this->_enablePushProcessing();

		// Set a title with "noodles".
		$articleTitle = 'Noodles are better than rice';

		// Submit a test article.
		$this->_articleId = $this->submitArticle($articleTitle);

		// Publish the article.
		$this->publishArticle($this->_articleId);

		// Check whether the article has been indexed.
		$indexedArticle = $this->_solr->getArticleFromIndex($this->_articleId);
		$this->assertTrue(is_array($indexedArticle), 'Publishing an article did not index it.');

		// Delete the article.
		$deleteArticleCommand = "php ./tools/deleteSubmissions.php $this->_articleId";
		$result = exec($deleteArticleCommand);

		// The article should no longer be indexed.
		$this->assertFalse($this->_solr->getArticleFromIndex($this->_articleId), 'Deleting an article did not remove it from the index.');

		// Clean up the article id.
		$this->_articleId = null;
	}

	/**
	 * FEATURE: pull indexing (OJS side only)
	 *
	 * BACKGROUND:
	 *   GIVEN I enabled the pull indexing feature
	 *
	 * SCENARIO: publishing or changing an article
	 *    WHEN I publish or change an article
	 *     BUT I do not unpublish the article
	 *    THEN an article setting "dirty" will be set to "1" which
	 *         indicates that the article must be re-indexed
	 *     AND the article will appear in the public XML
	 *         web service for pull-indexing
	 *     BUT the article will not be marked for deletion.
	 *
	 * SCENARIO: unpublishing an article
	 *    WHEN I unpublish a previously published article
	 *    THEN an article setting "dirty" will be set to "1" which
	 *         means that the article must be deleted from the index
	 *     AND the article will appear in the public XML
	 *         web service for pull-indexing.
	 *     AND the article will be marked for deletion.
	 *
	 * SCENARIO: pull request
	 *   WHEN the server receives a pull request
	 *   THEN all articles appearing in the request will be marked
	 *        "clean" once the request was successfully transferred to the
	 *        server side.
	 *
	 * For a specification of server side processing and for a full picture
	 * of pull processing, please see http://pkp.sfu.ca/wiki/index.php/OJSdeSearchConcept#Pull_Processing.
	 */
	function testPullIndexing() {
		// Enable pull indexing.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'pullIndexing', true);

		// Publish an article.
		$this->_articleId = $this->submitArticle();
		$this->publishArticle($this->_articleId);

		// Check that the article is "dirty".
		$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$article = $articleDao->getById($this->_articleId);
		$this->assertEquals(SOLR_INDEXINGSTATE_DIRTY, $article->getData('indexingState'));

		// Check that the article appears in the pull indexing
		// web service and is not marked for deletion.
		$pullXml = $this->_retrievePullIndexingXml();
		$this->assertContains('article id="' . $this->_articleId . '" sectionId="3" journalId="2" instId="test-inst" loadAction="replace"', $pullXml);

		// Check that the article is now "clean".
		$article = $articleDao->getById($this->_articleId);
		$this->assertEquals(SOLR_INDEXINGSTATE_CLEAN, $article->getData('indexingState'));

		// Unpublish the article.
		$this->unpublishArticle($this->_articleId);

		// Check that the article is "dirty" again.
		$article = $articleDao->getById($this->_articleId);
		$this->assertEquals(SOLR_INDEXINGSTATE_DIRTY, $article->getData('indexingState'));

		// Check that the article appears in the pull indexing
		// web service and is marked for deletion.
		$pullXml = $this->_retrievePullIndexingXml();
		$this->assertContains('article id="' . $this->_articleId . '" sectionId="3" journalId="2" instId="test-inst" loadAction="delete"', $pullXml);

		// Check that the article is "clean".
		$article = $articleDao->getById($this->_articleId);
		$this->assertEquals(SOLR_INDEXINGSTATE_CLEAN, $article->getData('indexingState'));
	}


	//
	// Private helper methods
	//
	/**
	 * Enable push indexing so that we can
	 * immediately check indexing success.
	 */
	function _enablePushProcessing() {
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'pullIndexing', false);
	}

	/**
	 * Call the pull indexing web service and return
	 * the result.
	 */
	function _retrievePullIndexingXml() {
		$curlCh = curl_init ();
		$pullUrl = $this->baseUrl . '/index.php/index/lucene/pullChangedArticles';
		curl_setopt($curlCh, CURLOPT_URL, $pullUrl);
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($curlCh);
	}

	/**
	 * Build the test file UI as understood by Selenium.
	 * @param string $fileFormat The test file format, e.g. "pdf".
	 * @return string
	 */
	private function _getTestFileUri($fileFormat) {
		$testFile = 'tests/functional/pages/editor/test-files/test-article.' . $fileFormat;
		self::assertTrue(file_exists($testFile));
		$testFile = realpath($testFile);
		if (Core::isWindows()) {
			$testFile = str_replace(DIRECTORY_SEPARATOR, '/', $testFile);
			$testFile = PKPString::regexp_replace('%^[A-Z]:/%', '/', $testFile);
		}
		$testFile = 'file://' . $testFile;
		return $testFile;
	}
}

