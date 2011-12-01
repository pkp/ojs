<?php

/**
 * @file tests/functional/plugins/importexport/medra/FunctionalMedraExportTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalMedraExportTest
 * @ingroup tests_functional_plugins_importexport_medra
 *
 * @brief Test the mEDRA plug-in.
 *
 * FEATURE: mEDRA DOI registration and export
 *   AS A    journal manager
 *   I WANT  to be able to register DOIs for issues, articles and
 *           supplementary files with the DOI registration agency mEDRA
 *   SO THAT these objects can be uniquely identified and
 *           discovered through public meta-data searches.
 */


import('tests.functional.plugins.importexport.FunctionalDoiExportTest');
import('plugins.importexport.medra.MedraExportPlugin');

class FunctionalMedraExportTest extends FunctionalDoiExportTest {

	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		$this->pluginId = 'medra';

		$baseUrl = Config::getVar('debug', 'webtest_base_url');
		$this->pages = array(
			'index' => $baseUrl . '/index.php/test/manager/importexport/plugin/MedraExportPlugin',
			'settings' => $baseUrl . '/index.php/test/manager/plugin/importexport/MedraExportPlugin/settings'
		);
		parent::setUp();

		// Store initial plug-in configuration.
		$settingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $settingsDao PluginSettingsDAO */
		$this->initialPluginSettings = array(
			'exportIssuesAs' => $settingsDao->getSetting(1, 'medraexportplugin', 'exportIssuesAs'),
			'publicationCountry' => $settingsDao->getSetting(1, 'medraexportplugin', 'publicationCountry')
		);
	}


	/**
	 * SCENARIO OUTLINE: see FunctionalDoiExportTest::doExportObjectTest().
	 *
	 * EXAMPLES:
	 *   export plug-in   |options            |object type|object id(s)|export format             |sample file(s)
	 *   =================|===================|===========|============|==========================|=====================================
	 *   MedraExportPlugin|exp. issues as work|issue      |1           |O4DOI serial issue as work|serial-issue-as-work.xml
	 */
	public function testExportSerialIssueAsWork() {
		$this->configurePlugin(O4DOI_ISSUE_AS_WORK);
		$this->doExportObjectTest('issue', 1, 'MedraExportPlugin', 'serial-issue-as-work.xml');
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
		$this->configurePlugin(O4DOI_ISSUE_AS_MANIFESTATION);
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
		$this->configurePlugin(O4DOI_ISSUE_AS_WORK);
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
		$this->configurePlugin(O4DOI_ISSUE_AS_MANIFESTATION);
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
		$this->configurePlugin(O4DOI_ISSUE_AS_MANIFESTATION);
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
	 */
	public function testRegisterOrExportSpecificObjects() {
		parent::testRegisterOrExportSpecificObjects(array('issue', 'article', 'galley'));
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testRegisterUnregisteredDois().
	 */
	public function testRegisterUnregisteredDois() {
		parent::testRegisterUnregisteredDois(array('Issue', 'Article', 'Galley'));
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
		try {
			foreach(array('index', 'articles', 'galleys', 'all') as $pageName) {
				$this->open($this->pages[$pageName]);
				$this->assertElementPresent('//a[@href="http://www.medra.org/en/metadata_td.htm"]');
			}
		} catch(Exception $e) {
			throw $this->improveException($e, "$pageName page");
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
				'exportIssuesAs' => O4DOI_ISSUE_AS_WORK,
				'publicationCountry' => 'US'
			),
			array(
				'exportIssuesAs' => O4DOI_ISSUE_AS_MANIFESTATION,
				'publicationCountry' => 'DE'
			)
		);
		$inputTypes = array(
			'exportIssuesAs' => TEST_INPUTTYPE_SELECT,
			'publicationCountry' => TEST_INPUTTYPE_SELECT
		);
		parent::testPluginSettings($tests, $inputTypes);
	}


	/**
	 * SCENARIO: See FunctionalDoiExportTest::testDoiPrefixError().
	 */
	public function testDoiPrefixError() {
		parent::testDoiPrefixError(array('issues', 'articles', 'galleys', 'all'));
	}


	/**
	 * SCENARIO: Tryping to export when no contact is configured.
	 *
	 *   GIVEN I've got no technical contact email configured
	 *    WHEN I try to export
	 *    THEN I'll be notified that I have to configure
	 *         a contact email before I can export to mEDRA.
	 */
	public function testContactNotConfiguredError() {
		$this->logIn();

		// Make sure that no publisher is configured.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		$journal->updateSetting('supportEmail', '');

		// Try to export any object.
		$this->open($this->pages['articles']);
		$this->check('css=input[name="articleId[]"]');
		$this->click('css=input.button.defaultButton');

		// I should now be notified that I have to set a publisher first.
		$this->waitForLocation('exact:'.$this->pages['index']);
		$this->assertText('css=.ui-pnotify-text', 'Please enter a technical support contact');
	}


	/**
	 * SCENARIO OUTLINE: See FunctionalDoiExportTest::testExportObjectsViaCLI().
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
	public function testExportObjectsViaCLI() {
		$examples = array(
			array(O4DOI_ISSUE_AS_WORK, 'issues', '1', 'serial-issue-as-work.xml'),
			array(O4DOI_ISSUE_AS_MANIFESTATION, 'issues', '1', 'serial-issue-as-manifestation.xml'),
			array(O4DOI_ISSUE_AS_WORK, 'articles', '1', 'serial-article-as-work-1.xml'),
			array(O4DOI_ISSUE_AS_MANIFESTATION, 'galleys', '1', 'serial-article-as-manifestation-1.xml'),
			array(O4DOI_ISSUE_AS_MANIFESTATION, 'galleys', '1 2 3', 'serial-article-as-manifestation-2.xml')
		);

		foreach($examples as $example) {
			list($settings, $exportObjectType, $objectIds, $xmlFile) = $example;

			// Configure the issue export type.
			$this->configurePlugin($settings);

			parent::testExportObjectsViaCLI('MedraExportPlugin', $exportObjectType, $objectIds, $xmlFile);
		}
	}


	/**
	 * SCENARIO OUTLINE: Register objects on the command line.
	 *
	 * EXAMPLES:
	 *
	 *   export plug-in   |export object type|object ids
	 *   =================|==================|==========
	 *   MedraExportPlugin|issues            |1
	 *   MedraExportPlugin|articles          |1
	 *   MedraExportPlugin|galleys           |1 2 3
	 */
	public function testRegisterObjectViaCLI() {
		parent::testRegisterObjectViaCLI();
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


	/**
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		// Restoring the tables alone will not update the settings cache
		// so we have to do this manually.
		assert(count($this->initialPluginSettings) == 2);
		$this->configurePlugin(
			$this->initialPluginSettings['exportIssuesAs'],
			$this->initialPluginSettings['publicationCountry']
		);

		// Restore tables, etc.
		parent::tearDown();
	}


	/**
	 * @see FunctionalDoiExportTest::cleanXml()
	 */
	protected function cleanXml($xml) {
		// Fix URLs.
		$xml = String::regexp_replace('#http://[^\s]+/index.php/(test|index)#', 'http://some-domain/index.php/test', $xml);

		// Fix sent date.
		$xml = String::regexp_replace('/<SentDate>[0-9]{12}<\/SentDate>/', '<SentDate>201111082218</SentDate>', $xml);

		// Fix version.
		$xml = String::regexp_replace('/(<MessageNote>[^<]*)([0-9]\.){4}(<\/MessageNote>)/', '\1x.x.x.x.\3', $xml);

		return parent::cleanXml($xml);
	}


	//
	// Private helper methods
	//
	/**
	 * Alter the plugin-configuration directly in the database.
	 *
	 * NB: We do not use Selenium here to improve performance.
	 *
	 * @param $exportType integer
	 * @param $publicationCountry string
	 */
	private function configurePlugin($exportType = O4DOI_ISSUE_AS_WORK, $publicationCountry = 'US') {
		$settingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $settingsDao PluginSettingsDAO */
		$settingsDao->updateSetting(1, 'medraexportplugin', 'exportIssuesAs', $exportType);
		$settingsDao->updateSetting(1, 'medraexportplugin', 'publicationCountry', $publicationCountry);
	}
}
?>