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


import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.PKPRouter');
import('classes.journal.Journal');
import('plugins.generic.lucene.LucenePlugin');
import('plugins.generic.lucene.SolrWebService');


class LucenePluginTest extends PKPTestCase {

	/** @var LucenePlugin */
	private $lucenePlugin;


	//
	// Implementing protected template methods from PKPTestCase
	//
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
			$router = new PKPRouter();
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
	 */
	public function testCallbackRetrieveResults() {
		// Test data.
		$journal = new Journal();
		$testCases = array(
			// Simple Searches
			array(null => 'test query'),
			array(ARTICLE_SEARCH_AUTHOR => 'author'),
			array(ARTICLE_SEARCH_TITLE => 'title'),
			array(ARTICLE_SEARCH_ABSTRACT => 'abstract'),
			array(ARTICLE_SEARCH_INDEX_TERMS => 'index terms'),
			array(ARTICLE_SEARCH_GALLEY_FILE => 'full text'),
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
		$fromDate = '2000-01-01 00:00:00';

		$expectedResults = array(
			array(null => 'test query'),
			array('authors' => 'author'),
			array('title' => 'title'),
			array('abstract' => 'abstract'),
			array(
				'discipline' => 'index terms',
				'subject' => 'index terms',
				'type' => 'index terms',
				'coverage' => 'index terms'
			),
			array('galley_full_text' => 'full text'),
			array(
				null => 'test query',
				'authors' => 'author',
				'title' => 'title',
				'discipline' => 'discipline',
				'subject' => 'subject',
				'type' => 'type',
				'coverage' => 'coverage',
				'galley_full_text' => 'full text',
				'suppFile_full_text' => 'supplementary files'
			),
		);

		$hook = 'ArticleSearch::retrieveResults';

		foreach($testCases as $testNum => $testCase) {
			// Mock a SolrWebService.
			$webService = $this->getMock('SolrWebService', array('retrieveResults'), array(), '', false);

			// Only the "index term" search uses "OR" as default operator.
			if (isset($testCase[ARTICLE_SEARCH_INDEX_TERMS])) {
				$defaultOperator = 'OR';
			} else {
				$defaultOperator = 'AND';
			}

			// Check whether the Lucene plug-in calls the web service
			// with the right parameters.
			$webService->expects($this->once())
			           ->method('retrieveResults')
			           ->with($this->equalTo($expectedResults[$testNum]),
			                  $this->equalTo('2000-01-01T00:00:00Z'),
			                  $this->equalTo(null),
			                  $this->equalTo($defaultOperator));
			$this->lucenePlugin->_solrWebService = $webService;

			// Execute the test.
			$params = array($journal, $testCase, $fromDate, null);
			$this->lucenePlugin->callbackRetrieveResults($hook, $params);
		}
	}
}
?>