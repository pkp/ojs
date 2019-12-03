<?php

/**
 * @file plugins/importexport/datacite/tests/functional/FunctionalDataciteExportTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalDataciteExportTest
 * @ingroup plugins_importexport_datacite_tests_functional
 *
 * @brief Test the DataCite plug-in.
 *
 * FEATURE: DataCite DOI registration and export
 *   AS A    journal manager
 *   I WANT  to be able to register DOIs for issues and articles
 *           with the DOI registration agency DataCite
 *   SO THAT these objects can be uniquely identified and
 *           discovered through public meta-data searches.
 */

import('tests.functional.plugins.importexport.FunctionalDoiExportTest');
import('plugins.importexport.datacite.DataciteExportPlugin');

class FunctionalDataciteExportTest extends FunctionalDoiExportTest {
	const TEST_ACCOUNT = 'TIB.OJSTEST';

	/** @var string See testRegisterObject() */
	private $fileToRegister, $dcPassword;

	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() : void {
		$this->pluginId = 'datacite';

		// Retrieve and check configuration. (We're in a chicken
		// and egg situation: This means that we cannot call
		// parent::setUp() at this point so we have to retrieve
		// the base URL here although it will be retrieved again
		// in the parent class.)
		$baseUrl = Config::getVar('debug', 'webtest_base_url');
		$this->dcPassword = Config::getVar('debug', 'webtest_datacite_pw');
		if (empty($baseUrl) || empty($this->dcPassword)) {
			$this->markTestSkipped(
				'Please set webtest_base_url and webtest_datacite_pw in your ' .
				'config.php\'s [debug] section to the base url of your test server ' .
				'and the password of your DataCite test account.'
			);
		}

		$indexPage = $baseUrl . '/index.php/test/manager/importexport/plugin/DataciteExportPlugin';
		$this->pages = array(
			'index' => $indexPage,
			'settings' => $baseUrl . '/index.php/test/manager/plugin/importexport/DataciteExportPlugin/settings'
		);

		$this->defaultPluginSettings = array(
			'username' => self::TEST_ACCOUNT,
			'password' => $this->dcPassword
		);

		parent::setUp('10.5072');

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
		$this->doTestExpectJournalNameAsPublisher();
		$this->doExportObjectTest('issue', 1, 'DataciteExportPlugin', 'datacite-issue.xml');
	}


