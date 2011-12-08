<?php

/**
 * @file tests/functional/plugins/importexport/datacite/FunctionalDataciteExportTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalDataciteExportTest
 * @ingroup tests_functional_plugins_importexport_datacite
 *
 * @brief Test the DataCite plug-in.
 *
 * FEATURE: DataCite DOI registration and export
 *   AS A    journal manager
 *   I WANT  to be able to register DOIs for issues, articles and
 *           supplementary files with the DOI registration agency DataCite
 *   SO THAT these objects can be uniquely identified and
 *           discovered through public meta-data searches.
 */


import('tests.functional.plugins.importexport.FunctionalDoiExportTest');
import('plugins.importexport.datacite.DataciteExportPlugin');

class FunctionalDataciteExportTest extends FunctionalDoiExportTest {

	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		$this->pluginId = 'datacite';

		$baseUrl = Config::getVar('debug', 'webtest_base_url');
		$indexPage = $baseUrl . '/index.php/test/manager/importexport/plugin/DataciteExportPlugin';
		$this->pages = array(
			'index' => $indexPage,
			'suppFiles' => $indexPage . '/suppFiles',
			'settings' => $baseUrl . '/index.php/test/manager/plugin/importexport/DataciteExportPlugin/settings'
		);

		$this->defaultPluginSettings = array(
			'symbol' => 'test-symbol',
			'password' => 'test-password'
		);

		parent::setUp();

		$this->configurePlugin();
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::doExportObjectTest().
	 *
	 * EXAMPLES:
	 *   export plug-in      |options|object type|object id(s)|export format    |sample file(s)
	 *   ====================|=======|===========|============|=================|==================
	 *   DataciteExportPlugin|./.    |issue      |1           |DataCite resource|datacite-issue.xml
	 */
	public function testExportIssue() {
		$this->testExpectJournalNameAsPublisher();
		$this->doExportObjectTest('issue', 1, 'DataciteExportPlugin', 'datacite-issue.xml');
	}


