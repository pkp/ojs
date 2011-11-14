<?php

/**
 * @file tests/functional/plugins/importexport/FunctionalMedraExportTest.inc.php
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


import('lib.pkp.tests.functional.plugins.importexport.FunctionalImportExportBaseTestCase');
import('plugins.importexport.medra.MedraExportPlugin');

class FunctionalMedraExportTest extends FunctionalImportExportBaseTestCase {
	private
		$testDataPath = './tests/functional/plugins/importexport/medra/',
		$pages, $initialPluginSettings, $initialJournalSettings;

	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'plugin_settings', 'issue_settings', 'article_settings',
			'article_galley_settings', 'article_supp_file_settings',
			'journal_settings', 'notifications', 'notification_settings'
		);
	}

	protected function setUp() {
		parent::setUp();
		$indexPage = $this->baseUrl . '/index.php/test/manager/importexport/plugin/MedraExportPlugin';
		$this->pages = array(
			'index' => $indexPage,
			'all' => $indexPage . '/all',
			'issues' => $indexPage . '/issues',
			'articles' => $indexPage . '/articles',
			'galleys' => $indexPage . '/galleys',
			'settings' => $this->baseUrl . '/index.php/test/manager/plugin/importexport/MedraExportPlugin/settings'
		);
		// Store initial plug-in configuration.
		$settingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $settingsDao PluginSettingsDAO */
		$this->initialPluginSettings = array(
			'exportIssuesAs' => $settingsDao->getSetting(1, 'medraexportplugin', 'exportIssuesAs'),
			'publicationCountry' => $settingsDao->getSetting(1, 'medraexportplugin', 'publicationCountry')
		);
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		$this->initialJournalSettings = array(
			'doiPrefix', $journal->getSetting('doiPrefix'),
			'doiSuffix' => $journal->getSetting('doiSuffix'),
			'publisherInstitution' => $journal->getSetting('publisherInstitution'),
			'supportEmail' => $journal->getSetting('supportEmail')
		);
	}


	/**
	 * SCENARIO: Export serial issues as work.
	 *
	 *   GIVEN I navigate to the mEDRA plug-in's settings page
	 *     AND I activate the default "export issues as work" option
	 *     AND I navigate to any issue in the export plug-in
	 *    WHEN I click the "Export" button
	 *    THEN the issue will be exported as a O4DOI serial issue
	 *         as work similarly to the example given in
	 *         'serial-issue-as-work.xml'.
	 */
	function testExportSerialIssuesAsWork() {
		$this->configurePlugin();
		// Export single issue.
		$xml = $this->getXmlOnExport('MedraExportPlugin/exportIssue/1');
		$this->assertXml('serial-issue-as-work.xml', $xml);
		// Export as multi-issue form post.
		$xml = $this->getXmlOnExport('MedraExportPlugin/exportIssues', array('issueId[]' => 1));
		$this->assertXml('serial-issue-as-work.xml', $xml);
	}


	/**
	 * SCENARIO: Export serial issues as product/manifestation.
	 *
	 *   GIVEN I navigate to the mEDRA plug-in's settings page
	 *     AND I activate the "export issues as manifestation" option
	 *     AND I navigate to any issue in the export plug-in
	 *    WHEN I click the "Export" button
	 *    THEN the issue will be exported as a O4DOI serial issue
	 *         as manifestation similarly to the example given in
	 *         'serial-issue-as-manifestation.xml'.
	 */
	function testExportSerialIssuesAsManifestation() {
		$this->configurePlugin(O4DOI_ISSUE_AS_MANIFESTATION);
		// Export single issue.
		$xml = $this->getXmlOnExport('MedraExportPlugin/exportIssue/1');
		$this->assertXml('serial-issue-as-manifestation.xml', $xml);
		// Export as multi-issue form post.
		$xml = $this->getXmlOnExport('MedraExportPlugin/exportIssues', array('issueId[]' => 1));
		$this->assertXml('serial-issue-as-manifestation.xml', $xml);
	}


	/**
	 * SCENARIO: Export serial article as work.
	 *
	 *   GIVEN I assign a DOI to an OJS article
	 *     AND I navigate to that article in the mEDRA export plug-in
	 *    WHEN I click the "Export" button
	 *    THEN the article will be exported as a O4DOI serial article
	 *         as work similarly to the example given in
	 *         'serial-article-as-work.xml'.
	 */
	function testExportSerialArticleAsWork() {
		$this->configurePlugin();
		// Export single article.
		$xml = $this->getXmlOnExport('MedraExportPlugin/exportArticle/1');
		$this->assertXml('serial-article-as-work.xml', $xml);
		// Export as multi-article form post.
		$xml = $this->getXmlOnExport('MedraExportPlugin/exportArticles', array('articleId[]' => 1));
		$this->assertXml('serial-article-as-work.xml', $xml);
	}


	/**
	 * SCENARIO: Export serial article as product/manifestation.
	 *
	 *   GIVEN I assign a DOI to an OJS article galley
	 *     AND I navigate to the corresponding article in the mEDRA
	 *         export plug-in
	 *    WHEN I click the "Export" button
	 *    THEN all galleys of that article will be exported as O4DOI
	 *         serial articles as manifestations similarly to the example
	 *         given in 'serial-article-as-manifestation.xml'.
	 */
	function testExportSerialArticleAsManifestation() {
		$this->configurePlugin(O4DOI_ISSUE_AS_MANIFESTATION);
		// Export single galley.
		$xml = $this->getXmlOnExport('MedraExportPlugin/exportGalley/1');
		$this->assertXml('serial-article-as-manifestation-1.xml', $xml);
		// Export as multi-galley form post.
		$xml = $this->getXmlOnExport('MedraExportPlugin/exportGalleys', 'galleyId[]=1&galleyId[]=2&galleyId[]=3');
		$this->assertXml('serial-article-as-manifestation-2.xml', $xml);
	}


	/**
	 * SCENARIO OUTLINE: Register/Export specific objects.
	 *
	 *   GIVEN I assign a DOI to an OJS {object} that has not been
	 *         registered with mEDRA before
	 *     AND I navigate to the corresponding {export page} in the
	 *         mEDRA export plug-in
	 *    WHEN I click the "{register or export}" button
	 *    THEN the DOIs of the selected objects will be
	 *         registered/exported as a new object.
	 *
	 * EXAMPLES:
	 *   object |export page                                               |register or export
	 *   =======|==========================================================|==================
	 *   Issue  |.../manager/importexport/plugin/MedraExportPlugin/issues  |Register
	 *   Issue  |.../manager/importexport/plugin/MedraExportPlugin/issues  |Export
	 *   Article|.../manager/importexport/plugin/MedraExportPlugin/articles|Register
	 *   Article|.../manager/importexport/plugin/MedraExportPlugin/articles|Export
	 *   Galley |.../manager/importexport/plugin/MedraExportPlugin/galleys |Register
	 *   Galley |.../manager/importexport/plugin/MedraExportPlugin/galleys |Export
	 */
	public function testRegisterOrExportSpecificObjects() {
		$this->logIn();
		foreach(array('Issue', 'Article', 'Galley') as $objectType) {
			try {
				// Navigate to the object's export page.
				$page = strtolower($objectType).'s';
				$this->open($this->pages[$page]);
				// Check whether clicking the export button in the table exports
				// a single element. We do not actually export as this is already
				// being tested elsewhere.
				$this->assertText('css=a.action[href="'.$this->pages['index'].'/export'.$objectType.'/1"]', 'Export');
				// Check whether clicking the register button in the table registers
				// a single element.
				// FIXME: Need to implement registration to test this (see AP9).

				// Check whether submitting the form via export button exports
				// all selected elements. We do not actually export as this is
				// already being tested elsewhere.
				$this->assertElementPresent('css=form[name="'.strtolower($objectType).'s"][action="'.$this->pages['index'].'/export'.$objectType.'s"]');
				$this->assertElementPresent('css=input[name="'.strtolower($objectType).'Id[]"]');
				$this->assertElementPresent('css=input.button[name="export"]');
				// Check whether submitting the form via register button registers
				// all selected elements.
				// FIXME: Need to implement registration to test this (see AP9).
			} catch(Exception $e) {
				throw $this->improveException($e, strtolower($objectType));
			}
		}

		self::markTestIncomplete('Need to implement registration to complete this test (see AP9).');
	}

	/**
	 * SCENARIO: Register/Export unregistered DOIs - part 1
	 *
	 *   GIVEN I navigate to the mEDRA export plug-in home page
	 *    WHEN I click the "register all unregistered DOIs" button
	 *    THEN a list of all unregistered objects will be compiled and
	 *         displayed for confirmation
	 *     AND the user will be presented with an "Export" and a "Register" button.
	 *
	 * SCENARIO: Register/Export unregistered DOIs - part 2
	 *
	 *   GIVEN I am presented with a list of unregistered objects after
	 *         having clicked the "register all unregistered DOIs" button
	 *    WHEN I click the "Register" button
	 *    THEN all DOIs of issues, articles and galleys on that list
	 *         will be automatically registered with mEDRA as new objects.
	 *     AND a notification will inform the user about the successful
	 *         registration.
	 *
	 * SCENARIO: Register/Export unregistered DOIs - part 3
	 *
	 *   GIVEN I am presented with a list of unregistered objects after
	 *         having clicked the "register all unregistered DOIs" button
	 *    WHEN I click the "Export" button
	 *    THEN all DOIs of issues, articles and galleys on that list
	 *         will be exported to files in the files/temp/medra directory.
	 *     AND a notification will inform the user about the successful
	 *         export.
	 */
	public function testRegisterOrExportUnregisteredDois() {
		$this->logIn();

		// Part 1:
		// Navigate to the mEDRA export plug-in home page.
		$this->open($this->pages['index']);
		// Click the "register all unregistered DOIs" button.
		$this->clickAndWait('//a[@href="'.$this->pages['all'].'"]');
		// Check whether the list of unregistered objects is there including
		// at least an issue, an article and several galleys.
		$objectTypeColumn = '//div[@id="allUnregistered"]//tr/td[2]';
		$this->assertElementPresent($objectTypeColumn . '[text()="Issue"]');
		$this->assertElementPresent($objectTypeColumn . '[text()="Article"]');
		$this->assertElementPresent($objectTypeColumn . '[text()="Galley"]');

		// Part 2:
		// FIXME: Need to implement registration to test this (see AP9).

		// Part 3:
		// NB: We risk a race condition here. Fix it if it becomes a problem.
		$timestamp = date('Ymd-Hi');
		// Click the "Export" button.
		$this->clickAndWait('//input[@type="submit" and @value="Export"]');
		// Check whether the export has generated files in the files/temp/medra directory.
		$medraTempDirectory = Config::getVar('files', 'files_dir') . '/medra/';
		foreach(array('issues', 'articles', 'galleys') as $fileType) {
			try {
				$this->assertFileExists($medraTempDirectory . "$timestamp-$fileType.xml");
			} catch(Exception $e) {
				throw $this->improveException($e, $fileType);
			}
		}
		// Check whether a notification informing about the successful export to the
		// medra export path is present.
		$this->waitForLocation('exact:'.$this->pages['all']);
		$this->assertText('css=.ui-pnotify-text', 'files/medra');

		self::markTestIncomplete('Need to implement registration to complete this test (see AP9).');
	}


	/**
	 * SCENARIO: Update button.
	 *
	 *    WHEN I navigate to an object in the mEDRA export plug-in
	 *         that has already been transmitted to mEDRA
	 *    THEN there will be an "Update" rather than a "Register" button
	 *
	 * SCENARIO: Update specific issues/articles (DOI unchanged).
	 *
	 *   GIVEN I navigate to an object in the mEDRA export plug-in
	 *         that has already been transmitted to mEDRA
	 *     AND the DOI has not changed
	 *    WHEN I click the "Update" button
	 *    THEN the meta-data of the selected object will be automatically
	 *         registered with mEDRA as an updated version of a previously
	 *         transmitted object.
	 *
	 * SCENARIO: Update specific issues/articles (DOI changed).
	 *
	 *   GIVEN I navigate to an object in the mEDRA export plug-in
	 *         that has already been transmitted to mEDRA
	 *     AND the DOI for the object has changed since its first registration
	 *    WHEN I click the "Update" button
	 *    THEN the new DOI will be automatically registered with mEDRA
	 *         as a new object with a relation to the object identified
	 *         by the previous DOI.
	 */
	function testUpdate() {
		self::markTestIncomplete('Need to implement registration to test this (see AP9).');
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
	 * SCENARIO OUTLINE: Objects without DOIs cannot be selected for export.
	 *
	 *   GIVEN I configure custom DOIs
	 *     AND I've not yet assigned a DOI
	 *    THEN the {object} will not appear on the corresponding {export page}.
	 *
	 * EXAMPLES:
	 *   object |export page
	 *   =======|==========================================================
	 *   Issue  |.../manager/importexport/plugin/MedraExportPlugin/issues
	 *   Article|.../manager/importexport/plugin/MedraExportPlugin/articles
	 *   Galley |.../manager/importexport/plugin/MedraExportPlugin/galleys
	 */
	public function testObjectsWithoutDOICannotBeSelectedForExport() {
		$this->logIn();

		// Configure custom DOIs.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		$journal->updateSetting('doiSuffix', 'customId');

		// Delete all existing DOIs.
		$scream = ini_get('xdebug.scream');
		ini_set('xdebug.scream', false);
		$journalDao->deleteAllPubIds(1, 'doi');
		ini_set('xdebug.scream', $scream);

		// Make sure that no custom suffix is saved for our test objects.
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issue = $issueDao->getIssueById(1, 1);
		$issue->setData('doiSuffix', '');
		$issueDao->updateIssue($issue);
		$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$article = $articleDao->getArticle(1, 1);
		$article->setData('doiSuffix', '');
		$articleDao->updateArticle($article); // Do not use PublishedArticleDAO::updatePublishedArticle() for this, otherwise the ADODB cache flush there may cause a permission error.
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$galleys = $galleyDao->getGalleysByArticle(1, 1);
		foreach($galleys as $galley) {
			$galley->setData('doiSuffix', '');
			$galleyDao->updateGalley($galley);
		}

		foreach(array('issues', 'articles', 'galleys', 'all') as $exportPage) {
			try {
				// Check that the object does not appear on the export page.
				$this->open($this->pages[$exportPage]);

				// Check that we get an empty list. This implicitly tests
				// that the "no data" marker is correctly displayed.
				$this->assertElementPresent('css=td.nodata');
			} catch(Exception $e) {
				throw $this->improveException($e, "$exportPage page");
			}
		}
	}


	/**
	 * SCENARIO: Change plug-in settings.
	 *
	 *   GIVEN I'm on the settings page
	 *    WHEN I change the settings
	 *     AND I click the "Save" button
	 *    THEN the settings will be stored to the database.
	 *
	 * SCENARIO: Display changed plug-in settings.
	 *
	 *    WHEN I navigate to the settings page
	 *    THEN I'll see the currently selected settings.
	 */
	public function testPluginSettings() {
		$this->logIn();

		$tests = array(
			array('exportIssuesAs' => O4DOI_ISSUE_AS_WORK, 'publicationCountry' => 'US'),
			array('exportIssuesAs' => O4DOI_ISSUE_AS_MANIFESTATION, 'publicationCountry' => 'DE')
		);

		foreach($tests as $test) {
			// Change settings.
			$this->open($this->pages['settings']);
			$this->select('exportIssuesAs', 'value='.$test['exportIssuesAs']);
			$this->select('publicationCountry', 'value='.$test['publicationCountry']);
			$this->clickAndWait('css=input.button.defaultButton');

			// Check whether settings are correctly displayed. This implicitly checks
			// that the settings have been stored to the database.
			$this->open($this->pages['settings']);
			$this->assertIsSelected('exportIssuesAs', $test['exportIssuesAs']);
			$this->assertIsSelected('publicationCountry', $test['publicationCountry']);
		}
	}


	/**
	 * SCENARIO: Disable plug-in when no DOI prefix is configured.
	 *
	 *   GIVEN I have no DOI prefix configured
	 *    WHEN I navigate to the plug-in home page
	 *    THEN I'll see an error message
	 *     AND there'll be no links to export pages.
	 */
	public function testDoiPrefixError() {
		$this->logIn();

		// Make sure that no DOI prefix is configured.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		$journal->updateSetting('doiPrefix', '');

		// Navigate to the plug-in home page.
		$this->open($this->pages['index']);

		// Check the error message.
		$this->assertText('content', 'A valid DOI prefix must be specified');

		// Make sure that no links are present.
		foreach (array('issues', 'articles', 'galleys', 'all') as $page) {
			try {
				$this->assertElementNotPresent('css=a[href="'.$this->pages[$page].'"]');
			} catch(Exception $e) {
				throw $this->improveException($e, "$page page");
			}
		}
	}


	/**
	 * SCENARIO: Tryping to export when no publisher configured.
	 *
	 *   GIVEN I've got no publisher configured
	 *    WHEN I try to export
	 *    THEN I'll be notified that I have to configure
	 *         a publisher before I can export to mEDRA.
	 */
	public function testPublisherNotConfiguredError() {
		$this->logIn();

		// Make sure that no publisher is configured.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		$journal->updateSetting('publisherInstitution', '');

		// Try to export any object.
		$this->open($this->pages['issues']);
		$this->check('css=input[name="issueId[]"]');
		$this->click('css=input.button.defaultButton');

		// I should now be notified that I have to set a publisher first.
		$this->waitForLocation('exact:'.$this->pages['index']);
		$this->assertText('css=.ui-pnotify-text', 'Please enter a publisher');
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
	 * SCENARIO OUTLINE: Export objects on the command line.
	 *
	 *  GIVEN I am in the applications base directory
	 *    AND I configured the mEDRA plug-in to export issues
	 *        as {issue export type}
	 *   WHEN I enter the following command on the command line:
	 *        > php tools/importExport.php MedraExportPlugin \
	 *        > export files/medra/test.xml {journalPath} \
	 *        > {exportObjectType} {objectIds}
	 *   THEN files/medra/test.xml will contain the XML specified
	 *        in {XML example file}.
	 *
	 * EXAMPLES:
	 *
	 *   issue export type|journalPath|exportObjectType|objectIds|XML example file
	 *   =================|===========|================|=========|=====================================
	 *   work             |test       |issues          |1        |serial-issue-as-work.xml
	 *   manifestation    |test       |issues          |1        |serial-issue-as-manifestation.xml
	 *   work             |test       |articles        |1        |serial-article-as-work.xml
	 *   manifestation    |test       |galleys         |1        |serial-article-as-manifestation-1.xml
	 *   manifestation    |test       |galleys         |1 2 3    |serial-article-as-manifestation-2.xml
	 */
	public function testExportObjectsViaCLI() {
		$examples = array(
			array(O4DOI_ISSUE_AS_WORK, 'issues', '1', 'serial-issue-as-work.xml'),
			array(O4DOI_ISSUE_AS_MANIFESTATION, 'issues', '1', 'serial-issue-as-manifestation.xml'),
			array(O4DOI_ISSUE_AS_WORK, 'articles', '1', 'serial-article-as-work.xml'),
			array(O4DOI_ISSUE_AS_MANIFESTATION, 'galleys', '1', 'serial-article-as-manifestation-1.xml'),
			array(O4DOI_ISSUE_AS_MANIFESTATION, 'galleys', '1 2 3', 'serial-article-as-manifestation-2.xml')
		);

		$outputFile = 'files/medra/test.xml';
		$journalPath = 'test';

		// Fake router.
		$this->fakeRouter();

		foreach($examples as $example) {
			list($exportType, $exportObjectType, $objectIds, $expectedXml) = $example;

			try {
				// Configure the issue export type.
				$this->configurePlugin($exportType);

				// Construct the command line arguments.
				$args = array('export', $outputFile, $journalPath, $exportObjectType);
				$args = array_merge($args, explode(' ', $objectIds));

				// Call the CLI.
				$result = $this->executeCLI('MedraExportPlugin', $args);

				// Check that we didn't get any error messages.
				$this->assertEquals('', $result);

				// Check the XML.
				$exportedXml = file_get_contents($outputFile);
				$this->assertXml($expectedXml, $this->cleanXml($exportedXml));
			} catch(Exception $e) {
				$commandLine = "'php tools /importExport.php MedraExportPlugin "
					. "files/medra/test.xml test $exportObjectType "
					. "$objectIds'";
				throw $this->improveException($e, $commandLine);
			}
		}
	}


	/**
	 * SCENARIO OUTLINE: Register objects on the command line.
	 *
	 *   WHEN I enter the following command on the command line:
	 *        > php tools/importExport.php MedraExportPlugin \
	 *        > register {journalPath} {exportObjectType} {objectIds}
	 *   THEN the specified objects will be automatically registered
	 *        with mEDRA.
	 *
	 * EXAMPLES:
	 *
	 *   journalPath|exportObjectType|objectIds
	 *   ===========|================|=========
	 *   test       |issues          |1
	 *   test       |articles        |1
	 *   test       |galleys         |1 2 3
	 */
	function testRegisterObjectViaCLI() {
		self::markTestIncomplete('Need to implement registration to test this (see AP9).');
	}

	/**
	 * SCENARIO: unsupported object type (CLI error)
	 *
	 *    WHEN I enter an unsupported export object type on
	 *         the CLI command line
	 *    THEN I'll receive a CLI error "The object type ...
	 *         cannot be exported."
	 */
	function testUnsupportedObjectTypeCliError() {
		$this->fakeRouter();

		// Construct the command line arguments.
		$unsupportedObjectType = 'suppFile';
		$args = array('export', 'files/medra/test.xml', 'test', $unsupportedObjectType, '1');

		// Call the CLI.
		// NB: We check the translation key rather than the string. This is a limitation
		// of the test environment, not of the implementation.
		$result = $this->executeCLI('MedraExportPlugin', $args);
		$this->assertRegExp('/##plugins.importexport.medra.export.error.unknownObjectType##/', $result);
	}


	/**
	 * SCENARIO: non-existent journal (CLI error)
	 *
	 *    WHEN I enter a non-existent journal path on
	 *         the CLI command line
	 *    THEN I'll receive a CLI error "No journal matched
	 *         the specified journal path: ...."
	 */
	function testNonExistentJournalPathCliError() {
		$this->fakeRouter();

		// Construct the command line arguments.
		$args = array('export', 'files/medra/test.xml', 'non-existent-journal', 'issues', '1');

		// Call the CLI.
		// NB: We check the translation key rather than the string. This is a limitation
		// of the test environment, not of the implementation.
		$result = $this->executeCLI('MedraExportPlugin', $args);
		$this->assertRegExp('/##plugins.importexport.medra.export.error.unknownJournal##/', $result);
	}


	/**
	 * SCENARIO: output file not writable (CLI error)
	 *
	 *    WHEN I enter an output file that is not writable
	 *    THEN I'll receive a CLI error "The output file ...
	 *         is not writable."
	 */
	function testOutputFileNotWritableCliError() {
		$this->fakeRouter();

		// Construct the command line arguments.
		$args = array('export', 'files/some-non-existent-path/test.xml', 'test', 'issues', '1');

		// Call the CLI.
		// NB: We check the translation key rather than the string. This is a limitation
		// of the test environment, not of the implementation.
		$result = $this->executeCLI('MedraExportPlugin', $args);
		$this->assertRegExp('/##plugins.importexport.medra.export.error.outputFileNotWritable##/', $result);
	}


	/**
	 * SCENARIO: non-existent object id (CLI error)
	 *
	 *    WHEN I enter a non-existent journal path on
	 *         the CLI command line
	 *    THEN I'll receive a CLI error "No journal matched
	 *         the specified journal path: ...."
	 */
	function testNonExistentObjectIdCliError() {
		$this->fakeRouter();

		// Construct the command line arguments.
		foreach(array('issue', 'article', 'galley') as $objectType) {
			$args = array('export', 'files/medra/test.xml', 'test', "${objectType}s", '999');

			// Call the CLI.
			// NB: We check the translation key rather than the string. This is a limitation
			// of the test environment, not of the implementation.
			$result = $this->executeCLI('MedraExportPlugin', $args);
			$this->assertRegExp("/##plugins.importexport.medra.export.error.${objectType}NotFound##/", $result);
		}
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
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		foreach($this->initialJournalSettings as $settingName => $settingValue) {
			$journal->updateSetting($settingName, $settingValue);
		}

		// Restore tables, etc.
		parent::tearDown();
	}

	/**
	 * @see FunctionalImportExportBaseTestCase::getXmlOnExport()
	 */
	protected function getXmlOnExport($pluginUrl, $postParams = array()) {
		$xml = parent::getXmlOnExport($pluginUrl, $postParams);
		$xml = $this->cleanXml($xml);
		return $xml;
	}

	/**
	 * Normalize the XML.
	 * @param $xml string
	 * @return string
	 */
	private function cleanXml($xml) {
		// Fix URLs.
		$xml = String::regexp_replace('#http://[^\s]+/index.php/(test|index)#', 'http://some-domain/index.php/test', $xml);

		// Fix sent date.
		$xml = String::regexp_replace('/<SentDate>[0-9]{12}<\/SentDate>/', '<SentDate>201111082218</SentDate>', $xml);

		// Fix version.
		$xml = String::regexp_replace('/(<MessageNote>[^<]*)([0-9]\.){4}(<\/MessageNote>)/', '\1x.x.x.x.\3', $xml);

		// Fix missing translations. This is a problem of the test environment not of the implementation.
		$xml = str_replace('##issue.vol##', 'Vol', $xml);
		$xml = str_replace('##issue.no##', 'No', $xml);

		return $xml;
	}

	/**
	 * Alter the plugin-configuration directly in the database.
	 *
	 * NB: We do not use Selenium here to improve performance.
	 *
	 * @param $exportType
	 */
	private function configurePlugin($exportType = O4DOI_ISSUE_AS_WORK, $publicationCountry = 'US') {
		$settingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $settingsDao PluginSettingsDAO */
		$settingsDao->updateSetting(1, 'medraexportplugin', 'exportIssuesAs', $exportType);
		$settingsDao->updateSetting(1, 'medraexportplugin', 'publicationCountry', $publicationCountry);
	}

	/**
	 * Test XML against file.
	 * @param $file string
	 * @param $xml string
	 */
	private function assertXml($file, $xml) {
		$this->assertXmlStringEqualsXmlFile($this->testDataPath.$file, $xml);
	}

	/**
	 * Fake a router for CLI tests.
	 */
	private function fakeRouter() {
		$application =& PKPApplication::getApplication();
		$request =& $application->getRequest();
		import('classes.core.PageRouter');
		$router = new PageRouter();
		$router->setApplication($application);
		$request->setRouter($router);
	}
}
?>