	/**
	 * SCENARIO: see FunctionalDoiExportTest::doTestExpectJournalNameAsPublisher()
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
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::testExportUnregisteredDois().
	 *
	 * EXAMPLES:
	 *   export plug-in      |objects                                            |XML files
	 *   ====================|===================================================|==========================================================================================
	 *   DataciteExportPlugin|issue 1; article 1; galleys 1, 2 and 3|datacite-article.xml,datacite-galley-{1,2,3}.xml,datacite-issue.xml
	 */
	public function testExportUnregisteredDois() {
		$objects = array(
			'issue' => 1,
			'article' => 1,
			'galley' => array(1,2,3),
		);
		$xmlFiles = array(
			'datacite-article.xml',
			'datacite-galley-1.xml',
			'datacite-galley-2.xml',
			'datacite-galley-3.xml',
			'datacite-issue.xml',
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
	 */
	public function testRegisterOrExportSpecificObjects() {
		parent::testRegisterOrExportSpecificObjects('DataciteExportPlugin', array('issue', 'article', 'galley'), self::TEST_ACCOUNT);
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testRegisterUnregisteredDois().
	 */
	public function testRegisterUnregisteredDois() {
		parent::testRegisterUnregisteredDois('DataciteExportPlugin', array('Issue', 'Article', 'Galley'), self::TEST_ACCOUNT);

		// Check whether the DOIs and meta-data have actually been registered.
		$registrationTests = array(
			array('datacite-issue.xml', 'issue/view/1', '10.5072/t.v1i1'),
			array('datacite-article.xml', 'article/view/1', '10.5072/t.v1i1.1'),
			array('datacite-galley-1.xml', 'article/view/1/1', '10.5072/t.v1i1.1.g1'),
			array('datacite-galley-2.xml', 'article/view/1/2', '10.5072/t.v1i1.1.g2'),
			array('datacite-galley-3.xml', 'article/view/1/3', '10.5072/t.v1i1.1.g3'),
		);
		foreach($registrationTests as $registrationTest) {
			list($sampleFile, $targetUrl, $doi) = $registrationTest;
			$targetUrl = 'http://example.com/index.php/test/' . $targetUrl;
			$this->checkDoiRegistration($doi, $sampleFile, $targetUrl);
		}
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
	 *   .../manager/importexport/plugin/DataciteExportPlugin/all
	 */
	public function testObjectsWithoutDOICannotBeSelectedForExport() {
		parent::testObjectsWithoutDOICannotBeSelectedForExport(array('issues', 'articles', 'galleys', 'all'));
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testPluginSettings().
	 */
	public function testPluginSettings() {
		$tests = array(
			array(
				'username' => 'some-test-symbol',
				'password' => 'some-password'
			),
			array(
				'username' => 'some-other-symbol',
				'password' => 'another-password'
			)
		);
		$inputTypes = array(
			'username' => TEST_INPUTTYPE_TEXT,
			'password' => TEST_INPUTTYPE_TEXT
		);
		parent::testPluginSettings($tests, $inputTypes);
	}


	/**
	 * SCENARIO OUTLINE: See FunctionalDoiExportTest::testConfigurationError().
	 *
	 * EXAMPLES:
	 *   configuration error
	 *   ==========================
	 *   no DOI prefix configured
	 */
	public function testConfigurationError() {
		parent::testConfigurationError(array('issues', 'articles', 'galleys', 'all'));
	}


	/**
	 * SCENARIO OUTLINE: CLI export, see FunctionalDoiExportTest::testExportAndRegisterObjectsViaCli().
	 *
	 * EXAMPLES:
	 *
	 *   export plug-in      |settings|export object type|object ids|XML file
	 *   ====================|========|==================|==========|=====================================
	 *   DataciteExportPlugin|./.     |issues            |1         |datacite-issue.xml
	 *   DataciteExportPlugin|./.     |articles          |1         |datacite-article.xml
	 *   DataciteExportPlugin|./.     |galleys           |1         |datacite-galley-1.xml
	 *   DataciteExportPlugin|./.     |galleys           |1 2 3     |datacite-galley-{1,2,3}.xml
	 *
	 *
	 * SCENARIO OUTLINE: CLI registration, see FunctionalDoiExportTest::testExportAndRegisterObjectsViaCli().
	 *
	 * EXAMPLES:
	 *   export plug-in      |settings|export object type|object ids
	 *   ====================|========|==================|==========
	 *   DataciteExportPlugin|./.     |issues            |1
	 *   DataciteExportPlugin|./.     |articles          |1
	 *   DataciteExportPlugin|./.     |galleys           |1
	 *   DataciteExportPlugin|./.     |galleys           |1 2 3
	 */
	public function testExportAndRegisterObjectsViaCli() {
		$examples = array(
			array('issues', '1', 'datacite-issue.xml'),
			array('articles', '1', 'datacite-article.xml'),
			array('galleys', '1', 'datacite-galley-1.xml'),
			array('galleys', '1 2 3', array('datacite-galley-1.xml', 'datacite-galley-2.xml', 'datacite-galley-3.xml')),
		);

		foreach($examples as $example) {
			list($exportObjectType, $objectIds, $xmlFiles) = $example;
			parent::testExportAndRegisterObjectsViaCli('DataciteExportPlugin', 'export', $exportObjectType, $objectIds, $xmlFiles);
			parent::testExportAndRegisterObjectsViaCli('DataciteExportPlugin', 'register', $exportObjectType, $objectIds);
		}
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


	//
	// Implement template methods from FunctionalDoiExportTest
	//
	/**
	 * @see FunctionalDoiExportTest::checkDoiRegistration()
	 */
	protected function checkDoiRegistration($doi, $sampleFile, $expectedTargetUrl) {
		// Prepare HTTP session.
		$curlCh = curl_init ();

		// Set up basic authentication.
		$login = 'TIB.OJSTEST:' . $this->dcPassword;
		curl_setopt($curlCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curlCh, CURLOPT_USERPWD, $login);

		// Request the DOI's URL over SSL.
		$apiUrl = "https://mds.datacite.org/%action/$doi?testMode=true";
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		try {
			$tests = array(
				'doi' => $doi,
				'metadata' => $sampleFile
			);
			foreach($tests as $action => $expectedResponse) {
				// Set the URL for the API request.
				curl_setopt($curlCh, CURLOPT_URL, str_replace('%action', $action, $apiUrl));

				// Wait for Handle to propagate our information
				// but not longer than 1 minute.
				$lastStatus = null;
				$tryAgain = true;
				for ($secs=0; $secs <= 60 && $tryAgain; $secs+=10) {
					// Status 204 means that the DOI has been registered
					// but is not yet available due to Handle's latency.
					if ($lastStatus == 204) {
						sleep(5);
					}
					$response = curl_exec($curlCh);
					$lastStatus = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
					$tryAgain = ($lastStatus == 204);
				}

				if ($lastStatus == 204) {
					self::fail("The DOI $doi has been correctly registered but is not yet available " .
						"due to Handle's latency. Please re-execute this test in a few minutes.");
				}

				// The return status should be 200 - OK.
				self::assertEquals(200, $lastStatus);
				if ($action == 'doi') {
					// Check the registered target URL.
					self::assertEquals($expectedTargetUrl, $response);
				} else {
					// Check the registered meta-data.
					$this->assertXml($sampleFile, $response);
				}
			}
			// Check the registered meta-data.
		} catch(Exception $e) {
			curl_close($curlCh);
			throw $e;
		}


		// Destroy HTTP session.
		curl_close($curlCh);
	}

	/**
	 * @see FunctionalDoiExportTest::cleanXml()
	 */
	protected function cleanXml($xml) {
		// Fix missing translations.
		$xml = str_replace('##editor.issues.pages##', 'Pages', $xml);

		// Fix modified date.
		if (strpos($xml, '<date dateType="Updated">') !== false) {
			// We have to fix the modified date as it changes
			// too often (e.g. by using the test server manually)
			// to be controlled reliably.
			// Make sure we got the actual modified date but
			// replace it with the modified date in the sample data
			// so that our tests do not bail.
			$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
			$article = $submissionDao->getById(1);
			$modifiedDate = date('Y-m-d', strtotime($article->getLastModified()));
			$xml = str_replace('<date dateType="Updated">' . $modifiedDate, '<date dateType="Updated">2011-12-09', $xml);
		}

		return parent::cleanXml($xml);
	}
}

