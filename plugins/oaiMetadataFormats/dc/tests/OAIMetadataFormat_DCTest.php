<?php
/**
 * @defgroup plugins_oaiMetadataFormats_dc_tests Dublin Core OAI Plugin
 */

/**
 * @file plugins/oaiMetadataFormats/dc/tests/OAIMetadataFormat_DCTest.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_DCTest
 * @ingroup plugins_oaiMetadataFormats_dc_tests
 *
 * @see OAIMetadataFormat_DC
 *
 * @brief Test class for OAIMetadataFormat_DC.
 */

namespace APP\plugins\oaiMetadataFormats\dc;

use APP\author\Author;
use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\journal\Section;
use APP\oai\ojs\OAIDAO;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\LazyCollection;
use OAIMetadataFormat_DC;
use OAIMetadataFormatPlugin_DC;
use PHPUnit\Framework\MockObject\MockObject;
use PKP\author\Repository as AuthorRepository;
use PKP\core\PKPRouter;
use PKP\core\Registry;
use PKP\db\DAORegistry;
use PKP\doi\Doi;
use PKP\galley\Galley;
use PKP\galley\Repository as GalleyRepository;
use PKP\oai\OAIRecord;
use PKP\submission\SubmissionKeywordDAO;
use PKP\submission\SubmissionSubjectDAO;
use PKP\tests\PKPTestCase;

import('plugins.oaiMetadataFormats.dc.OAIMetadataFormat_DC');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormatPlugin_DC');

class OAIMetadataFormat_DCTest extends PKPTestCase
{
    /**
     * @see PKPTestCase::getMockedDAOs()
     */
    protected function getMockedDAOs(): array
    {
        return [...parent::getMockedDAOs(), 'OAIDAO', 'SubmissionSubjectDAO', 'SubmissionKeywordDAO'];
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
        return [...parent::getMockedContainerKeys(), GalleyRepository::class, AuthorRepository::class];
    }

    /**
     * @covers OAIMetadataFormat_DC
     * @covers Dc11SchemaArticleAdapter
     */
    public function testToXml()
    {
        //
        // Create test data.
        //
        $journalId = 1;

        // Author
        $author = new Author();
        $author->setGivenName('author-firstname', 'en_US');
        $author->setFamilyName('author-lastname', 'en_US');
        $author->setAffiliation('author-affiliation', 'en_US');
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
        $publication->setData('issueId', 96);
        $publication->setData('pages', 15);
        $publication->setData('type', 'art-type', 'en_US');
        $publication->setData('title', 'article-title-en', 'en_US');
        $publication->setData('title', 'article-title-de', 'de_DE');
        $publication->setData('coverage', ['en_US' => ['article-coverage-geo', 'article-coverage-chron', 'article-coverage-sample']]);
        $publication->setData('abstract', 'article-abstract', 'en_US');
        $publication->setData('sponsor', 'article-sponsor', 'en_US');
        $publication->setData('doiObject', $publicationDoiObject);
        $publication->setData('languages', 'en_US');
        $publication->setData('copyrightHolder', 'article-copyright');
        $publication->setData('copyrightYear', 'year');

        // Article
        /** @var Submission|MockObject */
        $article = $this->getMockBuilder(Submission::class)
            ->onlyMethods(['getBestId', 'getCurrentPublication'])
            ->getMock();
        $article->expects($this->any())
            ->method('getBestId')
            ->will($this->returnValue(9));
        $article->setId(9);
        $article->setData('contextId', $journalId);
        $author->setSubmissionId($article->getId());
        $article->expects($this->any())
            ->method('getCurrentPublication')
            ->will($this->returnValue($publication));

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
            ->will($this->returnValue('galley-filetype'));
        $galley->expects(self::any())
            ->method('getBestGalleyId')
            ->will($this->returnValue(98));
        $galley->setId(98);
        $galley->setData('doiObject', $galleyDoiObject);

        $galleys = [$galley];

        // Journal
        /** @var Journal|MockObject */
        $journal = $this->getMockBuilder(Journal::class)
            ->onlyMethods(['getSetting'])
            ->getMock();
        $journal->expects($this->any())
            ->method('getSetting')
            ->with('publishingMode')
            ->will($this->returnValue(\APP\journal\Journal::PUBLISHING_MODE_OPEN));
        $journal->setName('journal-title', 'en_US');
        $journal->setData('publisherInstitution', 'journal-publisher');
        $journal->setPrimaryLocale('en_US');
        $journal->setPath('journal-path');
        $journal->setData('onlineIssn', 'onlineIssn');
        $journal->setData('printIssn', null);
        $journal->setId($journalId);

        // Section
        $section = new Section();
        $section->setIdentifyType('section-identify-type', 'en_US');

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
            ->will($this->returnValue('issue-identification'));
        $issue->setId(96);
        $issue->setDatePublished('2010-11-05');
        $issue->setData('doiObject', $issueDoiObject);
        $issue->setJournalId($journalId);


        //
        // Create infrastructural support objects
        //

        // Router
        /** @var PKPRouter|MockObject */
        $router = $this->getMockBuilder(PKPRouter::class)
            ->onlyMethods(['url'])
            ->getMock();
        $application = Application::get();
        $router->setApplication($application);
        $router->expects($this->any())
            ->method('url')
            ->will($this->returnCallback(fn ($request, $newContext = null, $handler = null, $op = null, $path = null) => $handler . '-' . $op . '-' . implode('-', $path)));

        // Request
        $requestMock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRouter'])
            ->getMock();
        $requestMock->expects($this->any())
            ->method('getRouter')
            ->will($this->returnValue($router));
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
            ->will($this->returnValue($journal));
        $oaiDao->expects($this->any())
            ->method('getSection')
            ->will($this->returnValue($section));
        $oaiDao->expects($this->any())
            ->method('getIssue')
            ->will($this->returnValue($issue));
        DAORegistry::registerDAO('OAIDAO', $oaiDao);

        /** @var AuthorRepository|MockObject */
        $mockAuthorRepository = $this->getMockBuilder(AuthorRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSubmissionAuthors'])
            ->getMock();
        $mockAuthorRepository->expects($this->any())
            ->method('getSubmissionAuthors')
            ->will($this->returnValue(LazyCollection::wrap([$author])));
        app()->instance(AuthorRepository::class, $mockAuthorRepository);

        /** @var GalleyRepository|MockObject */
        $mockGalleyRepository = $this->getMockBuilder(GalleyRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMany'])
            ->getMock();
        $mockGalleyRepository->expects($this->any())
            ->method('getMany')
            ->will($this->returnValue(LazyCollection::wrap($galleys)));
        app()->instance(GalleyRepository::class, $mockGalleyRepository);

        // Mocked DAO to return the subjects
        $submissionSubjectDao = $this->getMockBuilder(SubmissionSubjectDAO::class)
            ->onlyMethods(['getSubjects'])
            ->getMock();
        $submissionSubjectDao->expects($this->any())
            ->method('getSubjects')
            ->will($this->returnValue(['en_US' => ['article-subject', 'article-subject-class']]));
        DAORegistry::registerDAO('SubmissionSubjectDAO', $submissionSubjectDao);

        // Mocked DAO to return the keywords
        $submissionKeywordDao = $this->getMockBuilder(SubmissionKeywordDAO::class)
            ->onlyMethods(['getKeywords'])
            ->getMock();
        $submissionKeywordDao->expects($this->any())
            ->method('getKeywords')
            ->will($this->returnValue(['en_US' => ['article-keyword']]));
        DAORegistry::registerDAO('SubmissionKeywordDAO', $submissionKeywordDao);


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
