<?php

/**
 * @file tests/classes/search/ArticleSearchTest.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearchTest
 *
 * @ingroup tests_classes_search
 *
 * @see ArticleSearch
 *
 * @brief Test class for the ArticleSearch class
 */

namespace APP\tests\classes\search;

use APP\core\Application;
use APP\core\PageRouter;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use APP\search\ArticleSearch;
use APP\search\ArticleSearchDAO;
use APP\submission\Repository as SubmissionRepository;
use APP\section\Repository as SectionRepository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use PKP\tests\PKPTestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[CoversClass(ArticleSearch::class)]
class ArticleSearchTest extends PKPTestCase
{
    private const SUBMISSION_SEARCH_TEST_DEFAULT_ARTICLE = 1;

    private array $_retrieveResultsParams;

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
        return [...parent::getMockedContainerKeys(), \APP\issue\DAO::class];
    }

    /**
     * @see PKPTestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();
        Hook::rememberCalledHooks();

        // Prepare the mock environment for this test.
        $this->registerMockArticleSearchDAO();
        $this->registerMockJournalDAO();

        $request = Application::get()->getRequest();
        if (is_null($request->getRouter())) {
            $router = new PageRouter();
            $request->setRouter($router);
        }
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

    public function testRetrieveResultsWithUnpublishedIssueAndUnpublishedArticle()
    {
        // Make sure that no hook is being called.
        Hook::clear('SubmissionSearch::retrieveResults');

        $journal = new Journal();
        $keywords = [null => 'test'];
        $articleSearch = new ArticleSearch();
        $error = '';
        $request = Application::get()->getRequest();

        /**
         * @disregard P1013 PHP Intelephense error suppression
         * @see https://github.com/bmewburn/vscode-intelephense/issues/568
         */
        $publicationMock = Mockery::mock(\APP\publication\Publication::class)
            ->makePartial()
            ->shouldReceive('getData')
            ->with('keywords')
            ->andReturn($keywords)
            ->shouldReceive('getData')
            ->with('published')
            ->andReturn(false)
            ->getMock();
        
        $sectionMock = Mockery::mock(\APP\section\Section::class)
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(new \APP\section\Section())
            ->getMock();
        
        $sectionRepoMock = Mockery::mock(SectionRepository::class)
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn($sectionMock)
            ->getMock();

        app()->instance(SectionRepository::class, $sectionRepoMock);

        $submissionMock = Mockery::mock(\APP\submission\Submission::class)
            ->makePartial()
            ->shouldReceive([
                'getId' => 0,
                'getData' => 0,
                'getCurrentPublication' => $publicationMock,
                'getSectionId' => 0
            ])
            ->withAnyArgs()
            ->getMock();

        $submissionRepoMock = Mockery::mock(app(SubmissionRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn($submissionMock)
            ->getMock();

        app()->instance(SubmissionRepository::class, $submissionRepoMock);

        $issue = new \APP\issue\Issue();
        $issue->setPublished(false);
        $issue->setJournalId(1);

        // Setup the mock
        app()->instance(
            \APP\issue\DAO::class,
            Mockery::mock(
                \APP\issue\DAO::class,
                fn (MockInterface $mock) => $mock->shouldReceive('get')
                    ->withAnyArgs()
                    ->andReturn($issue)
            )
        );

        $this->registerMockArticleSearchDAO(); // This is necessary to instantiate a fresh iterator.
        $searchResult = $articleSearch->retrieveResults($request, $journal, $keywords, $error);
        self::assertTrue($searchResult->eof());
    }

    public function testRetrieveResultsWithUnpublishedIssueAndPublishedArticle()
    {
        // Make sure that no hook is being called.
        Hook::clear('SubmissionSearch::retrieveResults');

        // Test a simple search with a mock database back-end.
        $journal = new Journal();
        $keywords = [null => 'test'];
        $articleSearch = new ArticleSearch();
        $error = '';
        $request = Application::get()->getRequest();
        $searchResult = $articleSearch->retrieveResults($request, $journal, $keywords, $error);

        // Test whether the result from the mocked DAOs is being returned.
        self::assertInstanceOf('ItemIterator', $searchResult);
        $firstResult = $searchResult->next();
        self::assertArrayHasKey('article', $firstResult);
        self::assertEquals(self::SUBMISSION_SEARCH_TEST_DEFAULT_ARTICLE, $firstResult['article']->getId());
        self::assertEquals('', $error);

        // Make sure that published articles from unpublished/future issues will be counted
        $issue = new \APP\issue\Issue();
        $issue->setPublished(false);
        $issue->setJournalId(1);

        // Setup the mock
        app()->instance(
            \APP\issue\DAO::class,
            Mockery::mock(
                \APP\issue\DAO::class,
                fn (MockInterface $mock) => $mock->shouldReceive('get')
                    ->withAnyArgs()
                    ->andReturn($issue)
            )
        );

        $this->registerMockArticleSearchDAO(); // This is necessary to instantiate a fresh iterator.
        $keywords = [null => 'test'];
        $searchResult = $articleSearch->retrieveResults($request, $journal, $keywords, $error);
        
        self::assertFalse($searchResult->eof());
        self::assertEquals(self::SUBMISSION_SEARCH_TEST_DEFAULT_ARTICLE, $searchResult->getCount());
        
        $searchResult->next();
        self::assertTrue($searchResult->eof());
    }

    public function testRetrieveResultsViaPluginHook()
    {
        // Diverting a search to the search plugin hook.
        Hook::add('SubmissionSearch::retrieveResults', $this->callbackRetrieveResults(...));

        $testCases = [
            [null => 'query'], // Simple Search - "All"
            ['1' => 'author'], // Simple Search - "Authors"
            ['2' => 'title'], // Simple Search - "Title"
            [
                null => 'query',
                1 => 'author',
                2 => 'title'
            ], // Advanced Search
        ];

        $testFromDate = date('Y-m-d H:i:s', strtotime('2011-03-15 00:00:00'));
        $testToDate = date('Y-m-d H:i:s', strtotime('2012-03-15 18:30:00'));
        $error = '';

        $request = Application::get()->getRequest();

        foreach ($testCases as $testCase) {
            // Test a simple search with the simulated callback.
            $journal = new Journal();
            $keywords = $testCase;
            $articleSearch = new ArticleSearch();
            Hook::resetCalledHooks(true);
            $searchResult = $articleSearch->retrieveResults($request, $journal, $keywords, $error, $testFromDate, $testToDate);

            // Check the parameters passed into the callback.
            foreach ([
                $journal, $testCase, $testFromDate, $testToDate, $orderBy = 'score', $orderDir = 'desc',
                $exclude = [], $page = 1, $itemsPerPage = 20, $totalResults = 3, $error = '',
                //the last item, the result,  will be checked later on
            ] as $position => $expected) {
                self::assertEquals($expected, $this->_retrieveResultsParams[$position]);
            }

            // Test the call history of the hook registry.
            $calledHooks = Hook::getCalledHooks();
            self::assertCount(1, array_filter($calledHooks, fn ($hook) => $hook[0] === 'SubmissionSearch::retrieveResults'));

            // Test whether the result from the hook is being returned.
            self::assertInstanceOf('VirtualArrayIterator', $searchResult);

            // Test the total count.
            self::assertEquals(3, $searchResult->getCount());

            // Test the search result.
            $firstResult = $searchResult->next();
            self::assertArrayHasKey('article', $firstResult);
            self::assertEquals(self::SUBMISSION_SEARCH_TEST_DEFAULT_ARTICLE, $firstResult['article']->getId());
            self::assertEquals('', $error);
        }

        // Remove the test hook.
        Hook::clear('SubmissionSearch::retrieveResults');
    }


    //
    // Public callback methods
    //
    /**
     * Simulate a search plug-ins "retrieve results" hook.
     *
     * @see SubmissionSearch::retrieveResults()
     */
    public function callbackRetrieveResults($hook, $params): bool
    {
        // Save the test parameters
        $this->_retrieveResultsParams = $params;

        // Test returning count by-ref.
        $totalCount = & $params[9];
        $totalCount = 3;

        // Mock a result set and return it.
        $results = & $params[11];
        $results = [3 => self::SUBMISSION_SEARCH_TEST_DEFAULT_ARTICLE];
        return true;
    }


    //
    // Private helper methods
    //
    /**
     * Mock and register an ArticleSearchDAO as a test
     * back end for the ArticleSearch class.
     */
    private function registerMockArticleSearchDAO()
    {
        // Mock an ArticleSearchDAO.
        $articleSearchDao = $this->getMockBuilder(ArticleSearchDAO::class)
            ->onlyMethods(['getPhraseResults'])
            ->getMock();

        // Mock a result set.
        $searchResult = [
            self::SUBMISSION_SEARCH_TEST_DEFAULT_ARTICLE => [
                'count' => 3,
                'journal_id' => 2,
                'issuePublicationDate' => '2013-05-01 20:30:00',
                'publicationDate' => '2013-05-01 20:30:00'
            ]
        ];

        // Mock the getPhraseResults() method.
        $articleSearchDao->expects($this->any())
            ->method('getPhraseResults')
            ->willReturn($searchResult);

        // Register the mock DAO.
        DAORegistry::registerDAO('ArticleSearchDAO', $articleSearchDao);
    }


    /**
     * Mock and register an JournalDAO as a test
     * back end for the ArticleSearch class.
     */
    private function registerMockJournalDAO()
    {
        // Mock a JournalDAO.
        $journalDao = $this->getMockBuilder(JournalDAO::class)
            ->onlyMethods(['getById'])
            ->getMock();

        // Mock a journal.
        $journal = new Journal();
        $journal->setId(1);

        // Mock the getById() method.
        $journalDao->expects($this->any())
            ->method('getById')
            ->willReturn($journal);

        // Register the mock DAO.
        DAORegistry::registerDAO('JournalDAO', $journalDao);
    }
}
