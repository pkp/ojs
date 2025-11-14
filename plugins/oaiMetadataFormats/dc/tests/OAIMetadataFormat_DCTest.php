<?php

/**
 * @defgroup plugins_oaiMetadataFormats_dc_tests Dublin Core OAI Plugin
 */

/**
 * @file plugins/oaiMetadataFormats/dc/tests/OAIMetadataFormat_DCTest.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_DCTest
 *
 * @ingroup plugins_oaiMetadataFormats_dc_tests
 *
 * @see OAIMetadataFormat_DC
 *
 * @brief Test class for OAIMetadataFormat_DC.
 */

namespace APP\plugins\oaiMetadataFormats\dc\tests;

use APP\author\Author;
use APP\core\Application;
use APP\core\PageRouter;
use APP\core\Request;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\oai\ojs\OAIDAO;
use APP\plugins\oaiMetadataFormats\dc\OAIMetadataFormat_DC;
use APP\plugins\oaiMetadataFormats\dc\OAIMetadataFormatPlugin_DC;
use APP\publication\Publication;
use APP\section\Section;
use APP\submission\Submission;
use Illuminate\Support\LazyCollection;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PKP\author\Repository as AuthorRepository;
use PKP\controlledVocab\ControlledVocab;
use PKP\controlledVocab\Repository as ControlledVocabRepository;
use PKP\core\Dispatcher;
use PKP\core\Registry;
use PKP\db\DAORegistry;
use PKP\doi\Doi;
use PKP\galley\Collector as GalleyCollector;
use PKP\galley\Galley;
use PKP\oai\OAIRecord;
use PKP\tests\PKPTestCase;

#[CoversClass(OAIMetadataFormat_DC::class)]
#[CoversClass(\APP\plugins\metadata\dc11\filter\Dc11SchemaArticleAdapter::class)]
class OAIMetadataFormat_DCTest extends PKPTestCase
{
    /**
     * @see PKPTestCase::getMockedDAOs()
     */
    protected function getMockedDAOs(): array
    {
        return [...parent::getMockedDAOs(), 'OAIDAO'];
    }

