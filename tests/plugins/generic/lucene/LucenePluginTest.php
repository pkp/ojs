<?php

/**
 * @file tests/plugins/generic/lucene/LucenePluginTest.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LucenePluginTest
 * @ingroup tests_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Test class for the LucenePlugin class
 */


require_mock_env('env2'); // Required for mock app locale.

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.core.PKPRouter');
import('classes.article.Article');
import('classes.journal.Journal');
import('plugins.generic.lucene.LucenePlugin');
import('plugins.generic.lucene.classes.SolrWebService');
import('plugins.generic.lucene.classes.EmbeddedServer');


class LucenePluginTest extends DatabaseTestCase {

	/** @var LucenePlugin */
	private $lucenePlugin;


	//
	// Implementing protected template methods from DatabaseTestCase
	//
	/**
	 * @see DatabaseTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('plugin_settings');
	}


	//
	// Implementing protected template methods from PKPTestCase
	//
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

		// Instantiate the plug-in for testing.
		$application =& PKPApplication::getApplication();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$request =& $application->getRequest();
		if (is_null($request->getRouter())) {
			$router = $this->getMock('PKPRouter', array('url'));
			$router->expects($this->any())
			       ->method('url')
			       ->will($this->returnValue('http://test-url'));
			$request->setRouter($router);
		}
		PluginRegistry::loadCategory('generic', true, 0);
		$this->lucenePlugin = PluginRegistry::getPlugin('generic', 'luceneplugin');
	}


	//
	// Unit tests
	//
	/**
	 * @covers LucenePlugin
	 * @covers SolrSearchRequest
	 */
	public function testCallbackRetrieveResults() {
		// Test data.
		$testCases = array(
			// Simple Searches
			array(null => 'test AND query'),
			array(ARTICLE_SEARCH_AUTHOR => 'author'),
			array(ARTICLE_SEARCH_TITLE => 'title'),
			array(ARTICLE_SEARCH_ABSTRACT => 'abstract'),
			array(ARTICLE_SEARCH_INDEX_TERMS => 'Nicht index terms'),
			array(ARTICLE_SEARCH_GALLEY_FILE => 'full OR text'),
			// Advanced Search
			array(
				null => 'test query',
				ARTICLE_SEARCH_AUTHOR => 'author',
				ARTICLE_SEARCH_TITLE => 'title',
				ARTICLE_SEARCH_DISCIPLINE => 'discipline',
				ARTICLE_SEARCH_SUBJECT => 'subject',
				ARTICLE_SEARCH_TYPE => 'type',
				ARTICLE_SEARCH_COVERAGE => 'coverage',
				ARTICLE_SEARCH_GALLEY_FILE => 'full text',
				ARTICLE_SEARCH_SUPPLEMENTARY_FILE => 'supplementary files'
			)
		);

		$expectedResults = array(
			array('authors|title|abstract|galleyFullText|suppFiles|discipline|subject|type|coverage' => 'test AND query'),
			array('authors' => 'author'),
			array('title' => 'title'),
			array('abstract' => 'abstract'),
			array('discipline|subject|type|coverage' => 'Nicht index terms'), // Translation is done in the web service now.
			array('galleyFullText' => 'full OR text'),
			array(
				'authors|title|abstract|galleyFullText|suppFiles|discipline|subject|type|coverage' => 'test query',
				'authors' => 'author',
				'title' => 'title',
				'discipline' => 'discipline',
				'subject' => 'subject',
				'type' => 'type',
				'coverage' => 'coverage',
				'galleyFullText' => 'full text',
				'suppFiles' => 'supplementary files'
			),
		);

		$journal = new Journal();
		$fromDate = '2000-01-01 00:00:00';

		$hook = 'ArticleSearch::retrieveResults';
		$totalResults = null;
		$error = null;

		foreach($testCases as $testNum => $testCase) {
			// Build the expected search request.
			$searchRequest = new SolrSearchRequest();
			$searchRequest->setJournal($journal);
			$searchRequest->setFromDate($fromDate);
			$searchRequest->setQuery($expectedResults[$testNum]);

			// Mock a SolrWebService.
			$webService = $this->getMock('SolrWebService', array('retrieveResults'), array(), '', false);

			// Check whether the Lucene plug-in calls the web service
			// with the right parameters.
			$webService->expects($this->once())
			           ->method('retrieveResults')
			           ->with($this->equalTo($searchRequest),
			                  $this->equalTo($totalResults));
			$this->lucenePlugin->_solrWebService = $webService;
			unset($webService, $searchRequest);

			// Execute the test.
			$params = array($journal, $testCase, $fromDate, null, 1, 25, &$totalResults, &$error);
			$this->lucenePlugin->callbackRetrieveResults($hook, $params);
		}

		// Test an error condition.
		$webService = $this->getMock('SolrWebService', array('retrieveResults', 'getServiceMessage'), array(), '', false);
		$webService->expects($this->once())
		           ->method('retrieveResults')
		           ->will($this->returnValue(null));
		$webService->expects($this->any())
		           ->method('getServiceMessage')
		           ->will($this->returnValue('some error message'));
		$this->lucenePlugin->_solrWebService = $webService;
		$params = array($journal, array(null => 'test'), null, null, 1, 25, &$totalResults, &$error);
		$this->assertEquals(array(), $this->lucenePlugin->callbackRetrieveResults($hook, $params));
		$this->assertEquals('some error message', $error);
	}

