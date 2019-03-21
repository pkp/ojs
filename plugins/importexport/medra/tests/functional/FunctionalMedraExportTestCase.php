<?php

/**
 * @file plugins/importexport/medra/tests/functional/FunctionalMedraExportTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalMedraExportTest
 * @ingroup plugins_importexport_medra_tests_functional
 *
 * @brief Test the mEDRA plug-in.
 *
 * FEATURE: mEDRA DOI registration and export
 *   AS A    journal manager
 *   I WANT  to be able to register DOIs for issues and articles
 *           with the DOI registration agency mEDRA
 *   SO THAT these objects can be uniquely identified and
 *           discovered through public meta-data searches.
 */

import('tests.functional.plugins.importexport.FunctionalDoiExportTest');
import('plugins.importexport.medra.MedraExportPlugin');

class FunctionalMedraExportTest extends FunctionalDoiExportTest {
	const TEST_ACCOUNT = 'TEST_OJS';

	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		$this->pluginId = 'medra';

		// Retrieve and check configuration. (We're in a chicken
		// and egg situation: This means that we cannot call
		// parent::setUp() at this point so we have to retrieve
		// the base URL here although it will be retrieved again
		// in the parent class.)
		$baseUrl = Config::getVar('debug', 'webtest_base_url');
		$medraPassword = Config::getVar('debug', 'webtest_medra_pw');
		if (empty($baseUrl) || empty($medraPassword)) {
			$this->markTestSkipped(
				'Please set webtest_base_url and webtest_medra_pw in your ' .
				'config.php\'s [debug] section to the base url of your test server ' .
				'and the password of your Medra test account.'
			);
		}

		$this->pages = array(
			'index' => $baseUrl . '/index.php/test/manager/importexport/plugin/MedraExportPlugin',
			'settings' => $baseUrl . '/index.php/test/manager/plugin/importexport/MedraExportPlugin/settings'
		);

		$this->defaultPluginSettings = array(
			'username' => self::TEST_ACCOUNT,
			'password' => $medraPassword,
			'registrantName' => 'Registrant',
			'fromCompany' => 'From Company',
			'fromName' => 'From Person',
			'fromEmail' => 'from@email.com',
			'publicationCountry' => 'US',
			'exportIssuesAs' => O4DOI_ISSUE_AS_WORK
		);

