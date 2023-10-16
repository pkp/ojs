<?php

/**
 * @file tests/classes/search/ArticleSearchIndexTest.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearchIndexTest
 *
 * @ingroup tests_classes_search
 *
 * @see ArticleSearchIndex
 *
 * @brief Test class for the ArticleSearchIndex class
 */

namespace APP\tests\classes\search;

use APP\core\Application;
use APP\journal\JournalDAO;
use APP\publication\Publication;
use APP\search\ArticleSearchDAO;
use APP\search\ArticleSearchIndex;
use APP\submission\Submission;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PKP\db\DAORegistry;
use PKP\db\DAOResultFactory;
use PKP\plugins\Hook;
use PKP\submissionFile\Collector as SubmissionFileCollector;
use PKP\submissionFile\SubmissionFile;
use PKP\tests\PKPTestCase;

class ArticleSearchIndexTest extends PKPTestCase
{
    //
    // Implementing protected template methods from PKPTestCase
    //
    /**
     * @see PKPTestCase::getMockedDAOs()
     */
    protected function getMockedDAOs(): array
    {
        return [...parent::getMockedDAOs(), 'ArticleSearchDAO', 'JournalDAO'];
    }

    /**
     * @see PKPTestCase::getMockedContainerKeys()
     */
    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), SubmissionFileCollector::class];
    }

    /**
     * @see PKPTestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();
        Hook::rememberCalledHooks();
    }

    /**
     * @see PKPTestCase::tearDown()
     */
    protected function tearDown(): void
    {
        Hook::resetCalledHooks();
        parent::tearDown();
    }


    //
    // Unit tests
    //
    /**
     * @covers ArticleSearchIndex
     */
    public function testUpdateFileIndexViaPluginHook()
    {
        // Diverting to the search plugin hook.
        Hook::add('ArticleSearchIndex::submissionFileChanged', [$this, 'callbackUpdateFileIndex']);

        // Simulate updating an article file via hook.
        $submissionFile = new SubmissionFile();
        $submissionFile->setId(2);
        $articleSearchIndex = Application::getSubmissionSearchIndex();
        $articleSearchIndex->submissionFileChanged(0, 1, $submissionFile);

        // Test whether the hook was called.
        $calledHooks = Hook::getCalledHooks();
        $lastHook = array_pop($calledHooks);
        self::assertEquals('ArticleSearchIndex::submissionFileChanged', $lastHook[0]);

        // Remove the test hook.
        Hook::clear('ArticleSearchIndex::submissionFileChanged');
    }

    /**
     * @covers ArticleSearchIndex
     */
    public function testDeleteTextIndex()
    {
        // Prepare the mock environment for this test.
        $this->registerMockArticleSearchDAO($this->never(), $this->atLeastOnce());

        // Make sure that no hook is being called.
        Hook::clear('ArticleSearchIndex::submissionFileDeleted');

        // Test deleting an article from the index with a mock database back-end.#
        $articleSearchIndex = Application::getSubmissionSearchIndex();
        $articleSearchIndex->submissionFileDeleted(0);
    }

    /**
     * @covers ArticleSearchIndex
     */
    public function testDeleteTextIndexViaPluginHook()
    {
        // Diverting to the search plugin hook.
        Hook::add('ArticleSearchIndex::submissionFileDeleted', [$this, 'callbackDeleteTextIndex']);

        // The search DAO should not be called.
        $this->registerMockArticleSearchDAO($this->never(), $this->never());

        // Simulate deleting article index via hook.
        $articleSearchIndex = Application::getSubmissionSearchIndex();
        $articleSearchIndex->submissionFileDeleted(0, 1, 2);

        // Test whether the hook was called.
        $calledHooks = Hook::getCalledHooks();
        $lastHook = array_pop($calledHooks);
        self::assertEquals('ArticleSearchIndex::submissionFileDeleted', $lastHook[0]);

        // Remove the test hook.
        Hook::clear('ArticleSearchIndex::submissionFileDeleted');
    }

    /**
     * @covers ArticleSearchIndex
     */
    public function testRebuildIndex()
    {
        // Prepare the mock environment for this test.
        $this->registerMockArticleSearchDAO($this->atLeastOnce(), $this->never());
        $this->registerMockJournalDAO();

        // Make sure that no hook is being called.
        Hook::clear('ArticleSearchIndex::rebuildIndex');

        // Test log output.
        $this->expectOutputString(__('search.cli.rebuildIndex.clearingIndex') . ' ... ' . __('search.cli.rebuildIndex.done') . "\n");

        // Test rebuilding the index with a mock database back-end.
        $articleSearchIndex = Application::getSubmissionSearchIndex();
        $articleSearchIndex->rebuildIndex(true);
    }

    /**
     * @covers ArticleSearchIndex
     */
    public function testRebuildIndexViaPluginHook()
    {
        // Diverting to the search plugin hook.
        Hook::add('ArticleSearchIndex::rebuildIndex', [$this, 'callbackRebuildIndex']);

        // Test log output.
        $this->expectOutputString('Some log message from the plug-in.');

        // Simulate rebuilding the index via hook.
        $articleSearchIndex = Application::getSubmissionSearchIndex();
        $articleSearchIndex->rebuildIndex(true); // With log
        $articleSearchIndex->rebuildIndex(false); // Without log (that's why we expect the log message to appear only once).

        // Remove the test hook.
        Hook::clear('ArticleSearchIndex::rebuildIndex');
    }

    /**
     * @covers ArticleSearchIndex
     */
    public function testIndexArticleMetadata()
    {
        // Make sure that no hook is being called.
        Hook::clear('ArticleSearchIndex::articleMetadataChanged');

        /** @var Publication|MockObject */
        $publication = $this->getMockBuilder(Publication::class)
            ->onlyMethods([])
            ->getMock();
        $publication->setData('authors', []);
        $publication->setData('subjects', []);
        $publication->setData('keywords', []);
        $publication->setData('disciplines', []);

        /** @var Submission|MockObject */
        $article = $this->getMockBuilder(Submission::class)
            ->onlyMethods(['getCurrentPublication'])
            ->getMock();
        $article->expects($this->any())
            ->method('getCurrentPublication')
            ->will($this->returnValue($publication));

        // Test indexing an article with a mock environment.
        $articleSearchIndex = $this->getMockArticleSearchIndex($this->atLeastOnce());
        $articleSearchIndex->submissionMetadataChanged($article);
    }

    /**
     * @covers ArticleSearchIndex
     */
    public function testIndexArticleMetadataViaPluginHook()
    {
        // Diverting to the search plugin hook.
        Hook::add('ArticleSearchIndex::articleMetadataChanged', [$this, 'callbackIndexArticleMetadata']);

        // Simulate indexing via hook.
        $article = new Submission();
        $articleSearchIndex = $this->getMockArticleSearchIndex($this->never());
        $articleSearchIndex->submissionMetadataChanged($article);

        // Test whether the hook was called.
        $calledHooks = Hook::getCalledHooks();
        self::assertEquals('ArticleSearchIndex::articleMetadataChanged', $calledHooks[0][0]);

        // Remove the test hook.
        Hook::clear('ArticleSearchIndex::articleMetadataChanged');
    }

    /**
     * @covers ArticleSearchIndex
     */
    public function testIndexSubmissionFiles()
    {
        // Make sure that no hook is being called.
        Hook::clear('ArticleSearchIndex::submissionFilesChanged');
        $this->registerFileDAOs(true);

        // Test indexing an article with a mock environment.
        $article = new Submission();
        $articleSearchIndex = Application::getSubmissionSearchIndex();
        $articleSearchIndex->submissionFilesChanged($article);
        $this->assertTrue(true);
    }

    /**
     * @covers ArticleSearchIndex
     */
    public function testIndexSubmissionFilesViaPluginHook()
    {
        // Diverting to the search plugin hook.
        Hook::add('ArticleSearchIndex::submissionFilesChanged', [$this, 'callbackIndexSubmissionFiles']);
        // The file DAOs should not be called.
        $this->registerFileDAOs(false);

        // Simulate indexing via hook.
        $article = new Submission();
        $articleSearchIndex = Application::getSubmissionSearchIndex();
        $articleSearchIndex->submissionFilesChanged($article);

        // Test whether the hook was called.
        $calledHooks = Hook::getCalledHooks();
        $lastHook = array_pop($calledHooks);
        self::assertEquals('ArticleSearchIndex::submissionFilesChanged', $lastHook[0]);

        // Remove the test hook.
        Hook::clear('ArticleSearchIndex::submissionFilesChanged');
    }


    //
    // Public callback methods
    //
    /**
     * Simulate a search plug-ins "update file index"
     * hook.
     *
     * @see ArticleSearchIndex::submissionFileChanged()
     */
    public function callbackUpdateFileIndex($hook, $params)
    {
        self::assertEquals('ArticleSearchIndex::submissionFileChanged', $hook);

        [$articleId, $type, $submissionFileId] = $params;
        self::assertEquals(0, $articleId);
        self::assertEquals(1, $type);
        self::assertEquals(2, $submissionFileId);

        // Returning "true" is required so that the default submissionMetadataChanged()
        // code won't run.
        return true;
    }

    /**
     * Simulate a search plug-ins "delete text index"
     * hook.
     *
     * @see ArticleSearchIndex::submissionFileDeleted()
     */
    public function callbackDeleteTextIndex($hook, $params)
    {
        self::assertEquals('ArticleSearchIndex::submissionFileDeleted', $hook);

        [$articleId, $type, $assocId] = $params;
        self::assertEquals(0, $articleId);
        self::assertEquals(1, $type);
        self::assertEquals(2, $assocId);

        // Returning "true" is required so that the default submissionMetadataChanged()
        // code won't run.
        return true;
    }

    /**
     * Simulate a search plug-ins "rebuild index" hook.
     *
     * @see ArticleSearchIndex::rebuildIndex()
     */
    public function callbackRebuildIndex($hook, $params)
    {
        self::assertEquals('ArticleSearchIndex::rebuildIndex', $hook);

        [$log] = $params;
        if ($log) {
            echo 'Some log message from the plug-in.';
        }

        // Returning "true" is required so that the default rebuildIndex()
        // code won't run.
        return true;
    }

    /**
     * Simulate a search plug-ins "index article metadata"
     * hook.
     *
     * @see ArticleSearchIndex::submissionMetadataChanged()
     */
    public function callbackIndexArticleMetadata($hook, $params)
    {
        self::assertEquals('ArticleSearchIndex::articleMetadataChanged', $hook);

        [$article] = $params;
        self::assertInstanceOf('Submission', $article);

        // Returning "true" is required so that the default submissionMetadataChanged()
        // code won't run.
        return true;
    }

    /**
     * Simulate a search plug-ins "index article files"
     * hook.
     *
     * @see ArticleSearchIndex::submissionFilesChanged()
     */
    public function callbackIndexSubmissionFiles($hook, $params)
    {
        self::assertEquals('ArticleSearchIndex::submissionFilesChanged', $hook);

        [$article] = $params;
        self::assertInstanceOf('Submission', $article);

        // Returning "true" is required so that the default submissionMetadataChanged()
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
    private function registerMockArticleSearchDAO($clearIndexExpected, $deleteArticleExpected)
    {
        // Mock an ArticleSearchDAO.
        $articleSearchDao = $this->getMockBuilder(ArticleSearchDAO::class)
            ->onlyMethods(['clearIndex', 'deleteSubmissionKeywords'])
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
    private function registerMockJournalDAO()
    {
        // Mock a JournalDAO.
        $journalDao = $this->getMockBuilder(JournalDAO::class)
            ->onlyMethods(['getAll'])
            ->getMock();

        // Mock an empty result set.
        $journalsIterator = $this->getMockBuilder(DAOResultFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toIterator'])
            ->getMock();
        $journalsIterator
            ->method('toIterator')
            ->will($this->returnValue([]));

        // Mock the getAll() method.
        $journalDao->expects($this->any())
            ->method('getAll')
            ->will($this->returnValue($journalsIterator));

        // Register the mock DAO.
        DAORegistry::registerDAO('JournalDAO', $journalDao);
    }

    /**
     * Mock and register an SubmissionFile collector as a test back end for
     * the PreprintSearchIndex class.
     */
    private function registerFileDAOs(bool $expectMethodCall)
    {
        /** @var SubmissionFileCollector|MockInterface */
        $mock = Mockery::mock(
            app(SubmissionFileCollector::class),
            fn (MockInterface $mock) => $expectMethodCall
                ? $mock->shouldReceive('filterBySubmissionIds')->andReturn($mock)
                : $mock->shouldNotReceive('filterBySubmissionIds')
        );
        app()->instance(SubmissionFileCollector::class, $mock);
    }

    /**
     * Mock an ArticleSearchIndex implementation.
     *
     * @return ArticleSearchIndex
     */
    private function getMockArticleSearchIndex($expectedCall)
    {
        // Mock ArticleSearchIndex.
        /** @var ArticleSearchIndex|MockObject  */
        $articleSearchIndex = $this->getMockBuilder(ArticleSearchIndex::class)
            ->onlyMethods(['_updateTextIndex'])
            ->getMock();

        // Check for _updateTextIndex() calls.
        $articleSearchIndex->expects($expectedCall)
            ->method('_updateTextIndex')
            ->will($this->returnValue(null));
        return $articleSearchIndex;
    }
}