	/**
	 * SCENARIO: see FunctionalDoiExportTest::testExpectJournalNameAsPublisher()
	 */
	protected function checkThatPublisherIsJournalName($xml) {
		// Test that the publisher is set to the journal title.
		self::assertContains('<publisher>test</publisher>', $xml);
		self::assertContains('<creatorName>test</creatorName>', $xml);

		// Change publisher to the default.
		$xml = str_replace(
			'<publisher>test</publisher>',
			'<publisher>Test Publisher</publisher>',
			$xml
		);

		// Change (issue) creator to the default.
		return str_replace(
			'<creatorName>test</creatorName>',
			'<creatorName>Test Publisher</creatorName>',
			$xml
		);
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::doExportObjectTest().
	 *
	 * EXAMPLES:
	 *   export plug-in      |options|object type|object id(s)|export format    |sample file(s)
	 *   ====================|=======|===========|============|=================|====================
	 *   DataciteExportPlugin|./.    |article    |1           |DataCite resource|datacite-article.xml
	 */
	public function testExportArticle() {
		$this->doExportObjectTest('article', 1, 'DataciteExportPlugin', 'datacite-article.xml');
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::doExportObjectTest().
	 *
	 * EXAMPLES:
	 *   export plug-in      |options|object type|object id(s)|export format    |sample file(s)
	 *   ====================|=======|===========|============|=================|===========================
	 *   DataciteExportPlugin|./.    |galley     |1,2,3       |DataCite resource|datacite-galley-{1,2,3}.xml
	 */
	public function testExportGalley() {
		$sampleFiles = array(
			'datacite-galley-1.xml',
			'datacite-galley-2.xml',
			'datacite-galley-3.xml',
		);
		$this->doExportObjectTest('galley', array(1,2,3), 'DataciteExportPlugin', $sampleFiles);
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::doExportObjectTest().
	 *
	 * EXAMPLES:
	 *   export plug-in      |options|object type|object id(s)|export format    |sample file(s)
	 *   ====================|=======|===========|============|=================|======================
	 *   DataciteExportPlugin|./.    |supp file  |1           |DataCite resource|datacite-supp-file.xml
	 */
	public function testExportSuppFile() {
		$this->doExportObjectTest('suppFile', 1, 'DataciteExportPlugin', 'datacite-supp-file.xml');
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::testExportUnregisteredDois().
	 *
	 * EXAMPLES:
	 *   export plug-in      |objects                                            |XML files
	 *   ====================|===================================================|==========================================================================================
	 *   DataciteExportPlugin|issue 1; article 1; galleys 1, 2 and 3; supp-file 1|datacite-article.xml,datacite-galley-{1,2,3}.xml,datacite-issue.xml,datacite-supp-file.xml
	 */
	public function testExportUnregisteredDois() {
		$objects = array(
			'issue' => 1,
			'article' => 1,
			'galley' => array(1,2,3),
			'suppFile' => 1
		);
		$xmlFiles = array(
			'datacite-article.xml',
			array(
				'datacite-galley-1.xml',
				'datacite-galley-2.xml',
				'datacite-galley-3.xml',
			),
			'datacite-issue.xml',
			'datacite-supp-file.xml'
		);
		parent::testExportUnregisteredDois('DataciteExportPlugin', $objects, $xmlFiles);
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::testRegisterOrExportSpecificObjects().
	 *
	 * EXAMPLES:
	 *   object  |export page                                                   |register or export
	 *   ========|==============================================================|==================
	 *   Issue   |.../manager/importexport/plugin/DataciteExportPlugin/issues   |Register
	 *   Issue   |.../manager/importexport/plugin/DataciteExportPlugin/issues   |Export
	 *   Article |.../manager/importexport/plugin/DataciteExportPlugin/articles |Register
	 *   Article |.../manager/importexport/plugin/DataciteExportPlugin/articles |Export
	 *   Galley  |.../manager/importexport/plugin/DataciteExportPlugin/galleys  |Register
	 *   Galley  |.../manager/importexport/plugin/DataciteExportPlugin/galleys  |Export
	 *   SuppFile|.../manager/importexport/plugin/DataciteExportPlugin/suppFiles|Register
	 *   SuppFile|.../manager/importexport/plugin/DataciteExportPlugin/suppFiles|Export
	 */
	public function testRegisterOrExportSpecificObjects() {
		parent::testRegisterOrExportSpecificObjects(array('issue', 'article', 'galley', 'suppFile'));
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testRegisterUnregisteredDois().
	 */
	public function testRegisterUnregisteredDois() {
		parent::testRegisterUnregisteredDois(array('Issue', 'Article', 'Galley', 'Supplementary File'));
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::testObjectsWithoutDOICannotBeSelectedForExport().
	 *
	 * EXAMPLES:
	 *   export page
	 *   ==============================================================
	 *   .../manager/importexport/plugin/DataciteExportPlugin/issues
	 *   .../manager/importexport/plugin/DataciteExportPlugin/articles
	 *   .../manager/importexport/plugin/DataciteExportPlugin/galleys
	 *   .../manager/importexport/plugin/DataciteExportPlugin/suppFiles
	 *   .../manager/importexport/plugin/DataciteExportPlugin/all
	 */
	public function testObjectsWithoutDOICannotBeSelectedForExport() {
		parent::testObjectsWithoutDOICannotBeSelectedForExport(array('issues', 'articles', 'galleys', 'suppFiles', 'all'));
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testPluginSettings().
	 */
	public function testPluginSettings() {
		$tests = array(
			array(
				'symbol' => 'some-test-symbol',
				'password' => 'some-password'
			),
			array(
				'symbol' => 'some-other-symbol',
				'password' => 'another-password'
			)
		);
		$inputTypes = array(
			'symbol' => TEST_INPUTTYPE_TEXT,
			'password' => TEST_INPUTTYPE_TEXT
		);
		parent::testPluginSettings($tests, $inputTypes);
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testConfigurationError().
	 */
	public function testConfigurationError() {
		parent::testConfigurationError(array('issues', 'articles', 'galleys', 'suppFiles', 'all'), 'symbol');
	}


	/**
	 * SCENARIO OUTLINE: See FunctionalDoiExportTest::testExportObjectsViaCLI().
	 *
	 * EXAMPLES:
	 *
	 *   export plug-in      |settings|export object type|object ids|XML file
	 *   ====================|========|==================|==========|=====================================
	 *   DataciteExportPlugin|./.     |issues            |1         |datacite-issue.xml
	 *   DataciteExportPlugin|./.     |articles          |1         |datacite-article.xml
	 *   DataciteExportPlugin|./.     |galleys           |1         |datacite-galley-1.xml
	 *   DataciteExportPlugin|./.     |galleys           |1 2 3     |datacite-galley-{1,2,3}.xml
	 *   DataciteExportPlugin|./.     |suppFiles         |1         |datacite-supp-file.xml
	 */
	public function testExportObjectsViaCLI() {
		$examples = array(
			array('issues', '1', 'datacite-issue.xml'),
			array('articles', '1', 'datacite-article.xml'),
			array('galleys', '1', 'datacite-galley-1.xml'),
			array('galleys', '1 2 3', array('datacite-galley-1.xml', 'datacite-galley-2.xml', 'datacite-galley-3.xml')),
			array('suppFiles', '1', 'datacite-supp-file.xml')
		);

		foreach($examples as $example) {
			list($exportObjectType, $objectIds, $xmlFiles) = $example;
			parent::testExportObjectsViaCLI('DataciteExportPlugin', $exportObjectType, $objectIds, $xmlFiles);
		}
	}


	/**
	 * SCENARIO OUTLINE: Register objects on the command line.
	 *
	 * EXAMPLES:
	 *
	 *   export plug-in      |export object type|object ids
	 *   ====================|==================|==========
	 *   DataciteExportPlugin|issues            |1
	 *   DataciteExportPlugin|articles          |1
	 *   DataciteExportPlugin|galleys           |1 2 3
	 *   DataciteExportPlugin|suppFiles         |1
	 */
	public function testRegisterObjectViaCLI() {
		parent::testRegisterObjectViaCLI();
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testUnsupportedObjectTypeCliError().
	 */
	public function testUnsupportedObjectTypeCliError() {
		parent::testUnsupportedObjectTypeCliError('DataciteExportPlugin');
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testNonExistentJournalPathCliError().
	 */
	public function testNonExistentJournalPathCliError() {
		parent::testNonExistentJournalPathCliError('DataciteExportPlugin');
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testOutputFileNotWritableCliError().
	 */
	public function testOutputFileNotWritableCliError() {
		parent::testOutputFileNotWritableCliError('DataciteExportPlugin');
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testNonExistentObjectIdCliError().
	 */
	public function testNonExistentObjectIdCliError() {
		parent::testNonExistentObjectIdCliError('DataciteExportPlugin');
	}

	/**
	 * @see FunctionalDoiExportTest::cleanXml()
	 */
	protected function cleanXml($xml) {
		// Fix missing translations.
		$xml = str_replace('##editor.issues.pages##', 'Pages', $xml);

		return parent::cleanXml($xml);
	}
}
?>