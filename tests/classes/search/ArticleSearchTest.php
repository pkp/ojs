<?php

/**
 * @file tests/classes/search/ArticleSearchTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearchTest
 * @ingroup tests_classes_search
 * @see ArticleSearch
 *
 * @brief Test class for the ArticleSearch class
 */

require_mock_env('env1');

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.ArrayItemIterator');
import('classes.search.ArticleSearch');
import('lib.pkp.classes.core.PKPRouter');

define('SUBMISSION_SEARCH_TEST_DEFAULT_ARTICLE', 1);
define('SUBMISSION_SEARCH_TEST_ARTICLE_FROM_PLUGIN', 2);

class ArticleSearchTest extends PKPTestCase {
	/** @var array */
	private $_retrieveResultsParams;

	//
	// Implementing protected template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::getMockedDAOs()
	 */
	protected function getMockedDAOs() {
		$mockedDaos = parent::getMockedDAOs();
		$mockedDaos += array(
			'ArticleSearchDAO', 'SubmissionDAO',
			'IssueDAO', 'JournalDAO', 'SectionDAO'
		);
		return $mockedDaos;
	}

	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() : void {
		parent::setUp();
		HookRegistry::rememberCalledHooks();

		// Prepare the mock environment for this test.
		$this->registerMockArticleSearchDAO();
		$this->registerMockSubmissionDAO();
		$this->registerMockIssueDAO();
		$this->registerMockJournalDAO();
		$this->registerMockSectionDAO();

		$request = Application::get()->getRequest();
		if (is_null($request->getRouter())) {
			$router = new PKPRouter();
			$request->setRouter($router);
		}
	}

	/**
	 * @see PKPTestCase::tearDown()
	 */
	protected function tearDown() : void {
		HookRegistry::resetCalledHooks();
		parent::tearDown();
	}


	//
	// Unit tests
	//
	/**
	 * @covers ArticleSearch
	 */
	public function testRetrieveResults() {
		$this->markTestSkipped(); // Temporarily disabled!

		// Make sure that no hook is being called.
		HookRegistry::clear('SubmissionSearch::retrieveResults');

		// Test a simple search with a mock database back-end.
		$journal = new Journal();
		$keywords = array(null => 'test');
		$articleSearch = new ArticleSearch();
		$error = '';
		$request = Application::get()->getRequest();
		$searchResult = $articleSearch->retrieveResults($request, $journal, $keywords, $error);

		// Test whether the result from the mocked DAOs is being returned.
		self::assertInstanceOf('ItemIterator', $searchResult);
		$firstResult = $searchResult->next();
		self::assertArrayHasKey('article', $firstResult);
		self::assertEquals(SUBMISSION_SEARCH_TEST_DEFAULT_ARTICLE, $firstResult['article']->getId());
		self::assertEquals('', $error);

		// Make sure that articles from unpublished issues will
		// be filtered out.
		$this->registerMockIssueDAO(false);
		$this->registerMockArticleSearchDAO(); // This is necessary to instantiate a fresh iterator.
		$keywords = array(null => 'test');
		$searchResult = $articleSearch->retrieveResults($request, $journal, $keywords, $error);
		self::assertTrue($searchResult->eof());
	}

	/**
	 * @covers ArticleSearch
	 */
	public function testRetrieveResultsViaPluginHook() {
		$this->markTestSkipped(); // Temporarily disabled!

		// Diverting a search to the search plugin hook.
		HookRegistry::register('SubmissionSearch::retrieveResults', array($this, 'callbackRetrieveResults'));

		$testCases = array(
			array (null => 'query'), // Simple Search - "All"
			array ('1' => 'author'), // Simple Search - "Authors"
			array ('2' => 'title'), // Simple Search - "Title"
			array (
				null => 'query',
				1 => 'author',
				2 => 'title'
			), // Advanced Search
		);

		$testFromDate = date('Y-m-d H:i:s', strtotime('2011-03-15 00:00:00'));
		$testToDate = date('Y-m-d H:i:s', strtotime('2012-03-15 18:30:00'));
		$error = '';

		$request = Application::get()->getRequest();

		foreach($testCases as $testCase) {
			// Test a simple search with the simulated callback.
			$journal = new Journal();
			$keywords = $testCase;
			$articleSearch = new ArticleSearch();
			$searchResult = $articleSearch->retrieveResults($request, $journal, $keywords, $error, $testFromDate, $testToDate);

			// Check the parameters passed into the callback.
			$expectedPage = 1;
			$expectedItemsPerPage = 20;
			$expectedTotalResults = 3;
			$expectedError = '';
			$expectedParams = array(
				$journal, $testCase, $testFromDate, $testToDate,
				$expectedPage, $expectedItemsPerPage, $expectedTotalResults,
				$expectedError
			);
			self::assertEquals($expectedParams, $this->_retrieveResultsParams);

			// Test and clear the call history of the hook registry.
			$calledHooks = HookRegistry::getCalledHooks();
			self::assertEquals('SubmissionSearch::retrieveResults', $calledHooks[0][0]);
			HookRegistry::resetCalledHooks(true);

			// Test whether the result from the hook is being returned.
			self::assertInstanceOf('VirtualArrayIterator', $searchResult);

			// Test the total count.
			self::assertEquals(3, $searchResult->getCount());

			// Test the search result.
			$firstResult = $searchResult->next();
			self::assertArrayHasKey('article', $firstResult);
			self::assertEquals(SUBMISSION_SEARCH_TEST_ARTICLE_FROM_PLUGIN, $firstResult['article']->getId());
			self::assertEquals('', $error);
		}

		// Remove the test hook.
		HookRegistry::clear('SubmissionSearch::retrieveResults');
	}