	/**
	 * @covers LucenePlugin
	 */
	public function testArticleIndexingProblem() {
		// Make sure the embedded server is switched off.
		$embeddedServer = new EmbeddedServer();
		$this->assertTrue($embeddedServer->stopAndWait());

		// Mock email templates.
		$article = new Article();
		$article->setId(3);
		$article->setTitle('test article', 'en_US');
		$journal = new Journal();
		$journal->setId(2);
		$constructorArgs = array(
			$article,
			'LUCENE_ARTICLE_INDEXING_ERROR_NOTIFICATION',
			null, null, $journal, true, true
		);
		import('classes.mail.ArticleMailTemplate');
		$articleMail = $this->getMock('ArticleMailTemplate', array('send'), $constructorArgs); /* @var $articleMail ArticleMailTemplate */
		$articleMail->expects($this->exactly(2))
		            ->method('send')
		            ->will($this->returnValue(true));
		$this->lucenePlugin->setMailTemplate('LUCENE_ARTICLE_INDEXING_ERROR_NOTIFICATION', $articleMail);

		// Reset the time of the last sent email.
		$this->lucenePlugin->updateSetting(0, 'lastEmailTimestamp', 0);

		// Trying to delete a document without the server running
		// should trigger an email to the tech contact.
		$params = array($articleId = 3, $type = null, $assocId = null);
		$this->lucenePlugin->callbackDeleteTextIndex('ArticleSearchIndex::articleFileDeleted', $params);

		// Check the mail.
		$this->assertEquals('[] Article Indexing Error', $articleMail->getSubject());
		$this->assertContains('An indexing error occurred while indexing the article "test article" in the journal "lucene-test".', $articleMail->getBody());
		$this->assertEquals('"Open Journal Systems" <jerico.dev@gmail.com>', $articleMail->getRecipientString());
		$this->assertEquals('"Open Journal Systems" <jerico.dev@gmail.com>', $articleMail->getFromString());

		// Call again to make sure that a second mail is not being sent.
		$this->lucenePlugin->callbackDeleteTextIndex('ArticleSearchIndex::articleFileDeleted', $params);

		// Simulate that the last email is more than three hours ago.
		$this->lucenePlugin->updateSetting(0, 'lastEmailTimestamp', time() - 60 * 60 * 4);

		// This should trigger another email (see send() call count above).
		$this->lucenePlugin->callbackDeleteTextIndex('ArticleSearchIndex::articleFileDeleted', $params);

		// Restart the embedded server.
		$this->assertTrue($embeddedServer->start());
	}
}
?>