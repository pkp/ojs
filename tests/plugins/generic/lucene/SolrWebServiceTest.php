<?php

/**
 * @file tests/plugins/generic/lucene/SolrWebServiceTest.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SolrWebServiceTest
 * @ingroup tests_plugins_generic_lucene
 * @see SolrWebService
 *
 * @brief Test class for the SolrWebService class
 */


import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.lucene.SolrWebService');
import('plugins.generic.lucene.EmbeddedServer');
import('classes.article.PublishedArticle');
import('classes.journal.Journal');
import('classes.core.PageRouter');

class SolrWebServiceTest extends PKPTestCase {

	/** @var SolrWebService */
	private $solrWebService;


	//
	// Implementing protected template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::getMockedDAOs()
	 */
	protected function getMockedDAOs() {
		$mockedDaos = parent::getMockedDAOs();
		$mockedDaos += array(
			'AuthorDAO', 'SuppFileDAO', 'ArticleGalleyDAO'
		);
		return $mockedDaos;
	}

	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// Instantiate our web service for testing.
		$this->solrWebService = new SolrWebService();
	}


	//
	// Unit tests
	//
	/**
	 * @covers SolrWebService
	 */
	public function testRetrieveResults() {
		$embeddedServer = new EmbeddedServer();
		$this->_startServer($embeddedServer);

		// Make a search on specific fields.
		$journal = new Journal();
		$journal->setId(1);
		$testSearch = array(
			'all' => 'pizza',
			'authors' => 'Author',
			'galley_full_text' => 'Nutella',
			'title' => 'Titel'
		);
		$fromDate = date('Y-m-d\TH:i:s\Z', strtotime('2000-01-01'));
		$toDate = null;
		$scoredResults = $this->solrWebService->retrieveResults($journal, $testSearch, $fromDate, $toDate);
		self::assertTrue(is_array($scoredResults) && !empty($scoredResults) && in_array('3', $scoredResults));
	}

	/**
	 * @covers SolrWebService
	 */
	public function testGetAvailableFields() {
		$embeddedServer = new EmbeddedServer();
		$this->_startServer($embeddedServer);
		$this->solrWebService->flushFieldCache();
		// Only check one exemplary key to make sure that we got something useful back.
		self::assertArrayHasKey('title', $this->solrWebService->getAvailableFields());
	}

	/**
	 * @covers SolrWebService
	 */
	public function testGetServerStatus() {
		// Make sure the server has been started.
		$embeddedServer = new EmbeddedServer();
		$result = $this->_startServer($embeddedServer);

		// Test the status message.
		self::assertEquals(
			array(
				'status' => SOLR_STATUS_ONLINE,
				'message' => 'Index with 1 documents online.'
			),
			$result
		);

		// Stop the server, then test the status again.
		$embeddedServer->stop();
		while($embeddedServer->isRunning()) sleep(1);
		self::assertEquals(
			array(
				'status' => SOLR_STATUS_OFFLINE,
				'message' => 'Solr server not reachable. Is the solr server running? Does the configured search handler point to the right URL?'
			),
			$this->solrWebService->getServerStatus()
		);

		// Restart the server.
		$result = $this->_startServer($embeddedServer);
	}

	/**
	 * @covers SolrWebService
	 */
	public function testGetArticleXml() {
		// Generate a test article.
		$article = $this->_getTestArticle();
		// Generate a test journal.
		$journal = new Journal();
		$journal->setPath('test');

		// Test the transfer XML file.
		$articleDoc = $this->solrWebService->_getArticleXml($article, $journal);
		self::assertXmlStringEqualsXmlFile(
			'tests/plugins/generic/lucene/test-article.xml',
			XMLCustomWriter::getXml($articleDoc)
		);
	}

	/**
	 * @covers SolrWebService
	 */
	public function testIndexArticle() {
		// Generate a test article.
		$article = $this->_getTestArticle();
		$article->setJournalId('1');
		// Generate a test journal.
		$journal = new Journal();
		$journal->setId('1');
		$journal->setPath('test');

		// Test indexing. The service returns true if the article
		// was successfully processed.
		self::assertTrue($this->solrWebService->indexArticle($article, $journal));
	}

	/**
	 * @covers SolrWebService
	 */
	public function testIndexJournal() {
		// We need a router for URL generation.
		$application =& PKPApplication::getApplication();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$request =& $application->getRequest();
		$router = new PageRouter();
		$router->setApplication($application);
		$request->setRouter($router);

		// Generate a test journal.
		$journal = new Journal();
		$journal->setId('1');
		$journal->setPath('test');

		// Test indexing. The service returns the number of documents that
		// were successfully processed.
		self::assertNotNull($this->solrWebService->indexJournal($journal));
	}

	/**
	 * @covers SolrWebService
	 */
	public function testDeleteArticleFromIndex() {
		self::assertTrue($this->solrWebService->deleteArticleFromIndex(3));
	}

	/**
	 * @covers SolrWebService
	 */
	public function testDeleteAllArticlesFromIndex() {
		self::assertTrue($this->solrWebService->deleteAllArticlesFromIndex());
	}


	//
	// Private helper methods
	//
	/**
	 * Start the embedded server.
	 * @param $embeddedServer EmbeddedServer
	 * @return $result
	 */
	private function _startServer($embeddedServer) {
		if (!$embeddedServer->isRunning()) {
			$embeddedServer->start();
		}
		do {
			sleep(1);
			$result = $this->solrWebService->getServerStatus();
		} while ($result['status'] != SOLR_STATUS_ONLINE);
		return $result;
	}

	/**
	 * Mock and register a ArticleGalleyDAO as a test
	 * back end for the SolrWebService class.
	 */
	private function _registerMockArticleGalleyDAO() {
		// Mock an ArticleGalleyDAO.
		$galleyDAO = $this->getMock('ArticleGalleyDAO', array('getGalleysByArticle'), array(), '', false);

		// Mock a list of supplementary files.
		$galley1 = new ArticleGalley();
		$galley1->setId(4);
		$galley1->setLocale('de_DE');
		$galley1->setFileType('application/pdf');
		$galley1->setFileName('galley1.pdf');
		$galley2 = new ArticleGalley();
		$galley2->setId(5);
		$galley2->setLocale('en_US');
		$galley2->setFileType('text/html');
		$galley2->setFileName('galley2.html');
		$galleys = array($galley1, $galley2);

		// Mock the getGalleysByArticle() method.
		$galleyDAO->expects($this->any())
		          ->method('getGalleysByArticle')
		          ->will($this->returnValue($galleys));

		// Register the mock DAO.
		DAORegistry::registerDAO('ArticleGalleyDAO', $galleyDAO);
	}

	/**
	 * Mock and register a SuppFileDAO as a test
	 * back end for the SolrWebService class.
	 */
	private function _registerMockSuppFileDAO() {
		// Mock an SuppFileDAO.
		$suppFileDAO = $this->getMock('SuppFileDAO', array('getSuppFilesByArticle'), array(), '', false);

		// Mock a list of supplementary files.
		$suppFile1 = new SuppFile();
		$suppFile1->setId(2);
		$suppFile1->setLanguage('de');
		$suppFile1->setFileType('application/pdf');
		$suppFile1->setFileName('suppFile1.pdf');
		$suppFile2 = new SuppFile();
		$suppFile2->setId(3);
		$suppFile2->setLanguage('tu');
		$suppFile2->setFileType('text/html');
		$suppFile2->setFileName('suppFile2.html');
		$suppFile2->setTitle('Titel', 'de_DE');
		$suppFile2->setCreator('Autor', 'de_DE');
		$suppFile2->setSubject('Thema', 'de_DE');
		$suppFile2->setTypeOther('Sonstiger Typ', 'de_DE');
		$suppFile2->setDescription('Beschreibung', 'de_DE');
		$suppFile2->setSource('Quelle', 'de_DE');
		$suppFiles = array($suppFile1, $suppFile2);

		// Mock the getSuppFilesByArticle() method.
		$suppFileDAO->expects($this->any())
		            ->method('getSuppFilesByArticle')
		            ->will($this->returnValue($suppFiles));

		// Register the mock DAO.
		DAORegistry::registerDAO('SuppFileDAO', $suppFileDAO);
	}

	/**
	 * Mock and register an AuthorDAO as a test
	 * back end for the SolrWebService class.
	 */
	private function _registerMockAuthorDAO() {
		// Mock an AuthorDAO.
		$authorDAO = $this->getMock('AuthorDAO', array('getAuthorsBySubmissionId'), array(), '', false);

		// Mock a list of authors.
		$author1 = new Author();
		$author1->setFirstName('First');
		$author1->setLastName('Author');
		$author2 = new Author();
		$author2->setFirstName('Second');
		$author2->setMiddleName('M.');
		$author2->setLastName('Name');
		$authors = array($author1, $author2);

		// Mock the getAuthorsBySubmissionId() method.
		$authorDAO->expects($this->any())
		          ->method('getAuthorsBySubmissionId')
		          ->will($this->returnValue($authors));

		// Register the mock DAO.
		DAORegistry::registerDAO('AuthorDAO', $authorDAO);
	}

	/**
	 * Activate mock DAOs for authors, galleys and supp files
	 * and return a test article.
	 *
	 * @return Article
	 */
	private function _getTestArticle() {
		// Activate the mock DAOs.
		$this->_registerMockAuthorDAO();
		$this->_registerMockArticleGalleyDAO();
		$this->_registerMockSuppFileDAO();

		// We need a router for URL generation.
		$application =& PKPApplication::getApplication();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$request =& $application->getRequest();
		$router = new PageRouter();
		$router->setApplication($application);
		$request->setRouter($router);

		// Create a test article.
		$article = new PublishedArticle();
		$article->setId(3);
		$article->setJournalId(1);
		$article->setTitle('Deutscher Titel', 'de_DE');
		$article->setTitle('English Title', 'en_US');
		$article->setAbstract('Deutsche Zusammenfassung', 'de_DE');
		$article->setAbstract('English Abstract', 'en_US');
		$article->setDiscipline('Sozialwissenschaften', 'de_DE');
		$article->setDiscipline('Social Sciences', 'en_US');
		$article->setSubject('Thema', 'de_DE');
		$article->setSubjectClass('Ein Themengebiet', 'de_DE');
		$article->setSubject('subject', 'en_US');
		$article->setSubjectClass('Uma classe de temas', 'pt_BR');
		$article->setType('Typ', 'de_DE');
		$article->setType('type', 'en_US');
		$article->setCoverageGeo('Kaltes Kap', 'de_DE');
		$article->setCoverageGeo('Cabo Frio', 'pt_BR');
		$article->setCoverageChron('Sommer 2012', 'de_DE');
		$article->setCoverageChron('Summer 2012', 'en_US');
		$article->setCoverageSample('Alles', 'de_DE');
		$article->setCoverageSample('everything', 'en_US');
		$article->setDatePublished('2012-03-15 16:45:00');
		return $article;
	}
}
?>