	//
	// Public callback methods
	//
	/**
	 * Simulate a search plug-ins "retrieve results" hook.
	 * @see SubmissionSearch::retrieveResults()
	 */
	public function callbackRetrieveResults($hook, $params) {
		// Save the test parameters
		$this->_retrieveResultsParams = $params;

		// Test returning count by-ref.
		$totalCount =& $params[6];
		$totalCount = 3;

		// Mock a result set and return it.
		$results = array(
			3 => SUBMISSION_SEARCH_TEST_ARTICLE_FROM_PLUGIN
		);
		return $results;
	}

	/**
	 * Callback dealing with SubmissionDAO::getArticle()
	 * calls via our mock SubmissionDAO.
	 *
	 * @see SubmissionDAO::getArticle()
	 */
	public function callbackGetArticle($articleId, $journalId = null, $useCache = false) {
		// Create an article instance with the correct id.
		$article = new Submission();
		$article->setId($articleId);
		return $article;
	}


	//
	// Private helper methods
	//
	/**
	 * Mock and register an ArticleSearchDAO as a test
	 * back end for the ArticleSearch class.
	 */
	private function registerMockArticleSearchDAO() {
		// Mock an ArticleSearchDAO.
		$articleSearchDAO = $this->getMockBuilder(ArticleSearchDAO::class)
			->setMethods(array('getPhraseResults'))
			->getMock();

		// Mock a result set.
		$searchResult = array(
			SUBMISSION_SEARCH_TEST_DEFAULT_ARTICLE => array(
				'count' => 3,
				'journal_id' => 2,
				'issuePublicationDate' => '2013-05-01 20:30:00',
				'publicationDate' => '2013-05-01 20:30:00'
			)
		);

		// Mock the getPhraseResults() method.
		$articleSearchDAO->expects($this->any())
		                 ->method('getPhraseResults')
		                 ->will($this->returnValue($searchResult));

		// Register the mock DAO.
		DAORegistry::registerDAO('ArticleSearchDAO', $articleSearchDAO);
	}

	/**
	 * Mock and register an SubmissionDAO as a test
	 * back end for the ArticleSearch class.
	 */
	private function registerMockSubmissionDAO() {
		// Mock an SubmissionDAO.
		$submissionDao = $this->getMockBuilder(SubmissionDAO::class)
			->setMethods(array('getArticle'))
			->getMock();

		// Mock an article.
		$article = new Submission();

		// Mock the getArticle() method.
		$submissionDao->expects($this->any())
		           ->method('getArticle')
		           ->will($this->returnCallback(array($this, 'callbackGetArticle')));

		// Register the mock DAO.
		DAORegistry::registerDAO('SubmissionDAO', $submissionDao);
	}

	/**
	 * Mock and register an IssueDAO as a test
	 * back end for the ArticleSearch class.
	 */
	private function registerMockIssueDAO($published = true) {
		// Mock an IssueDAO.
		$issueDAO = $this->getMockBuilder(IssueDAO::class)
			->setMethods(array('getById'))
			->getMock();

		// Mock an issue.
		$issue = $issueDAO->newDataObject();
		$issue->setPublished($published);

		// Mock the getById() method.
		$issueDAO->expects($this->any())
		         ->method('getById')
		         ->will($this->returnValue($issue));

		// Register the mock DAO.
		DAORegistry::registerDAO('IssueDAO', $issueDAO);
	}

	/**
	 * Mock and register an JournalDAO as a test
	 * back end for the ArticleSearch class.
	 */
	private function registerMockJournalDAO() {
		// Mock a JournalDAO.
		$journalDAO = $this->getMockBuilder(JournalDAO::class)
			->setMethods(array('getById'))
			->getMock();

		// Mock a journal.
		$journal = new Journal();

		// Mock the getById() method.
		$journalDAO->expects($this->any())
		           ->method('getById')
		           ->will($this->returnValue($journal));

		// Register the mock DAO.
		DAORegistry::registerDAO('JournalDAO', $journalDAO);
	}

	/**
	 * Mock and register an SectionDAO as a test
	 * back end for the ArticleSearch class.
	 */
	private function registerMockSectionDAO() {
		// Mock a SectionDAO.
		$sectionDAO = $this->getMockBuilder(SectionDAO::class)
			->setMethods(array('getSection'))
			->getMock();

		// Mock a section.
		$section = new Section();

		// Mock the getSection() method.
		$sectionDAO->expects($this->any())
		           ->method('getSection')
		           ->will($this->returnValue($section));

		// Register the mock DAO.
		DAORegistry::registerDAO('SectionDAO', $sectionDAO);
	}
}

