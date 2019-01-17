<?php

/**
 * @file tests/classes/search/ArticleSearchIndexTest.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
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
import('classes.article.SuppFile');
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
			'ArticleSearchDAO', 'JournalDAO', 'SuppFileDAO',
			'ArticleGalleyDAO'
		);
		return $mockedDaos;
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
		$this->markTestSkipped();
		// Diverting to the search plugin hook.
		HookRegistry::register('ArticleSearchIndex::articleFileChanged', array($this, 'callbackUpdateFileIndex'));

		// Simulate updating an article file via hook.
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleFileChanged(0, 1, 2);

		// Test whether the hook was called.
		$calledHooks = HookRegistry::getCalledHooks();
		$lastHook = array_pop($calledHooks);
		self::assertEquals('ArticleSearchIndex::articleFileChanged', $lastHook[0]);

		// Remove the test hook.
		HookRegistry::clear('ArticleSearchIndex::articleFileChanged');
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testDeleteTextIndex() {
		// Prepare the mock environment for this test.
		$this->registerMockArticleSearchDAO($this->never(), $this->atLeastOnce());

		// Make sure that no hook is being called.
		HookRegistry::clear('ArticleSearchIndex::articleFileDeleted');

		// Test deleting an article from the index with a mock database back-end.#
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleFileDeleted(0);
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testDeleteTextIndexViaPluginHook() {
		$this->markTestSkipped();
		// Diverting to the search plugin hook.
		HookRegistry::register('ArticleSearchIndex::articleFileDeleted', array($this, 'callbackDeleteTextIndex'));

		// The search DAO should not be called.
		$this->registerMockArticleSearchDAO($this->never(), $this->never());

		// Simulate deleting article index via hook.
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleFileDeleted(0, 1, 2);

		// Test whether the hook was called.
		$calledHooks = HookRegistry::getCalledHooks();
		$lastHook = array_pop($calledHooks);
		self::assertEquals('ArticleSearchIndex::articleFileDeleted', $lastHook[0]);

		// Remove the test hook.
		HookRegistry::clear('ArticleSearchIndex::articleFileDeleted');
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
		// Make sure that no hook is being called.
		HookRegistry::clear('ArticleSearchIndex::articleMetadataChanged');

		// Mock an article so that the authors are not
		// being retrieved from the database.
		$article = $this->getMock('Article', array('getAuthors'));
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
		$this->markTestSkipped();
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
	public function testIndexSuppFileMetadata() {
		// Make sure that no hook is being called.
		HookRegistry::clear('ArticleSearchIndex::suppFileMetadataChanged');

		// Test indexing an article with a mock environment.
		$suppFile = new SuppFile();
		$articleSearchIndex = $this->getMockArticleSearchIndex($this->atLeastOnce());
		$articleSearchIndex->suppFileMetadataChanged($suppFile);
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testIndexSuppFileMetadataViaPluginHook() {
		$this->markTestSkipped();
		// Diverting to the search plugin hook.
		HookRegistry::register('ArticleSearchIndex::suppFileMetadataChanged', array($this, 'callbackIndexSuppFileMetadata'));

		// Simulate indexing via hook.
		$suppFile = new SuppFile();
		$articleSearchIndex = $this->getMockArticleSearchIndex($this->never());
		$articleSearchIndex->suppFileMetadataChanged($suppFile);

		// Test whether the hook was called.
		$calledHooks = HookRegistry::getCalledHooks();
		self::assertEquals('ArticleSearchIndex::suppFileMetadataChanged', $calledHooks[0][0]);

		// Remove the test hook.
		HookRegistry::clear('ArticleSearchIndex::suppFileMetadataChanged');
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testIndexArticleFiles() {
		// Make sure that no hook is being called.
		HookRegistry::clear('ArticleSearchIndex::articleFilesChanged');
		$this->registerFileDAOs(true);

		// Test indexing an article with a mock environment.
		$article = new Article();
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleFilesChanged($article);
	}

	/**
	 * @covers ArticleSearchIndex
	 */
	public function testIndexArticleFilesViaPluginHook() {
		$this->markTestSkipped();
		// Diverting to the search plugin hook.
		HookRegistry::register('ArticleSearchIndex::articleFilesChanged', array($this, 'callbackIndexArticleFiles'));

		// The file DAOs should not be called.
		$this->registerFileDAOs(false);

		// Simulate indexing via hook.
		$article = new Article();
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleFilesChanged($article);

		// Test whether the hook was called.
		$calledHooks = HookRegistry::getCalledHooks();
		$lastHook = array_pop($calledHooks);
		self::assertEquals('ArticleSearchIndex::articleFilesChanged', $lastHook[0]);

		// Remove the test hook.
		HookRegistry::clear('ArticleSearchIndex::articleFilesChanged');
	}


	//
	// Public callback methods
	//
	/**
	 * Simulate a search plug-ins "update file index"
	 * hook.
	 * @see ArticleSearchIndex::articleFileChanged()
	 */
	public function callbackUpdateFileIndex($hook, $params) {
		self::assertEquals('ArticleSearchIndex::articleFileChanged', $hook);

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
	 * @see ArticleSearchIndex::articleFileDeleted()
	 */
	public function callbackDeleteTextIndex($hook, $params) {
		self::assertEquals('ArticleSearchIndex::articleFileDeleted', $hook);

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
	 * Simulate a search plug-ins "index supp file metadata"
	 * hook.
	 * @see ArticleSearchIndex::suppFileMetadataChanged()
	 */
	public function callbackIndexSuppFileMetadata($hook, $params) {
		self::assertEquals('ArticleSearchIndex::suppFileMetadataChanged', $hook);

		list($suppFile) = $params;
		self::assertInstanceOf('SuppFile', $suppFile);

		// Returning "true" is required so that the default articleMetadataChanged()
		// code won't run.
		return true;
	}

	/**
	 * Simulate a search plug-ins "index article files"
	 * hook.
	 * @see ArticleSearchIndex::articleFilesChanged()
	 */
	public function callbackIndexArticleFiles($hook, $params) {
		self::assertEquals('ArticleSearchIndex::articleFilesChanged', $hook);

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
		$articleSearchDao = $this->getMock('ArticleSearchDAO', array('clearIndex', 'deleteArticleKeywords'), array(), '', false);

		// Test the clearIndex() method.
		$articleSearchDao->expects($clearIndexExpected)
		                 ->method('clearIndex')
		                 ->will($this->returnValue(null));

		// Test the deleteArticleKeywords() method.
		$articleSearchDao->expects($deleteArticleExpected)
		                 ->method('deleteArticleKeywords')
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
		$journalDao = $this->getMock('JournalDAO', array('getJournals'), array(), '', false);

		// Mock an empty result set.
		$journals = array();
		$journalsIterator = new ArrayItemIterator($journals);

		// Mock the getById() method.
		$journalDao->expects($this->any())
		           ->method('getJournals')
		           ->will($this->returnValue($journalsIterator));

		// Register the mock DAO.
		DAORegistry::registerDAO('JournalDAO', $journalDao);
	}

	/**
	 * Mock and register a SuppFileDAO and
	 * ArticleGalleyDAO as a test back end for
	 * the ArticleSearchIndex class.
	 */
	private function registerFileDAOs($expectMethodCall) {
		// Mock file DAOs.
		$suppFileDao = $this->getMock('SuppFileDAO', array('getSuppFilesByArticle'), array(), '', false);
		$articleGalleyDao = $this->getMock('ArticleGalleyDAO', array('getGalleysByArticle'), array(), '', false);

		// Make sure that the DAOs are being called.
		if ($expectMethodCall) {
			$expectation = $this->atLeastOnce();
		} else {
			$expectation = $this->never();
		}
		$suppFileDao->expects($expectation)
		            ->method('getSuppFilesByArticle')
		            ->will($this->returnValue(array()));
		$articleGalleyDao->expects($expectation)
		                 ->method('getGalleysByArticle')
		                 ->will($this->returnValue(array()));
		DAORegistry::registerDAO('SuppFileDAO', $suppFileDao);
		DAORegistry::registerDAO('ArticleGalleyDAO', $articleGalleyDao);
	}

	/**
	 * Mock an ArticleSearchIndex implementation.
	 * @return ArticleSearchIndex
	 */
	private function getMockArticleSearchIndex($expectedCall) {
		// Mock ArticleSearchIndex.
		/* @var $articleSearchIndex ArticleSearchIndex */
		$articleSearchIndex = $this->getMock('ArticleSearchIndex', array('_updateTextIndex'));

		// Check for _updateTextIndex() calls.
		$articleSearchIndex->expects($expectedCall)
		                   ->method('_updateTextIndex')
		                   ->will($this->returnValue(null));
		return $articleSearchIndex;
	}
}
?>
