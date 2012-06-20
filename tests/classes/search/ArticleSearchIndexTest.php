<?php

/**
 * @file tests/classes/search/ArticleSearchIndexTest.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearchIndexTest
 * @ingroup tests_classes_search
 * @see ArticleSearchIndex
 *
 * @brief Test class for the ArticleSearchIndex class
 */


import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.ArrayItemIterator');
import('classes.search.ArticleSearchIndex');

class ArticleSearchIndexTest extends PKPTestCase {

	//
	// Implementing protected template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::getMockedDAOs()
	 */
	protected function getMockedDAOs() {
		$mockedDaos = parent::getMockedDAOs();
		$mockedDaos += array(
			'ArticleSearchDAO', 'JournalDAO'
		);
		return $mockedDaos;
	}

	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// Prepare the mock environment for this test.
		$this->registerMockArticleSearchDAO();
		$this->registerMockJournalDAO();
	}

	/**
	 * @see PKPTestCase::tearDown()
	 */
	protected function tearDown() {
		HookRegistry::resetCalledHooks();
		parent::tearDown();
	}


	//
	// Unit tests
	//
	/**
	 * @covers ArticleSearchIndex
	 */
	public function testRebuildIndex() {
		// Make sure that no hook is being called.
		HookRegistry::clear('ArticleSearchIndex::rebuildIndex');

		// Test log output.
		$this->expectOutputString("Clearing index ... done\n");

		// Test rebuilding the index with a mock database back-end.
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->rebuildIndex(true);
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testRebuildIndexViaPluginHook() {
		// Diverting to the search plugin hook.
		HookRegistry::register('ArticleSearchIndex::rebuildIndex', array($this, 'callbackRebuildIndex'));

		// Test log output.
		$this->expectOutputString("Some log message from the plug-in.");

		// Simulate rebuilding the index via hook.
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->rebuildIndex(true); // With log
		$articleSearchIndex->rebuildIndex(false); // Without log (that's why we expect the log message to appear only once).

		// Remove the test hook.
		HookRegistry::clear('ArticleSearchIndex::rebuildIndex');
	}


	//
	// Public callback methods
	//
	/**
	 * Simulate a search plug-ins "rebuild index" hook.
	 * @see ArticleSearchIndex::rebuildIndex()
	 */
	public function callbackRebuildIndex($hook, $params) {
		list($log) = $params;
		if ($log) echo "Some log message from the plug-in.";

		// Returning "true" is required so that the default rebuildIndex()
		// code won't run.
		return true;
	}


	//
	// Private helper methods
	//
	/**
	 * Mock and register an ArticleSearchDAO as a test
	 * back end for the ArticleSearchIndex class.
	 */
	private function registerMockArticleSearchDAO() {
		// Mock an ArticleSearchDAO.
		$articleSearchDAO = $this->getMock('ArticleSearchDAO', array('clearIndex'), array(), '', false);

		// Make sure that the clearIndex() method
		// does nothing.
		$articleSearchDAO->expects($this->any())
		                 ->method('clearIndex')
		                 ->will($this->returnValue(null));

		// Register the mock DAO.
		DAORegistry::registerDAO('ArticleSearchDAO', $articleSearchDAO);
	}

	/**
	 * Mock and register an JournalDAO as a test
	 * back end for the ArticleSearchIndex class.
	 */
	private function registerMockJournalDAO() {
		// Mock a JournalDAO.
		$journalDAO = $this->getMock('JournalDAO', array('getJournals'), array(), '', false);

		// Mock an empty result set.
		$journals = array();
		$journalsIterator = new ArrayItemIterator($journals);

		// Mock the getById() method.
		$journalDAO->expects($this->any())
		           ->method('getJournals')
		           ->will($this->returnValue($journalsIterator));

		// Register the mock DAO.
		DAORegistry::registerDAO('JournalDAO', $journalDAO);
	}
}
?>