    /**
     * @see PKPTestCase::getMockedRegistryKeys()
     */
    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), 'request'];
    }

    /**
     * @see PKPTestCase::getMockedContainerKeys()
     */
    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), GalleyCollector::class, AuthorRepository::class];
    }

    public function testToXml()
    {
        $controlledVocabRepoMock = Mockery::mock(ControlledVocabRepository::class)
            ->makePartial()
            ->shouldReceive('getBySymbolic')
            ->twice()
            ->withAnyArgs()
            ->andReturn(
                [
                    'en' => [
                        [
                            'name' => 'article-keyword',
                        ],
                    ]
                ],
                [
                    'en' => [
                        [
                            'name' => 'article-subject',
                        ],
                        [
                            'name' => 'article-subject-class'
                        ]
                    ]
                ]
            )
            ->getMock();

        app()->instance(ControlledVocabRepository::class, $controlledVocabRepoMock);

        //
        // Create test data.
        //
        $journalId = 1;

        // Author
        $author = new Author();
        $author->setGivenName('author-firstname', 'en');
        $author->setFamilyName('author-lastname', 'en');
        $author->setAffiliations([
            Repo::affiliation()->newDataObject([
                'id' => 1,
                'authorId' => 1,
                'ror' => 'https://ror.org/05ek4tb53',
                'name' => ['en' => 'author-affiliation']
            ])
        ]);
        $author->setEmail('someone@example.com');

        // Publication
        /** @var Doi|MockObject */
        $publicationDoiObject = $this->getMockBuilder(Doi::class)
            ->onlyMethods([])
            ->getMock();
        $publicationDoiObject->setData('doi', 'article-doi');

        /** @var Publication|MockObject */
        $publication = $this->getMockBuilder(Publication::class)
            ->onlyMethods([])
            ->getMock();
        $publication->setData('id', 0);
        $publication->setData('issueId', 96);
        $publication->setData('pages', 15);
        $publication->setData('type', 'art-type', 'en');
        $publication->setData('title', 'article-title-en', 'en');
        $publication->setData('title', 'article-title-de', 'de');
        $publication->setData('coverage', ['en' => ['article-coverage-geo', 'article-coverage-chron', 'article-coverage-sample']]);
        $publication->setData('abstract', 'article-abstract', 'en');
        $publication->setData('sponsor', 'article-sponsor', 'en');
        $publication->setData('doiObject', $publicationDoiObject);
        $publication->setData('copyrightHolder', 'article-copyright');
        $publication->setData('copyrightYear', 'year');
        $publication->setData('authors', collect([$author]));
        $publication->setData(
            'keywords',
            Repo::controlledVocab()->getBySymbolic(
                ControlledVocab::CONTROLLED_VOCAB_SUBMISSION_KEYWORD,
                0,
                Application::ASSOC_TYPE_PUBLICATION
            )['en'],
            'en'
        );
        $publication->setData(
            'subjects',
            Repo::controlledVocab()->getBySymbolic(
                ControlledVocab::CONTROLLED_VOCAB_SUBMISSION_SUBJECT,
                0,
                Application::ASSOC_TYPE_PUBLICATION
            )['en'],
            'en'
        );

        // Article
        /** @var Submission|MockObject */
        $article = $this->getMockBuilder(Submission::class)
            ->onlyMethods(['getId', 'getCurrentPublication'])
            ->getMock();
        $article->expects($this->any())
            ->method('getId')
            ->willReturn(9);
        $article->setData('locale', 'en');
        $article->setId(9);
        $article->setData('contextId', $journalId);
        $author->setSubmissionId($article->getId());
        $article->expects($this->any())
            ->method('getCurrentPublication')
            ->willReturn($publication);

        /** @var Doi|MockObject */
        $galleyDoiObject = $this->getMockBuilder(Doi::class)
            ->onlyMethods([])
            ->getMock();
        $galleyDoiObject->setData('doi', 'galley-doi');

        // Galleys
        $galley = Repo::galley()->newDataObject();
        /** @var Galley|MockObject */
        $galley = $this->getMockBuilder(Galley::class)
            ->onlyMethods(['getFileType', 'getBestGalleyId'])
            ->setProxyTarget($galley)
            ->getMock();
        $galley->expects(self::any())
            ->method('getFileType')
            ->willReturn('galley-filetype');
        $galley->expects(self::any())
            ->method('getBestGalleyId')
            ->willReturn(98);
        $galley->setId(98);
        $galley->setData('doiObject', $galleyDoiObject);
        $galley->setData('locale', 'en');

        $galleys = [$galley];

        // Journal
        /** @var Journal|MockObject */
        $journal = $this->getMockBuilder(Journal::class)
            ->onlyMethods(['getSetting'])
            ->getMock();
        $journal->expects($this->any())
            ->method('getSetting')
            ->with('publishingMode')
            ->willReturn(Journal::PUBLISHING_MODE_OPEN);
        $journal->setName('journal-title', 'en');
        $journal->setData('publisherInstitution', 'journal-publisher');
        $journal->setData('supportedFormLocales', []);
        $journal->setPrimaryLocale('en');
        $journal->setPath('journal-path');
        $journal->setData('onlineIssn', 'onlineIssn');
        $journal->setData('printIssn', null);
        $journal->setData(Journal::SETTING_ENABLE_DOIS, true);
        $journal->setId($journalId);

        // Section
        $section = new Section();
        $section->setIdentifyType('section-identify-type', 'en');

        /** @var Doi|MockObject */
        $issueDoiObject = $this->getMockBuilder(Doi::class)
            ->onlyMethods([])
            ->getMock();
        $issueDoiObject->setData('doi', 'issue-doi');

        // Issue
        /** @var Issue|MockObject */
        $issue = $this->getMockBuilder(Issue::class)
            ->onlyMethods(['getIssueIdentification'])
            ->getMock();
        $issue->expects($this->any())
            ->method('getIssueIdentification')
            ->willReturn('issue-identification');
        $issue->setId(96);
        $issue->setDatePublished('2010-11-05');
        $issue->setData('doiObject', $issueDoiObject);
        $issue->setJournalId($journalId);


        //
        // Create infrastructural support objects
        //

        // Router
        /** @var PageRouter|MockObject */
        $router = $this->getMockBuilder(PageRouter::class)
            ->onlyMethods(['url'])
            ->getMock();
        $application = Application::get();
        $router->setApplication($application);
        $router->expects($this->any())
            ->method('url')
            ->willReturnCallback(fn ($request, $newContext = null, $handler = null, $op = null, $path = null) => $handler . '-' . $op . '-' . implode('-', $path));

        // Dispatcher
        /** @var Dispatcher|MockObject */
        $dispatcher = $this->getMockBuilder(Dispatcher::class)
            ->onlyMethods(['url'])
            ->getMock();
        $dispatcher->expects($this->any())
            ->method('url')
            ->willReturnCallback(fn ($request, $shortcut, $newContext = null, $handler = null, $op = null, $path = null) => $handler . '-' . $op . '-' . implode('-', $path));

        // Request
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRouter', 'getDispatcher'])
            ->getMock();
        $requestMock->expects($this->any())
            ->method('getRouter')
            ->willReturn($router);
        $requestMock->expects($this->any())
            ->method('getDispatcher')
            ->willReturn($dispatcher);
        Registry::set('request', $requestMock);

        //
        // Create mock DAOs
        //

        // Create a mocked OAIDAO that returns our test data.
        $oaiDao = $this->getMockBuilder(OAIDAO::class)
            ->onlyMethods(['getJournal', 'getSection', 'getIssue'])
            ->getMock();
        $oaiDao->expects($this->any())
            ->method('getJournal')
            ->willReturn($journal);
        $oaiDao->expects($this->any())
            ->method('getSection')
            ->willReturn($section);
        $oaiDao->expects($this->any())
            ->method('getIssue')
            ->willReturn($issue);
        DAORegistry::registerDAO('OAIDAO', $oaiDao);

        /** @var GalleyCollector|MockObject */
        $mockGalleyCollector = $this->getMockBuilder(GalleyCollector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMany'])
            ->getMock();
        $mockGalleyCollector->expects($this->any())
            ->method('getMany')
            ->willReturn(LazyCollection::wrap($galleys));
        app()->instance(GalleyCollector::class, $mockGalleyCollector);

        //
        // Test
        //

        // OAI record
        $record = new OAIRecord();
        $record->setData('article', $article);
        $record->setData('galleys', $galleys);
        $record->setData('journal', $journal);
        $record->setData('section', $section);
        $record->setData('issue', $issue);

        // Instantiate the OAI meta-data format.
        $prefix = OAIMetadataFormatPlugin_DC::getMetadataPrefix();
        $schema = OAIMetadataFormatPlugin_DC::getSchema();
        $namespace = OAIMetadataFormatPlugin_DC::getNamespace();
        $mdFormat = new OAIMetadataFormat_DC($prefix, $schema, $namespace);

        $xml = $mdFormat->toXml($record);
        self::assertXmlStringEqualsXmlFile('plugins/oaiMetadataFormats/dc/tests/expectedResult.xml', $xml);
    }
}