		parent::setUp('1749');
	}


	/**
	 * SCENARIO: see FunctionalDoiExportTest::doTestExpectJournalNameAsPublisher()
	 *
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::doExportObjectTest().
	 *
	 * EXAMPLES:
	 *   export plug-in   |options            |object type|object id(s)|export format             |sample file(s)
	 *   =================|===================|===========|============|==========================|=====================================
	 *   MedraExportPlugin|exp. issues as work|issue      |1           |O4DOI serial issue as work|serial-issue-as-work.xml
	 */
	public function testExportSerialIssueAsWork() {
		$this->removeRegisteredDois('MedraExportPlugin');
		$this->configurePlugin(array('exportIssuesAs' => O4DOI_ISSUE_AS_WORK));
		$this->doTestExpectJournalNameAsPublisher();
		$this->doExportObjectTest('issue', 1, 'MedraExportPlugin', 'serial-issue-as-work.xml');
	}


	/**
	 * SCENARIO: see FunctionalDoiExportTest::doTestExpectJournalNameAsPublisher()
	 */
	protected function checkThatPublisherIsJournalName($xml) {
		// Test that the publisher is set to the journal title.
		self::assertContains('<PublisherName>test</PublisherName>', $xml);

		// Change publisher to the default.
		return str_replace(
			'<PublisherName>test</PublisherName>',
			'<PublisherName>Test Publisher</PublisherName>',
			$xml
		);
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::doExportObjectTest().
	 *
	 * EXAMPLES:
	 *   export plug-in   |options            |object type|object id(s)|export format             |sample file(s)
	 *   =================|===================|===========|============|==========================|=====================================
	 *   MedraExportPlugin|exp. issues as man.|issue      |1           |O4DOI serial issue as man.|serial-issue-as-manifestation.xml
	 */
	public function testExportSerialIssueAsManifestation() {
		$this->removeRegisteredDois('MedraExportPlugin');
		$this->configurePlugin(array('exportIssuesAs' => O4DOI_ISSUE_AS_MANIFESTATION));
		$this->doExportObjectTest('issue', 1, 'MedraExportPlugin', 'serial-issue-as-manifestation.xml');
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::doExportObjectTest().
	 *
	 * EXAMPLES:
	 *   export plug-in   |options            |object type|object id(s)|export format             |sample file(s)
	 *   =================|===================|===========|============|==========================|=====================================
	 *   MedraExportPlugin|exp. issues as work|article    |1           |O4DOI serial art. as work |serial-article-as-work-1.xml
	 */
	public function testExportSerialArticleAsWork() {
		$this->removeRegisteredDois('MedraExportPlugin');
		$this->configurePlugin(array('exportIssuesAs' => O4DOI_ISSUE_AS_WORK));
		$this->doExportObjectTest('article', 1, 'MedraExportPlugin', 'serial-article-as-work-1.xml');
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::doExportObjectTest().
	 *
	 * EXAMPLES:
	 *   export plug-in   |options            |object type|object id(s)|export format             |sample file(s)
	 *   =================|===================|===========|============|==========================|=====================================
	 *   MedraExportPlugin|exp. issues as man.|galley     |1           |O4DOI serial art. as man. |serial-article-as-manifestation-1.xml
	 *   MedraExportPlugin|exp. issues as man.|galley     |1,2,3       |O4DOI serial art. as man. |serial-article-as-manifestation-2.xml
	 */
	public function testExportSerialArticleAsManifestation() {
		$this->removeRegisteredDois('MedraExportPlugin');
		$this->configurePlugin(array('exportIssuesAs' => O4DOI_ISSUE_AS_MANIFESTATION));
		$this->doExportObjectTest('galley', 1, 'MedraExportPlugin', 'serial-article-as-manifestation-1.xml', true);
		$this->doExportObjectTest('galley', array(1,2,3), 'MedraExportPlugin', 'serial-article-as-manifestation-2.xml', true);
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::testExportUnregisteredDois().
	 *
	 * EXAMPLES:
	 *   export plug-in   |objects                               |XML files
	 *   =================|======================================|==============================================================================
	 *   MedraExportPlugin|issue 1; article 1; galleys 1, 2 and 3|serial-article-as-{work,manifestation}-2.xml,serial-issue-as-manifestation.xml
	 */
	public function testExportUnregisteredDois() {
		$this->removeRegisteredDois('MedraExportPlugin');
		$this->configurePlugin(array('exportIssuesAs' => O4DOI_ISSUE_AS_MANIFESTATION));

		// Test whether exporting updates changes correctly
		// sets the notification type.
		$pluginInstance = $this->instantiatePlugin('MedraExportPlugin');
		$hookName = 'articledao::getAdditionalFieldNames';
		HookRegistry::register($hookName, array($pluginInstance, 'getAdditionalFieldNames'));
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$testObject = $articleDao->getById(1);
		$testObject->setData('medra::' . DOI_EXPORT_REGDOI, '1749/t.v1i1.1');
		$articleDao->updateObject($testObject);

		// Remove the hook.
		$hooks = HookRegistry::getHooks();
		foreach($hooks[$hookName] as $index => $hook) {
			if (is_a($hook[0], 'MedraExportPlugin')) {
				unset($hooks[$hookName][$index]);
				break;
			}
		}

		$objects = array(
			'issue' => 1,
			'article' => 1,
			'galley' => array(1,2,3)
		);
		$xmlFiles = array(
			'serial-article-as-work-2.xml',
			'serial-article-as-manifestation-2.xml',
			'serial-issue-as-manifestation.xml'
		);
		parent::testExportUnregisteredDois('MedraExportPlugin', $objects, $xmlFiles);
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::testRegisterOrExportSpecificObjects().
	 *
	 * EXAMPLES:
	 *   object  |export page                                                |register or export
	 *   ========|===========================================================|==================
	 *   Issue   |.../manager/importexport/plugin/MedraExportPlugin/issues   |Register
	 *   Issue   |.../manager/importexport/plugin/MedraExportPlugin/issues   |Export
	 *   Article |.../manager/importexport/plugin/MedraExportPlugin/articles |Register
	 *   Article |.../manager/importexport/plugin/MedraExportPlugin/articles |Export
	 *   Galley  |.../manager/importexport/plugin/MedraExportPlugin/galleys  |Register
	 *   Galley  |.../manager/importexport/plugin/MedraExportPlugin/galleys  |Export
	 *
	 *
	 * SCENARIO: Reset button.
	 *   GIVEN I already registered an object once
	 *    WHEN I am navigating to the list containing the object
	 *    THEN I'll see an additional "Reset" button
	 *
	 *
	 * SCENARIO: Reset the registration state.
	 *    WHEN I click on the "Reset" button in an object list
	 *    THEN all internal registration state will be deleted
	 *     AND the registration button will read "Register" rather
	 *         than "Update" again
	 *     AND I will no longer see an additional "Reset" button.
	 */
	public function testRegisterOrExportSpecificObjects() {
		parent::testRegisterOrExportSpecificObjects('MedraExportPlugin', array('issue', 'article', 'galley'), self::TEST_ACCOUNT, true);
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testRegisterUnregisteredDois().
	 */
	public function testRegisterUnregisteredDois() {
		parent::testRegisterUnregisteredDois('MedraExportPlugin', array('Issue', 'Article', 'Galley'), self::TEST_ACCOUNT);
	}


	/**
	 * SCENARIO: Explain the work/product distinction
	 *
	 *    WHEN I navigate to the mEDRA export plug-in home page or
	 *         article export page
	 *    THEN I'll see an explanatory text: "DOIs assigned to articles
	 *         will be exported to mEDRA as 'works'. DOIs assigned to
	 *         galleys will be exported as 'manifestations'."
	 *     AND the words 'work' and 'manifestation' will link to
	 *         <http://www.medra.org/en/metadata_td.htm>.
	 */
	public function testWorkProductExplanation() {
		$this->logIn();
		foreach(array('index', 'articles', 'galleys', 'all') as $pageName) {
			$this->open($this->pages[$pageName]);
			$this->assertElementPresent('//a[@href="http://www.medra.org/en/metadata_td.htm"]');
		}
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::testObjectsWithoutDOICannotBeSelectedForExport().
	 *
	 * EXAMPLES:
	 *   export page
	 *   ==========================================================
	 *   .../manager/importexport/plugin/MedraExportPlugin/issues
	 *   .../manager/importexport/plugin/MedraExportPlugin/articles
	 *   .../manager/importexport/plugin/MedraExportPlugin/galleys
	 *   .../manager/importexport/plugin/MedraExportPlugin/all
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
				'fromCompany' => 'FromComp1',
				'fromName' => 'FromName1',
				'fromEmail' => 'from@email.us',
				'registrantName' => 'Registrant1',
				'publicationCountry' => 'US',
				'exportIssuesAs' => O4DOI_ISSUE_AS_WORK
			),
			array(
				'fromCompany' => 'FromComp2',
				'fromName' => 'FromName2',
				'fromEmail' => 'from@email.de',
				'registrantName' => 'Registrant2',
				'publicationCountry' => 'DE',
				'exportIssuesAs' => O4DOI_ISSUE_AS_MANIFESTATION
			)
		);
		$inputTypes = array(
			'fromCompany' => TEST_INPUTTYPE_TEXT,
			'fromName' => TEST_INPUTTYPE_TEXT,
			'fromEmail' => TEST_INPUTTYPE_EMAIL,
			'registrantName' => TEST_INPUTTYPE_TEXT,
			'publicationCountry' => TEST_INPUTTYPE_SELECT,
			'exportIssuesAs' => TEST_INPUTTYPE_SELECT
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
	 *   not configured the plug-in
	 */
	public function testConfigurationError() {
		$exportPages = array('issues', 'articles', 'galleys', 'all');
		parent::testConfigurationError($exportPages);

		// Test that the plug-in cannot be used when required configuration parameters are missing.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(1, $this->pluginId . 'exportplugin', 'registrantName', '');
		$this->assertConfigurationError($exportPages, 'The plug-in is not fully set up');
	}


	/**
	 * SCENARIO OUTLINE: CLI export, see FunctionalDoiExportTest::testExportAndRegisterObjectsViaCli().
	 *
	 * EXAMPLES:
	 *
	 *   export plug-in   |settings           |export object type|object ids|XML file
	 *   =================|===================|==================|==========|=====================================
	 *   MedraExportPlugin|exp. issues as work|issues            |1         |serial-issue-as-work.xml
	 *   MedraExportPlugin|exp. issues as man.|issues            |1         |serial-issue-as-manifestation.xml
	 *   MedraExportPlugin|exp. issues as work|articles          |1         |serial-article-as-work-1.xml
	 *   MedraExportPlugin|exp. issues as man.|galleys           |1         |serial-article-as-manifestation-1.xml
	 *   MedraExportPlugin|exp. issues as man.|galleys           |1 2 3     |serial-article-as-manifestation-2.xml
	 */
	public function testExportAndRegisterObjectsViaCli() {
		$this->removeRegisteredDois('MedraExportPlugin');
		$examples = array(
			array(O4DOI_ISSUE_AS_WORK, 'issues', '1', 'serial-issue-as-work.xml'),
			array(O4DOI_ISSUE_AS_MANIFESTATION, 'issues', '1', 'serial-issue-as-manifestation.xml'),
			array(O4DOI_ISSUE_AS_WORK, 'articles', '1', 'serial-article-as-work-1.xml'),
			array(O4DOI_ISSUE_AS_MANIFESTATION, 'galleys', '1', 'serial-article-as-manifestation-1.xml'),
			array(O4DOI_ISSUE_AS_MANIFESTATION, 'galleys', '1 2 3', 'serial-article-as-manifestation-2.xml')
		);

		foreach($examples as $example) {
			list($exportIssuesAs, $exportObjectType, $objectIds, $xmlFile) = $example;

			// Configure the issue export type.
			$this->configurePlugin(array('exportIssuesAs' => $exportIssuesAs));

			parent::testExportAndRegisterObjectsViaCli('MedraExportPlugin', 'export', $exportObjectType, $objectIds, $xmlFile);
			parent::testExportAndRegisterObjectsViaCli('MedraExportPlugin', 'register', $exportObjectType, $objectIds);
			$this->removeRegisteredDois('MedraExportPlugin');
		}
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testUnsupportedObjectTypeCliError().
	 */
	public function testUnsupportedObjectTypeCliError() {
		parent::testUnsupportedObjectTypeCliError('MedraExportPlugin');
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testNonExistentJournalPathCliError().
	 */
	public function testNonExistentJournalPathCliError() {
		parent::testNonExistentJournalPathCliError('MedraExportPlugin');
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testOutputFileNotWritableCliError().
	 */
	public function testOutputFileNotWritableCliError() {
		parent::testOutputFileNotWritableCliError('MedraExportPlugin');
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testNonExistentObjectIdCliError().
	 */
	public function testNonExistentObjectIdCliError() {
		parent::testNonExistentObjectIdCliError('MedraExportPlugin');
	}


	//
	// Implement template methods from FunctionalDoiExportTest
	//
	/**
	 * @see FunctionalDoiExportTest::cleanXml()
	 */
	protected function cleanXml($xml) {
		// Fix URLs.
		$xml = PKPString::regexp_replace('#http://[^\s]+/index.php/(test|index)#', 'http://example.com/index.php/test', $xml);

		// Fix sent date.
		$xml = PKPString::regexp_replace('/<SentDate>[0-9]{12}<\/SentDate>/', '<SentDate>201111082218</SentDate>', $xml);

		// Fix version.
		$xml = PKPString::regexp_replace('/(<MessageNote>[^<]*)([0-9]\.){4}(<\/MessageNote>)/', '\1x.x.x.x.\3', $xml);

		return parent::cleanXml($xml);
	}
}

