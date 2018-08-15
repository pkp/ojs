<?php

/**
 * @file plugins/generic/lucene/tests/LucenePluginTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LucenePluginTest
 * @ingroup plugins_generic_lucene_tests
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
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		$this->markTestSkipped('Needs fixing.');

		// Instantiate the plug-in for testing.
		$this->mockRequest();
		PluginRegistry::loadCategory('generic', true, 0);
		$this->lucenePlugin = PluginRegistry::getPlugin('generic', 'luceneplugin');
		if (!$this->lucenePlugin) $this->markTestSkipped('Could not fetch Lucene plugin!');
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
			array(SUBMISSION_SEARCH_AUTHOR => 'author'),
			array(SUBMISSION_SEARCH_TITLE => 'title'),
			array(SUBMISSION_SEARCH_ABSTRACT => 'abstract'),
			array(SUBMISSION_SEARCH_INDEX_TERMS => 'Nicht index terms'),
			array(SUBMISSION_SEARCH_GALLEY_FILE => 'full OR text'),
			// Advanced Search
			array(
				null => 'test query',
				SUBMISSION_SEARCH_AUTHOR => 'author',
				SUBMISSION_SEARCH_TITLE => 'title',
				SUBMISSION_SEARCH_DISCIPLINE => 'discipline',
				SUBMISSION_SEARCH_SUBJECT => 'subject',
				SUBMISSION_SEARCH_TYPE => 'type',
				SUBMISSION_SEARCH_COVERAGE => 'coverage',
				SUBMISSION_SEARCH_GALLEY_FILE => 'full text',
			)
		);

		$expectedResults = array(
			array('authors|title|abstract|galleyFullText|discipline|subject|type|coverage' => 'test AND query'),
			array('authors' => 'author'),
			array('title' => 'title'),
			array('abstract' => 'abstract'),
			array('discipline|subject|type|coverage' => 'Nicht index terms'), // Translation is done in the web service now.
			array('galleyFullText' => 'full OR text'),
			array(
				'authors|title|abstract|galleyFullText|discipline|subject|type|coverage' => 'test query',
				'authors' => 'author',
				'title' => 'title',
				'discipline' => 'discipline',
				'subject' => 'subject',
				'type' => 'type',
				'coverage' => 'coverage',
				'galleyFullText' => 'full text',
			),
		);

		$journal = new Journal();
		$fromDate = '2000-01-01 00:00:00';

		$hook = 'SubmissionSearch::retrieveResults';
		$totalResults = null;
		$error = null;

		$facetCategories = array(
			'discipline', 'subject', 'type', 'coverage', 'authors'
			// journal and date should always be missing as we have
			// active journal and date filters for all tests.
		);

		foreach($testCases as $testNum => $testCase) {
			// Build the expected search request.
			$searchRequest = new SolrSearchRequest();
			$searchRequest->setJournal($journal);
			$searchRequest->setFromDate($fromDate);
			$searchRequest->setQuery($expectedResults[$testNum]);
			$searchRequest->setSpellcheck(true);
			$searchRequest->setHighlighting(true);
			// Facets should only be requested for categories that have no
			// active filter.
			$expectedFacetCategories = array_values(array_diff($facetCategories, array_keys($expectedResults[$testNum])));
			$searchRequest->setFacetCategories($expectedFacetCategories);

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
		$originalWebService = $this->lucenePlugin->_solrWebService;
		$this->lucenePlugin->_solrWebService = $webService;
		$params = array($journal, array(null => 'test'), null, null, 1, 25, &$totalResults, &$error);
		$this->assertEquals(array(), $this->lucenePlugin->callbackRetrieveResults($hook, $params));
		$this->assertEquals('some error message ##plugins.generic.lucene.message.techAdminInformed##', $error);
		$this->lucenePlugin->_solrWebService = $originalWebService;
	}

	/**
	 * @covers LucenePlugin
	 */
	public function testArticleIndexingProblem() {
		// Make sure the embedded server is switched off.
		$embeddedServer = new EmbeddedServer();
		$this->assertTrue($embeddedServer->stopAndWait());

		// Mock email templates.
		$constructorArgs = array(
			'LUCENE_ARTICLE_INDEXING_ERROR_NOTIFICATION',
			null, null, null, true, true
		);
		import('lib.pkp.classes.mail.MailTemplate');
		$techInfoMail = $this->getMock('MailTemplate', array('send'), $constructorArgs); /* @var $techInfoMail MailTemplate */
		$techInfoMail->expects($this->exactly(2))
		             ->method('send')
		             ->will($this->returnValue(true));
		$this->lucenePlugin->setMailTemplate('LUCENE_ARTICLE_INDEXING_ERROR_NOTIFICATION', $techInfoMail);

		// Reset the time of the last sent email.
		$this->lucenePlugin->updateSetting(0, 'lastEmailTimestamp', 0);

		// Trying to delete a document without the server running
		// should trigger an email to the tech contact.
		$params = array($articleId = 3, $type = null, $assocId = null);
		$this->lucenePlugin->callbackSubmissionFileDeleted('ArticleSearchIndex::submissionFileDeleted', $params);
		$this->lucenePlugin->callbackArticleChangesFinished('ArticleSearchIndex::articleChangesFinished', array());

		// Check the mail.
		$this->assertEquals('Article Indexing Error', $techInfoMail->getSubject());
		$this->assertContains('An indexing error occurred while updating the article index.', $techInfoMail->getBody());
		$this->assertContains('##plugins.generic.lucene.message.searchServiceOffline##', $techInfoMail->getBody());
		if (Core::isWindows()) {
			$this->assertEquals('jerico.dev@gmail.com', $techInfoMail->getRecipientString());
		} else {
			$this->assertEquals('"Open Journal Systems" <jerico.dev@gmail.com>', $techInfoMail->getRecipientString());
		}
		$this->assertEquals('"Open Journal Systems" <jerico.dev@gmail.com>', $techInfoMail->getFromString());

		// Call again to make sure that a second mail is not being sent.
		$this->lucenePlugin->callbackArticleChangesFinished('ArticleSearchIndex::articleChangesFinished', array());

		// Simulate that the last email is more than three hours ago.
		$this->lucenePlugin->updateSetting(0, 'lastEmailTimestamp', time() - 60 * 60 * 4);

		// This should trigger another email (see send() call count above).
		$this->lucenePlugin->callbackArticleChangesFinished('ArticleSearchIndex::articleChangesFinished', array());

		// Restart the embedded server.
		$this->assertTrue($embeddedServer->start());
	}
}

