<?php

/**
 * @file tests/classes/search/ArticleSearchTest.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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

class ArticleSearchTest extends PKPTestCase {

	//
	// Implementing protected template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::getMockedDAOs()
	 */
	protected function getMockedDAOs() {
		$mockedDAOs = parent::getMockedDAOs();
		$mockedDAOs += array(
			'ArticleSearchDAO', 'ArticleDAO', 'PublishedArticleDAO',
			'IssueDAO', 'JournalDAO', 'SectionDAO'
		);
		return $mockedDAOs;
	}

	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// Prepare the mock environment for this test.
		$this->registerMockArticleSearchDAO();
		$this->registerMockArticleDAO();
		$this->registerMockPublishedArticleDAO();
		$this->registerMockIssueDAO();
		$this->registerMockJournalDAO();
		$this->registerMockSectionDAO();
	}


	//
	// Unit tests
	//
	/**
	 * @covers ArticleSearch
	 */
	public function testRetrieveResults() {
		// Test a simple search with a mock database back-end.
		$journal = new Journal();
		$keywords = array(
			array('+' => array(array('test')), '' => array(), '-' => array())
		);
		$articleSearch = new ArticleSearch();
		self::assertInstanceOf('ItemIterator', $articleSearch->retrieveResults($journal, $keywords));
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
		$articleSearchDAO = $this->getMock('ArticleSearchDAO', array('getPhraseResults'));

		// Mock a result set.
		$searchResult = array(
			array('article_id' => 1, 'count' => 1),
			array('article_id' => 2, 'count' => 3)
		);
		$searchResultIterator = new ArrayItemIterator($searchResult);

		// Mock the getPhraseResults() method.
		$articleSearchDAO->expects($this->any())
		                 ->method('getPhraseResults')
		                 ->will($this->returnValue($searchResultIterator));

		// Register the mock DAO.
		DAORegistry::registerDAO('ArticleSearchDAO', $articleSearchDAO);
	}

	/**
	 * Mock and register an ArticleDAO as a test
	 * back end for the ArticleSearch class.
	 */
	private function registerMockArticleDAO() {
		// Mock an ArticleDAO.
		$articleDAO = $this->getMock('ArticleDAO', array('getArticle'));

		// Mock an article.
		$article = new Article();

		// Mock the getArticle() method.
		$articleDAO->expects($this->any())
		           ->method('getArticle')
		           ->will($this->returnValue($article));

		// Register the mock DAO.
		DAORegistry::registerDAO('ArticleDAO', $articleDAO);
	}

	/**
	 * Mock and register an PublishedArticleDAO as a test
	 * back end for the ArticleSearch class.
	 */
	private function registerMockPublishedArticleDAO() {
		// Mock a PublishedArticleDAO.
		$publishedArticleDAO = $this->getMock('PublishedArticleDAO', array('getPublishedArticleByArticleId'));

		// Mock a published article.
		$publishedArticle = new PublishedArticle();

		// Mock the getPublishedArticleByArticleId() method.
		$publishedArticleDAO->expects($this->any())
		                    ->method('getPublishedArticleByArticleId')
		                    ->will($this->returnValue($publishedArticle));

		// Register the mock DAO.
		DAORegistry::registerDAO('PublishedArticleDAO', $publishedArticleDAO);
	}

	/**
	 * Mock and register an IssueDAO as a test
	 * back end for the ArticleSearch class.
	 */
	private function registerMockIssueDAO() {
		// Mock an IssueDAO.
		$issueDAO = $this->getMock('IssueDAO', array('getIssueById'));

		// Mock an issue.
		$issue = new Issue();

		// Mock the getIssueById() method.
		$issueDAO->expects($this->any())
		         ->method('getIssueById')
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
		$journalDAO = $this->getMock('JournalDAO', array('getById'));

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
		$sectionDAO = $this->getMock('SectionDAO', array('getSection'));

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
?>