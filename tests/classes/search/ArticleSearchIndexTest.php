<?php

/**
 * @file tests/classes/search/ArticleSearchIndexTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearchIndexTest
 * @ingroup tests_classes_search
 * @see ArticleSearchIndex
 *
 * @brief Test class for the ArticleSearchIndex class
 */


import('lib.pkp.tests.PKPTestCase');
import('classes.article.Article');
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
			'ArticleSearchDAO', 'JournalDAO',
			'ArticleGalleyDAO'
		);
		return $mockedDaos;
	}

	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		HookRegistry::rememberCalledHooks();
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
	public function testUpdateFileIndexViaPluginHook() {
		// Diverting to the search plugin hook.
		HookRegistry::register('ArticleSearchIndex::submissionFileChanged', array($this, 'callbackUpdateFileIndex'));

		// Simulate updating an article file via hook.
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->submissionFileChanged(0, 1, 2);

		// Test whether the hook was called.
		$calledHooks = HookRegistry::getCalledHooks();
		$lastHook = array_pop($calledHooks);
		self::assertEquals('ArticleSearchIndex::submissionFileChanged', $lastHook[0]);

		// Remove the test hook.
		HookRegistry::clear('ArticleSearchIndex::submissionFileChanged');
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testDeleteTextIndex() {
		// Prepare the mock environment for this test.
		$this->registerMockArticleSearchDAO($this->never(), $this->atLeastOnce());

		// Make sure that no hook is being called.
		HookRegistry::clear('ArticleSearchIndex::submissionFileDeleted');

		// Test deleting an article from the index with a mock database back-end.#
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->submissionFileDeleted(0);
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testDeleteTextIndexViaPluginHook() {
		// Diverting to the search plugin hook.
		HookRegistry::register('ArticleSearchIndex::submissionFileDeleted', array($this, 'callbackDeleteTextIndex'));

		// The search DAO should not be called.
		$this->registerMockArticleSearchDAO($this->never(), $this->never());

		// Simulate deleting article index via hook.
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->submissionFileDeleted(0, 1, 2);

		// Test whether the hook was called.
		$calledHooks = HookRegistry::getCalledHooks();
		$lastHook = array_pop($calledHooks);
		self::assertEquals('ArticleSearchIndex::submissionFileDeleted', $lastHook[0]);

		// Remove the test hook.
		HookRegistry::clear('ArticleSearchIndex::submissionFileDeleted');
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testRebuildIndex() {
		// Prepare the mock environment for this test.
		$this->registerMockArticleSearchDAO($this->atLeastOnce(), $this->never());
		$this->registerMockJournalDAO();

		// Make sure that no hook is being called.
		HookRegistry::clear('ArticleSearchIndex::rebuildIndex');

		// Test log output.
		$this->expectOutputString("##search.cli.rebuildIndex.clearingIndex## ... ##search.cli.rebuildIndex.done##\n");

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

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testIndexArticleMetadata() {
		$this->markTestSkipped(); // Temporarily disabled!

		// Make sure that no hook is being called.
		HookRegistry::clear('ArticleSearchIndex::articleMetadataChanged');

		// Mock an article so that the authors are not
		// being retrieved from the database.
		$article = $this->getMockBuilder(Article::class)
			->setMethods(array('getAuthors'))
			->getMock();
		$article->expects($this->any())
		        ->method('getAuthors')
		        ->will($this->returnValue(array()));

		// Test indexing an article with a mock environment.
		$articleSearchIndex = $this->getMockArticleSearchIndex($this->atLeastOnce());
		$articleSearchIndex->articleMetadataChanged($article);
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testIndexArticleMetadataViaPluginHook() {
		// Diverting to the search plugin hook.
		HookRegistry::register('ArticleSearchIndex::articleMetadataChanged', array($this, 'callbackIndexArticleMetadata'));

		// Simulate indexing via hook.
		$article = new Article();
		$articleSearchIndex = $this->getMockArticleSearchIndex($this->never());
		$articleSearchIndex->articleMetadataChanged($article);

		// Test whether the hook was called.
		$calledHooks = HookRegistry::getCalledHooks();
		self::assertEquals('ArticleSearchIndex::articleMetadataChanged', $calledHooks[0][0]);

		// Remove the test hook.
		HookRegistry::clear('ArticleSearchIndex::articleMetadataChanged');
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testIndexSubmissionFiles() {
		$this->markTestSkipped(); // Temporarily disabled!

		// Make sure that no hook is being called.
		HookRegistry::clear('ArticleSearchIndex::submissionFilesChanged');
		$this->registerFileDAOs(true);

		// Test indexing an article with a mock environment.
		$article = new Article();
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->submissionFilesChanged($article);
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testIndexSubmissionFilesViaPluginHook() {
		// Diverting to the search plugin hook.
		HookRegistry::register('ArticleSearchIndex::submissionFilesChanged', array($this, 'callbackIndexSubmissionFiles'));

		// The file DAOs should not be called.
		$this->registerFileDAOs(false);

		// Simulate indexing via hook.
		$article = new Article();
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->submissionFilesChanged($article);

		// Test whether the hook was called.
		$calledHooks = HookRegistry::getCalledHooks();
		$lastHook = array_pop($calledHooks);
		self::assertEquals('ArticleSearchIndex::submissionFilesChanged', $lastHook[0]);

		// Remove the test hook.
		HookRegistry::clear('ArticleSearchIndex::submissionFilesChanged');
	}


	//
	// Public callback methods
	//
	/**
	 * Simulate a search plug-ins "update file index"
	 * hook.
	 * @see ArticleSearchIndex::submissionFileChanged()
	 */
	public function callbackUpdateFileIndex($hook, $params) {
		self::assertEquals('ArticleSearchIndex::submissionFileChanged', $hook);

		list($articleId, $type, $fileId) = $params;
		self::assertEquals(0, $articleId);
		self::assertEquals(1, $type);
		self::assertEquals(2, $fileId);

		// Returning "true" is required so that the default articleMetadataChanged()
		// code won't run.
		return true;
	}

	/**
	 * Simulate a search plug-ins "delete text index"
	 * hook.
	 * @see ArticleSearchIndex::submissionFileDeleted()
	 */
	public function callbackDeleteTextIndex($hook, $params) {
		self::assertEquals('ArticleSearchIndex::submissionFileDeleted', $hook);

		list($articleId, $type, $assocId) = $params;
		self::assertEquals(0, $articleId);
		self::assertEquals(1, $type);
		self::assertEquals(2, $assocId);

		// Returning "true" is required so that the default articleMetadataChanged()
		// code won't run.
		return true;
	}

	/**
	 * Simulate a search plug-ins "rebuild index" hook.
	 * @see ArticleSearchIndex::rebuildIndex()
	 */
	public function callbackRebuildIndex($hook, $params) {
		self::assertEquals('ArticleSearchIndex::rebuildIndex', $hook);

		list($log) = $params;
		if ($log) echo "Some log message from the plug-in.";

		// Returning "true" is required so that the default rebuildIndex()
		// code won't run.
		return true;
	}

	/**
	 * Simulate a search plug-ins "index article metadata"
	 * hook.
	 * @see ArticleSearchIndex::articleMetadataChanged()
	 */
	public function callbackIndexArticleMetadata($hook, $params) {
		self::assertEquals('ArticleSearchIndex::articleMetadataChanged', $hook);

		list($article) = $params;
		self::assertInstanceOf('Article', $article);

		// Returning "true" is required so that the default articleMetadataChanged()
		// code won't run.
		return true;
	}

	/**
	 * Simulate a search plug-ins "index article files"
	 * hook.
	 * @see ArticleSearchIndex::submissionFilesChanged()
	 */
	public function callbackIndexSubmissionFiles($hook, $params) {
		self::assertEquals('ArticleSearchIndex::submissionFilesChanged', $hook);

		list($article) = $params;
		self::assertInstanceOf('Article', $article);

		// Returning "true" is required so that the default articleMetadataChanged()
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
	private function registerMockArticleSearchDAO($clearIndexExpected, $deleteArticleExpected) {
		// Mock an ArticleSearchDAO.
		$articleSearchDao = $this->getMockBuilder(ArticleSearchDAO::class)
			->setMethods(array('clearIndex', 'deleteSubmissionKeywords'))
			->getMock();

		// Test the clearIndex() method.
		$articleSearchDao->expects($clearIndexExpected)
		                 ->method('clearIndex')
		                 ->will($this->returnValue(null));

		// Test the deleteSubmissionKeywords() method.
		$articleSearchDao->expects($deleteArticleExpected)
		                 ->method('deleteSubmissionKeywords')
		                 ->will($this->returnValue(null));

		// Register the mock DAO.
		DAORegistry::registerDAO('ArticleSearchDAO', $articleSearchDao);
	}

	/**
	 * Mock and register a JournalDAO as a test
	 * back end for the ArticleSearchIndex class.
	 */
	private function registerMockJournalDAO() {
		// Mock a JournalDAO.
		$journalDao = $this->getMockBuilder(JournalDAO::class)
			->setMethods(array('getAll'))
			->getMock();

		// Mock an empty result set.
		$journals = array();
		$journalsIterator = new ArrayItemIterator($journals);

		// Mock the getById() method.
		$journalDao->expects($this->any())
		           ->method('getAll')
		           ->will($this->returnValue($journalsIterator));

		// Register the mock DAO.
		DAORegistry::registerDAO('JournalDAO', $journalDao);
	}

	/**
	 * Mock and register an ArticleGalleyDAO as a test back end for
	 * the ArticleSearchIndex class.
	 */
	private function registerFileDAOs($expectMethodCall) {
		// Mock file DAOs.
		$articleGalleyDao = $this->getMockBuilder(ArticleGalleyDAO::class)
			->setMethods(array('getBySubmissionId'))
			->getMock();

		// Make sure that the DAOs are being called.
		if ($expectMethodCall) {
			$expectation = $this->atLeastOnce();
		} else {
			$expectation = $this->never();
		}
		$articleGalleyDao->expects($expectation)
		                 ->method('getBySubmissionId')
		                 ->will($this->returnValue(array()));
		// FIXME: ArticleGalleyDAO::getBySubmissionId returns iterator; array expected here. Fix expectations.
		DAORegistry::registerDAO('ArticleGalleyDAO', $articleGalleyDao);
	}

	/**
	 * Mock an ArticleSearchIndex implementation.
	 * @return ArticleSearchIndex
	 */
	private function getMockArticleSearchIndex($expectedCall) {
		// Mock ArticleSearchIndex.
		/* @var $articleSearchIndex ArticleSearchIndex */
		$articleSearchIndex = $this->getMockBuilder(ArticleSearchIndex::class)
			->setMethods(array('_updateTextIndex'))
			->getMock();

		// Check for _updateTextIndex() calls.
		$articleSearchIndex->expects($expectedCall)
		                   ->method('_updateTextIndex')
		                   ->will($this->returnValue(null));
		return $articleSearchIndex;
	}
}

