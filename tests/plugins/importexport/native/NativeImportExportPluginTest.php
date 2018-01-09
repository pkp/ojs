<?php

/**
 * @file tests/plugins/importexport/native/NativeImportExportPluginTest.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportPluginTest
 * @ingroup tests_plugins_importexport_native
 * @see NativeImportExportPlugin
 *
 * @brief Test class for the NativeImportExportPlugin class
 */

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.core.PKPRouter');
import('classes.article.Article');
import('classes.journal.Journal');
import('plugins.importexport.native.NativeImportExportPlugin');

class NativeImportExportPluginTest extends DatabaseTestCase {
	/** @var NativeImportExportPlugin */
	private $plugin;

	//
	// Implementing protected template methods from DatabaseTestCase
	//
	/**
	 * @see DatabaseTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return PKP_TEST_ENTIRE_DB;
	}


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
			$router = $this->getMock('PKPRouter', array('url'));
			$router->expects($this->any())
			       ->method('url')
			       ->will($this->returnValue('http://test-url'));
			$request->setRouter($router);
		}
		$p = PluginRegistry::loadCategory('importexport', true, 0);
		$this->plugin = PluginRegistry::getPlugin('importexport', 'NativeImportExportPlugin');
		assert(is_a($this->plugin, 'ImportExportPlugin'));
	}


	//
	// Unit tests
	//
	/**
	 * @covers NativeImportExportPlugin
	 * @covers SolrSearchRequest
	 */
	public function testExport() {
		// Get a journal
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getJournalByPath('publicknowledge');
		$this->assertTrue(is_a($journal, 'Journal'));

		// Get an article
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$articles =& $publishedArticleDao->getBySetting('title', 'A Review of Information Systems and Corporate Memory: design for staff turn-over', $journal->getId());
		$this->assertTrue(count($articles)==1);
		$article = array_shift($articles);
		$this->assertTrue(is_a($article, 'Article'));

		// Get issue
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueById($article->getIssueId());
		$this->assertTrue(is_a($issue, 'Issue'));

		// Get section
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($article->getSectionId());
		$this->assertTrue(is_a($section, 'Section'));

		// Export the article and load it using DOM
		$filename = tempnam(sys_get_temp_dir(), 'NativeExport');
		$this->plugin->exportArticle($journal, $issue, $section, $article, $filename);
		$doc = new DOMDocument();
		$doc->load($filename);
		$this->assertTrue(is_a($doc, 'DOMDocument'));

		$doc->formatOutput = true;
		$generatedXml = trim($doc->saveXML());

		$dummyFile = getenv('DUMMYFILE');
		import('lib.pkp.classes.site.VersionCheck');
		$currentVersion =& VersionCheck::getCurrentDBVersion();
		$params = array(
			'{$embedContents}' => base64_encode(file_get_contents($dummyFile)),
			'{$currentDate}' => date('Y-m-d'),
			'{$currentYear}' => date('Y'),
			'{$dummyFileName}' => basename($dummyFile),
			'{$version}' => urlencode($currentVersion->getMajor() . '.' . $currentVersion->getMinor() . '.' . $currentVersion->getRevision()),
		);

		$this->assertEquals(
			str_replace(
				array_keys($params),
				array_values($params),
				trim(file_get_contents(dirname(__FILE__) . '/expectedExport.xml'))
			),
			$generatedXml
		);

		unlink($filename); // Clean up temporary file
	}

	/**
	 * @covers NativeImportExportPlugin
	 * @covers SolrSearchRequest
	 */
	public function testImport() {
		$dummyFile = getenv('DUMMYFILE');
		$params = array(
			'{$embedContents}' => base64_encode(file_get_contents($dummyFile)),
			'{$currentDate}' => date('Y-m-d'),
			'{$currentYear}' => date('Y'),
			'{$dummyFileName}' => basename($dummyFile),
		);

		$parser = new XMLParser();
                $doc =& $parser->parseText(str_replace(
			array_keys($params),
			array_values($params),
			trim(file_get_contents(dirname(__FILE__) . '/expectedExport.xml'))
		));

		// Get a journal
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getJournalByPath('publicknowledge');
		$this->assertTrue(is_a($journal, 'Journal'));

		// Alter the journal configuration in order to prevent automatically-assigned
		// permissions from looking the same as the import ones
		$journal->updateSetting('copyrightHolderType', 'other', 'string');
		$journal->updateSetting('copyrightHolderOther', array('en_US' => 'Tucker Fitzgerald'), 'string', true);
		$journal->updateSetting('copyrightYearBasis', 'issue', 'string'); // Later we'll set the issue pub date
		$journal->updateSetting('licenseUrl', 'http://www.apache.org/licenses/LICENSE-2.0', 'string');

		// Get an article
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$articles =& $publishedArticleDao->getBySetting('title', 'A Review of Information Systems and Corporate Memory: design for staff turn-over', $journal->getId());
		$this->assertTrue(count($articles)==1);
		$article = array_shift($articles);
		$this->assertTrue(is_a($article, 'Article'));

		// Get issue
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueById($article->getIssueId());
		$this->assertTrue(is_a($issue, 'Issue'));
		// Set the issue publication date somewhere strange to properly test
		// copyright date on import.
		$issue->setDatePublished('1980-10-05');
		$issueDao->updateIssue($issue);

		// Get section
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($article->getSectionId());
		$this->assertTrue(is_a($section, 'Section'));

		// Get user
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getByUsername('admin');

		// Run the import
		$this->plugin->import('NativeImportDom');
		$result = NativeImportDom::importArticle($journal, $doc, $issue, $section, $newArticle, $errors, $user, true);
		$this->assertEquals(1, $result);

		// Check the resulting import
		$this->assertEquals('en_US', $newArticle->getLocale());
		$this->assertEquals($section->getId(), $newArticle->getSectionId());
		$this->assertEquals($journal->getId(), $newArticle->getJournalId());
		$this->assertEquals($user->getId(), $newArticle->getUserId());
		$this->assertEquals(STATUS_PUBLISHED, $newArticle->getStatus());
		$this->assertEquals(array('en_US' => 'A Review of Information Systems and Corporate Memory: design for staff turn-over'), $newArticle->getTitle(null));
		$this->assertEquals(array('en_US' => 'information technology;knowledge preservation'), $newArticle->getSubject(null));
		$this->assertEquals('en', $newArticle->getLanguage());
		$this->assertFalse($article->getId() == $newArticle->getId());

		// Check permissions
		$this->assertEquals(date('Y'), $newArticle->getCopyrightYear());
		$this->assertEquals(array('en_US' => 'Brian Vemer'), $newArticle->getCopyrightHolder(null));
		$this->assertEquals('https://creativecommons.org/licenses/by-nc-nd/4.0', $newArticle->getLicenseURL());
	}
}

?>
