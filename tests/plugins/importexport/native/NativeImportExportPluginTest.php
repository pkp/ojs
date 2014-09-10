<?php

/**
 * @file tests/plugins/importexport/native/NativeImportExportPluginTest.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportPluginTest
 * @ingroup tests_plugins_importexport_native
 * @see NativeImportExportPlugin
 *
 * @brief Test class for the NativeImportExportPlugin class
 */


require_mock_env('env2'); // Required for mock app locale.

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

		// Truncate embedded file contents
		$embeds = $doc->getElementsByTagName('embed');
		foreach ($embeds as $embed) {
			$embed->nodeValue = 'embed_contents';
		}

		$doc->formatOutput = true;
		$generatedXml = trim($doc->saveXML());

		$this->assertEquals($generatedXml, trim(file_get_contents(dirname(__FILE__) . '/expectedExport.xml')));
		
		unlink($filename); // Clean up temporary file
	}
}

?>
