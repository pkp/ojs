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

require_mock_env('env2');

import('lib.pkp.tests.PKPTestCase');

use APP\article\AuthorDAO;
use APP\core\Request;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\journal\Section;
use APP\oai\ojs\OAIDAO;
use PKP\core\PKPRouter;
use PKP\galley\DAO as GalleyDAO;
use PKP\oai\OAIRecord;
use PKP\submission\Submission;

import('plugins.oaiMetadataFormats.dc.OAIMetadataFormat_DC');
import('plugins.oaiMetadataFormats.dc.OAIMetadataFormatPlugin_DC');

class OAIMetadataFormat_DCTest extends PKPTestCase
{
    /**
     * @see PKPTestCase::getMockedDAOs()
     */
    protected function getMockedDAOs()
    {
        return ['OAIDAO', 'GalleyDAO'];
    }

    /**
     * @see PKPTestCase::getMockedRegistryKeys()
     */
    protected function getMockedRegistryKeys()
    {
        return ['request'];
    }

    /**
     * @covers OAIMetadataFormat_DC
     * @covers Dc11SchemaArticleAdapter
     */
    public function testToXml()
    {
        $this->markTestSkipped('Skipped because of weird class interaction with ControlledVocabDAO.');

        //
        // Create test data.
        //
        $journalId = 1;

        // Enable the DOI plugin.
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /** @var PluginSettingsDAO $pluginSettingsDao */
        $pluginSettingsDao->updateSetting($journalId, 'doipubidplugin', 'enabled', 1);
        $pluginSettingsDao->updateSetting($journalId, 'doipubidplugin', 'enableIssueDoi', 1);
        $pluginSettingsDao->updateSetting($journalId, 'doipubidplugin', 'enablePublicationDoi', 1);
        $pluginSettingsDao->updateSetting($journalId, 'doipubidplugin', 'enableRepresentationyDoi', 1);

        // Author
        $author = new Author();
        $author->setGivenName('author-firstname', 'en_US');
        $author->setFamilyName('author-lastname', 'en_US');
        $author->setAffiliation('author-affiliation', 'en_US');
        $author->setEmail('someone@example.com');

        // Article
        $article = $this->getMockBuilder(Submission::class)
            ->setMethods(['getBestId'])
            ->getMock();
        $article->expects($this->any())
            ->method('getBestId')
            ->will($this->returnValue(9));
        $article->setId(9);
        $article->setJournalId($journalId);
        $author->setSubmissionId($article->getId());
        $article->setPages(15);
        $article->setType('art-type', 'en_US');
        $article->setTitle('article-title-en', 'en_US');
        $article->setTitle('article-title-de', 'de_DE');
        $article->setDiscipline('article-discipline', 'en_US');
        $article->setSubject('article-subject', 'en_US');
        $article->setAbstract('article-abstract', 'en_US');
        $article->setSponsor('article-sponsor', 'en_US');
        $article->setStoredPubId('doi', 'article-doi');
        $article->setLanguage('en_US');

        // Galleys
        $galley = Repo::galley()->newDataObject();
        $galley->setId(98);
        $galley->setStoredPubId('doi', 'galley-doi');
        $galleys = [$galley];

        // Journal
        $journal = $this->getMockBuilder(Journal::class)
            ->setMethods(['getSetting'])
            ->getMock();
        $journal->expects($this->any())
            ->method('getSetting') // includes getTitle()
            ->will($this->returnCallback([$this, 'getJournalSetting']));
        $journal->setPrimaryLocale('en_US');
        $journal->setPath('journal-path');
        $journal->setId($journalId);

        // Section
        $section = new Section();
        $section->setIdentifyType('section-identify-type', 'en_US');

        // Issue
        $issue = $this->getMockBuilder(Issue::class)
            ->setMethods(['getIssueIdentification'])
            ->getMock();
        $issue->expects($this->any())
            ->method('getIssueIdentification')
            ->will($this->returnValue('issue-identification'));
        $issue->setId(96);
        $issue->setDatePublished('2010-11-05');
        $issue->setStoredPubId('doi', 'issue-doi');
        $issue->setJournalId($journalId);


        //
        // Create infrastructural support objects
        //

        // Router
        $router = $this->getMockBuilder(PKPRouter::class)
            ->setMethods(['url'])
            ->getMock();
        $application = Application::get();
        $router->setApplication($application);
        $router->expects($this->any())
            ->method('url')
            ->will($this->returnCallback([$this, 'routerUrl']));

        // Request
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['getRouter'])
            ->getMock();
        $request->expects($this->any())
            ->method('getRouter')
            ->will($this->returnValue($router));
        Registry::set('request', $request);


        //
        // Create mock DAOs
        //

        // FIXME getBySubmissionId should use the publication id now.
        $authorDao = $this->getMockBuilder(AuthorDAO::class)
            ->setMethods(['getBySubmissionId'])
            ->getMock();
        $authorDao->expects($this->any())
            ->method('getBySubmissionId')
            ->will($this->returnValue([$author]));
        DAORegistry::registerDAO('AuthorDAO', $authorDao);

        // Create a mocked OAIDAO that returns our test data.
        $oaiDao = $this->getMockBuilder(OAIDAO::class)
            ->setMethods(['getJournal', 'getSection', 'getIssue'])
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

        // Create a mocked GallyeDAO that returns our test data.
        $galleyDao = $this->getMockBuilder(GalleyDAO::class)
            ->setMethods(['getBySubmissionId'])
            ->getMock();
        $galleyDao->expects($this->any())
            ->method('getBySubmissionId')
            ->will($this->returnValue($galleys));

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
        self::assertXmlStringEqualsXmlFile('tests/plugins/oaiMetadataFormats/dc/expectedResult.xml', $xml);
    }


    //
    // Public helper methods
    //
    /**
     * Callback for journal settings.
     *
     * @param string $settingName
     */
    public function getJournalSetting($settingName)
    {
        switch ($settingName) {
            case 'name':
                return ['en_US' => 'journal-title'];

            case 'licenseTerms':
                return ['en_US' => 'journal-copyright'];

            case 'publisherInstitution':
                return ['journal-publisher'];

            case 'onlineIssn':
                return 'onlineIssn';

            case 'printIssn':
                return null;

            default:
                self::fail('Required journal setting is not necessary for the purpose of this test.');
        }
    }


    //
    // Private helper methods
    //
    /**
     * Callback for router url construction simulation.
     *
     * @param null|mixed $newContext
     * @param null|mixed $handler
     * @param null|mixed $op
     * @param null|mixed $path
     */
    public function routerUrl($request, $newContext = null, $handler = null, $op = null, $path = null)
    {
        return $handler . '-' . $op . '-' . implode('-', $path);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // See: http://docs.mockery.io/en/latest/reference/phpunit_integration.html
        \Mockery::close();
    }
}
