<?php

/**
 * @file plugins/generic/lucene/tests/classes/SolrWebServiceTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SolrWebServiceTest
 * @ingroup plugins_generic_lucene_tests_classes
 * @see SolrWebService
 *
 * @brief Test class for the SolrWebService class
 */

require_mock_env('env2'); // Make sure we're in an en_US environment by using the mock AppLocale.

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.lucene.classes.SolrWebService');
import('plugins.generic.lucene.classes.EmbeddedServer');
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
			'AuthorDAO', 'IssueDAO', 'JournalDAO',
			'ArticleGalleyDAO'
		);
		return $mockedDaos;
	}

	/**
	 * @see PKPTestCase::getMockedRegistryKeys()
	 */
	protected function getMockedRegistryKeys() {
		return array('request');
	}

	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// We need a router for URL generation.
		$application = Application::getApplication();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$request = $application->getRequest();
		if (!is_a($request->getRouter(), 'PKPRouter')) {
			$router = new PageRouter();
			$router->setApplication($application);
			$request->setRouter($router);
		}

		// Add the indexing state as setting.
		HookRegistry::register('articledao::getAdditionalFieldNames', array($this, 'callbackAdditionalFieldNames'));

		// Set translations. This must be done early
		// as these translations will be saved statically
		// during the first request.
		AppLocale::setTranslations(
			array(
				'search.operator.not' => 'nicht',
				'search.operator.and' => 'und',
				'search.operator.or' => 'oder'
			)
		);

		// Instantiate our web service for testing.
		$this->solrWebService = new SolrWebService('http://localhost:8983/solr/ojs/search', 'admin', 'please change', 'test-inst');
	}


	//
	// Unit tests
	//
	/**
	 * @covers SolrWebService::retrieveResults()
	 *
	 * NB: Depends on correct journal indexing
	 * and must therefore be run after testIndexJournal().
	 * We run journal indexing as the last test and
	 * this test as the first test as journal indexing
	 * is asynchronous. This means that a prior test
	 * run must be successful for this test to pass.
	 */
	public function testRetrieveResults() {
		$this->markTestSkipped('Not currently working in CI environment.');

		$embeddedServer = new EmbeddedServer();
		$this->_startServer($embeddedServer);

		// Make sure that the journal is properly indexed.
		$this->_indexTestJournals();

		// Make a search on specific fields.
		$searchRequest = new SolrSearchRequest();
		$journal = new Journal();
		$journal->setId(2);
		$searchRequest->setJournal($journal);
		$searchRequest->setQuery(
			array(
				'authors' => 'Author',
				'galleyFullText' => 'Nutella',
				'title' => 'Article'
			)
		);
		$searchRequest->setFromDate(date('Y-m-d\TH:i:s\Z', strtotime('2000-01-01')));
		$searchRequest->setHighlighting(true);
		$totalResults = null;
		$results = $this->solrWebService->retrieveResults($searchRequest, $totalResults);

		// Check search results.
		self::assertTrue(is_int($totalResults), $totalResults > 0);
		self::assertTrue(isset($results['scoredResults']));
		$scoredResults = $results['scoredResults'];
		self::assertTrue(is_array($scoredResults));
		self::assertTrue(!empty($scoredResults));
		self::assertTrue(in_array('3', $scoredResults));

		// Check highlighting results.
		self::assertTrue(isset($results['highlightedArticles']));
		$highlightedArticles = $results['highlightedArticles'];
		self::assertTrue(is_array($highlightedArticles));
		self::assertTrue(!empty($highlightedArticles));
		self::assertTrue(isset($highlightedArticles['3']));
		self::assertContains('Lucene Test <em>Article</em> 1 Abstract', $highlightedArticles['3']);
		$searchRequest->setHighlighting(false);

		// Test result set ordering via simple (default field) search.
		$searchRequest->setQuery(array('title' => 'lucene'));
		$searchRequest->setOrderBy('authors');
		$searchRequest->setOrderDir('asc');
		$results = $this->solrWebService->retrieveResults($searchRequest, $totalResults);
		$scoredResults = (isset($results['scoredResults']) ? $results['scoredResults'] : null);
		self::assertEquals(array(4, 3), array_values($scoredResults));
		$searchRequest->setOrderBy('title');
		$searchRequest->setOrderDir('desc');
		$searchRequest->setFacetCategories(
			array('discipline', 'subject', 'coverage', 'journalTitle', 'authors', 'publicationDate')
		);
		$results = $this->solrWebService->retrieveResults($searchRequest, $totalResults);
		$scoredResults = (isset($results['scoredResults']) ? $results['scoredResults'] : null);
		self::assertEquals(array(3, 4), array_values($scoredResults));

		// Check faceting results.
		self::assertTrue(isset($results['facets']));
		$facets = $results['facets'];
		self::assertTrue(is_array($facets));
		$expectedFacets = array(
			// only facets that return at least one result will be shown.
			'discipline' => array('exotic food' => 1, 'dietary research' => 1),
			'subject' => array('lunchtime no lunch' => 1),
			// facets for 'type' were not requested so we shouldn't get a result here
			'coverage' => array('daily probes' => 1, 'the 21st century' => 1, 'world wide' => 1),
			'journalTitle' => array(), // This shows that non-selected facets will not be shown.
			'authors' => array('author, some' => 1, 'authorname, second a' => 1, 'author, another' => 1),
			'publicationDate' => array('2011' => 1, '2012' => 1) // This shows that range queries work.
		);
		self::assertEquals($expectedFacets, $facets);
		$searchRequest->setFacetCategories(array());

		// Test translation of search terms.
		// If the word "und" is not correctly translated to "AND" then
		// the search should return results due to our "implicit OR" strategy.
		// We confirm that by confirming that the two words without the keyword
		// will return something.
		$searchRequest->setQuery(array('galleyFullText' => 'nutella und quatsch'));
		$results = $this->solrWebService->retrieveResults($searchRequest, $totalResults);
		self::assertTrue(empty($results['scoredResults']));
		$searchRequest->setQuery(array('galleyFullText' => 'nutella quatsch'));
		$results = $this->solrWebService->retrieveResults($searchRequest, $totalResults);
		self::assertFalse(empty($results['scoredResults']));

		// Test spelling suggestions.
		$searchRequest->setQuery(array('galleyFullText' => 'Nutela'));
		$results = $this->solrWebService->retrieveResults($searchRequest, $totalResults);
		self::assertTrue(empty($results['spellingSuggestion']));
		$searchRequest->setSpellcheck(true);
		$results = $this->solrWebService->retrieveResults($searchRequest, $totalResults);
		self::assertFalse(empty($results['spellingSuggestion']));
		self::assertEquals('nutella', $results['spellingSuggestion']);
	}

	/**
	 * @covers SolrWebService::getAvailableFields()
	 */
	public function testGetAvailableFields() {
		$this->markTestSkipped('Not currently working in CI environment.');

		$embeddedServer = new EmbeddedServer();
		$this->_startServer($embeddedServer);
		$this->solrWebService->flushFieldCache();
		// Only a few exemplary keys to make sure that we got something useful back.
		$searchFields = $this->solrWebService->getAvailableFields('search');
		foreach(array('authors', 'title', 'galleyFullText') as $fieldName) {
			self::assertArrayHasKey($fieldName, $searchFields, "The search field $fieldName should exist.");
			self::assertFalse(empty($searchFields[$fieldName]), "The search field $fieldName should not be empty.");
		}
		$sortFields = $this->solrWebService->getAvailableFields('sort');
		foreach(array('authors', 'issuePublicationDate') as $fieldName) {
			self::assertArrayHasKey($fieldName, $sortFields, "The sort field $fieldName should exist.");
			self::assertFalse(empty($sortFields[$fieldName]), "The sort field $fieldName should not be empty.");
		}
	}

	/**
	 * @covers SolrWebService::getServerStatus()
	 */
	public function testGetServerStatus() {
		$this->markTestSkipped('Not currently working in CI environment.');

		// Make sure the server has been started.
		$embeddedServer = new EmbeddedServer();
		$result = $this->_startServer($embeddedServer);

		// Test the status message.
		self::assertEquals(SOLR_STATUS_ONLINE, $result['status']);
		self::assertEquals('##plugins.generic.lucene.message.indexOnline##', $result['message']);

		// Stop the server, then test the status again.
		$embeddedServer->stop();
		while($embeddedServer->isRunning()) sleep(1);
		self::assertEquals(SOLR_STATUS_OFFLINE, $this->solrWebService->getServerStatus());
		self::assertEquals('##plugins.generic.lucene.message.searchServiceOffline##', $this->solrWebService->getServiceMessage());

		// Restart the server.
		$result = $this->_startServer($embeddedServer);
	}

	/**
	 * @covers SolrWebService::getArticleListXml()
	 */
	public function testGetArticleListXml() {
		$this->markTestSkipped('Not currently working in CI environment.');

		// Generate test objects.
		$articleToReplace = $this->_getTestArticle();
		$articleToDelete = new PublishedArticle();
		$articleToDelete->setId(99);
		$articleToDelete->setJournalId(2);
		$articleToDelete->setIssueId(2);
		$articleToDelete->setSectionId(2);
		$articleToDelete->setStatus(STATUS_DECLINED);
		$articles = array($articleToReplace, $articleToDelete);

		// Test the transfer XML file.
		$deletedCount = null;
		$articleXml = $this->solrWebService->_getArticleListXml($articles, 3, $deletedCount);
		$expectedXml = file_get_contents('tests/plugins/generic/lucene/classes/test-article.xml');
		$expectedXml = str_replace('%%test-url%%', Config::getVar('debug', 'webtest_base_url'), $expectedXml);
		self::assertXmlStringEqualsXmlString($expectedXml, $articleXml);
		self::assertEquals(1, $deletedCount);
	}

	/**
	 * @covers SolrWebService::deleteArticleFromIndex()
	 */
	public function testDeleteArticleFromIndex() {
		$this->articleInIndex(3);
		self::assertTrue($this->solrWebService->deleteArticleFromIndex(3));
		$this->articleNotInIndex(3);
	}

	/**
	 * @covers SolrWebService::markArticleChanged()
	 * @covers SolrWebService::pushChangedArticles()
	 */
	public function testPushIndexing() {
		$this->markTestSkipped('Not currently working in CI environment.');

		// Test indexing. The service returns true if the article
		// was successfully processed.
		$this->articleNotInIndex(3);
		$this->solrWebService->markArticleChanged(3);
		self::assertEquals(1, $this->solrWebService->pushChangedArticles());
		$this->articleInIndex(3);
	}

	/**
	 * @covers SolrWebService::deleteArticlesFromIndex()
	 */
	public function testDeleteAllArticlesFromIndex() {
		// Check that the articles we are deleting are in the index
		// before we delete them.
		$this->articleInIndex(1);
		$this->articleInIndex(9);

		// Delete the articles from one journal.
		self::assertTrue($this->solrWebService->deleteArticlesFromIndex(2));
		$this->articleInIndex(1);
		$this->articleNotInIndex(9);

		// Delete all remaining articles from the index.
		self::assertTrue($this->solrWebService->deleteArticlesFromIndex());
		$this->articleNotInIndex(1);
		$this->articleNotInIndex(9);

		// Clean up the mess and re-build the index. ;-)
		$this->_indexTestJournals();
	}

	/**
	 * @covers SolrWebService::getAutosuggestions()
	 */
	public function testGetAutosuggestions() {
		$this->markTestSkipped('Not currently working in CI environment.');

		// Fake a search request.
		$searchRequest = new SolrSearchRequest();
		$journal = new Journal();
		$journal->setId(2);
		$searchRequest->setJournal($journal);
		$searchRequest->setQuery(
			array('authors' => 'McAutomatic')
		);

		// Only the last word should be corrected. This also
		// checks whether suggestions come from different fields.
		self::assertEquals(
			array('chic (AND wings', 'chic (AND wide'),
			$this->solrWebService->getAutosuggestions(
				$searchRequest, 'query', 'chic (AND wi', SOLR_AUTOSUGGEST_SUGGESTER
			)
		);

		// The faceting component will return no suggestions for
		// the same query as it wouldn't return any results.
		self::assertEquals(
			array(),
			$this->solrWebService->getAutosuggestions(
				$searchRequest, 'query', 'chic (AND wi', SOLR_AUTOSUGGEST_FACETING
			)
		);

		// Even when we correct the first word, we'll not get
		// results due to the fact that there's no such article
		// with the author given in the search request.
		self::assertEquals(
			array(),
			$this->solrWebService->getAutosuggestions(
				$searchRequest, 'query', 'chicken (AND wi', SOLR_AUTOSUGGEST_FACETING
			)
		);

		// We start to get a result from the faceting component
		// when we enter another author. But the result will only
		// include suggestions that actually return something.
		$searchRequest->setQuery(array('authors' => 'Peter Poultry'));
		self::assertEquals(
			array('chicken (AND wings'),
			$this->solrWebService->getAutosuggestions(
				$searchRequest, 'query', 'chicken (AND wi', SOLR_AUTOSUGGEST_FACETING
			)
		);

		$searchRequest->setQuery(array());
		foreach(array(SOLR_AUTOSUGGEST_FACETING, SOLR_AUTOSUGGEST_SUGGESTER) as $autosuggestType) {
			// When the last word cannot be improved then
			// return no results.
			self::assertEquals(
				array(),
				$this->solrWebService->getAutosuggestions(
					$searchRequest, 'query', 'chicken AND dslgkhsi', $autosuggestType
				)
			);

			// Check whether results for index term suggestions
			// come from different sources (but not all sources).
			// The following search should not return 'lucene' for
			// example, which would come from a non-index field.
			self::assertEquals(
				array('lunch', 'lunchtime'),
				$this->solrWebService->getAutosuggestions(
					$searchRequest, 'indexTerms', 'lu', $autosuggestType
				)
			);
		}

		// Check one of the "simple" search fields, e.g. authors.
		// This also shows that the suggester will propose terms
		// from other journals. The author "tester" does not appear
		// in the journal chosen for the search request above.
		self::assertEquals(
			array('tester'),
			$this->solrWebService->getAutosuggestions(
				$searchRequest, 'authors', 'tes', SOLR_AUTOSUGGEST_SUGGESTER
			)
		);

		// In the case of the faceting component this should not return
		// any result as the author comes from a different journal.
		self::assertEquals(
			array(),
			$this->solrWebService->getAutosuggestions(
				$searchRequest, 'authors', 'tes', SOLR_AUTOSUGGEST_FACETING
			)
		);

		// This changes when we look for author names that
		// exist in the journal.
		self::assertEquals(
			array('author', 'authorname'),
			$this->solrWebService->getAutosuggestions(
				$searchRequest, 'authors', 'au', SOLR_AUTOSUGGEST_FACETING
			)
		);
	}

	/**
	 * @covers SolrWebService::getInterestingTerms()
	 */
	public function testGetInterestingTerms() {
		$this->markTestSkipped('Not currently working in CI environment.');

		$actualTerms = $this->solrWebService->getInterestingTerms(2);
		self::assertEquals(array(), $actualTerms);
		$expectedTerms = array('ranking', 'article', 'test');
		$actualTerms = $this->solrWebService->getInterestingTerms(10);
		self::assertEquals($expectedTerms, $actualTerms);
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
		} while ($result != SOLR_STATUS_ONLINE);
		return array(
			'status' => $result,
			'message' => $this->solrWebService->getServiceMessage()
		);
	}

	/**
	 * Mock and register a ArticleGalleyDAO as a test
	 * back end for the SolrWebService class.
	 */
	private function _registerMockArticleGalleyDAO() {
		// Mock an ArticleGalleyDAO.
		$galleyDao = $this->getMock('ArticleGalleyDAO', array('getBySubmissionId'), array(), '', false);

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
		$galleyDao->expects($this->any())
		          ->method('getGalleysByArticle')
		          ->will($this->returnValue($galleys));
		// FIXME: ArticleGalleyDAO::getBySubmissionId returns iterator; array expected here. Fix expectations.

		// Register the mock DAO.
		DAORegistry::registerDAO('ArticleGalleyDAO', $galleyDao);
	}

	/**
	 * Mock and register an AuthorDAO as a test
	 * back end for the SolrWebService class.
	 */
	private function _registerMockAuthorDAO() {
		// Mock an AuthorDAO.
		$authorDao = $this->getMock('AuthorDAO', array('getBySubmissionId'), array(), '', false);

		// Mock a list of authors.
		$author1 = new Author();
		$author1->setFirstName('First');
		$author1->setLastName('Author');
		$author2 = new Author();
		$author2->setFirstName('Second');
		$author2->setMiddleName('M.');
		$author2->setLastName('Name');
		$authors = array($author1, $author2);

		// Mock the getBySubmissionId() method.
		$authorDao->expects($this->any())
		          ->method('getBySubmissionId')
		          ->will($this->returnValue($authors));

		// Register the mock DAO.
		DAORegistry::registerDAO('AuthorDAO', $authorDao);
	}

	/**
	 * Mock and register an IssueDAO as a test
	 * back end for the SolrWebService class.
	 */
	private function _registerMockIssueDAO() {
		// Mock an IssueDAO.
		$issueDao = $this->getMock('IssueDAO', array('getById'), array(), '', false);

		// Mock an issue.
		$issue = $issueDao->newDataObject();
		$issue->setDatePublished('2012-03-15 15:30:00');
		$issue->setPublished(true);

		// Mock the getById() method.
		$issueDao->expects($this->any())
		         ->method('getById')
		         ->will($this->returnValue($issue));

		// Register the mock DAO.
		DAORegistry::registerDAO('IssueDAO', $issueDao);
	}

	/**
	 * Mock and register an JournalDAO as a test
	 * back end for the SolrWebService class.
	 */
	private function _registerMockJournalDAO() {
		// Mock a JournalDAO.
		$journalDao = $this->getMock('JournalDAO', array('getById'), array(), '', false);

		// Mock a journal.
		$journal = $this->_getTestJournal();

		// Mock the getById() method.
		$journalDao->expects($this->any())
		           ->method('getById')
		           ->will($this->returnValue($journal));

		// Register the mock DAO.
		DAORegistry::registerDAO('JournalDAO', $journalDao);
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
		$this->_registerMockIssueDAO();
		$this->_registerMockJournalDAO();
		$this->_registerMockArticleGalleyDAO();

		// Create a test article.
		$article = new PublishedArticle();
		$article->setId(3);
		$article->setJournalId(2);
		$article->setIssueId(2);
		$article->setSectionId(1);
		$article->setStatus(STATUS_PUBLISHED);
		$article->setTitle('Deutscher Titel', 'de_DE');
		$article->setTitle('English Title', 'en_US');
		$article->setAbstract('Deutsche Zusammenfassung', 'de_DE');
		$article->setAbstract('English Abstract', 'en_US');
		$article->setDiscipline('Sozialwissenschaften', 'de_DE');
		$article->setDiscipline('Social Sciences', 'en_US');
		$article->setSubject('Thema', 'de_DE');
		$article->setSubject('subject', 'en_US');
		$article->setType('Typ', 'de_DE');
		$article->setType('type', 'en_US');
		$article->setDatePublished('2012-03-15 16:45:00');
		$article->setLocale('de_DE');
		return $article;
	}

	/**
	 * Return a test journal.
	 *
	 * @return Journal
	 */
	private function _getTestJournal() {
		// Generate a test journal.
		$journal = $this->getMock('Journal', array('getSetting'));
		$journal->setId('2');
		$journal->setPath('lucene-test');
		$journal->setData(
			'supportedLocaleNames',
			array(
				'en_US' => 'English',
				'de_DE' => 'German',
				'fr_FR' => 'French'
			)
		);
		$journal->expects($this->any())
		        ->method('getSetting')
		        ->will($this->returnCallback(array($this, 'journalGetSettingCallback')));
		return $journal;
	}

	/**
	 * A callback mocking the Journal::getSetting() method.
	 * @param $name string
	 * @param $locale string
	 * @return string
	 */
	public function &journalGetSettingCallback($name, $locale) {
		$titleValues = array(
			'de_DE' => 'Zeitschrift',
			'en_US' => 'Journal'
		);
		if ($name == 'name' && isset($titleValues[$locale])) {
			return $titleValues[$locale];
		}
		$nullVar = null;
		return $nullVar;
	}

	/**
	 * Index the test journal (and test that this actually works).
	 */
	private function _indexTestJournals() {
		// Test indexing. The service returns the number of documents that
		// were successfully processed.
		self::assertGreaterThan(0, $this->solrWebService->markJournalChanged(1));
		self::assertGreaterThan(1, $this->solrWebService->markJournalChanged(2));
		self::assertGreaterThan(1, $this->solrWebService->pushChangedArticles());
		$this->solrWebService->rebuildDictionaries();
	}

	/**
	 * Check that the given article is indexed.
	 * @param $articleId integer
	 */
	private function articleInIndex($articleId) {
		$this->markTestSkipped('Not currently working in CI environment.');

		$article = $this->solrWebService->getArticleFromIndex($articleId);
		self::assertFalse(empty($article));
	}

	/**
	 * Check that the given article is not indexed.
	 * @param $articleId integer
	 */
	private function articleNotInIndex($articleId) {
		$article = $this->solrWebService->getArticleFromIndex($articleId);
		self::assertTrue(empty($article));
	}

	/**
	 * @see DAO::getAdditionalFieldNames()
	 */
	function callbackAdditionalFieldNames($hookName, $args) {
		$returner =& $args[1];
		$returner[] = 'indexingState';
	}
}

