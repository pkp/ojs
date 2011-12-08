<?php

/**
 * @file tests/functional/plugins/importexport/FunctionalDoiExportTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalDoiExportTest
 * @ingroup tests_functional_plugins_importexport
 *
 * @brief Base class to test DOI export plug-ins.
 */


import('lib.pkp.tests.functional.plugins.importexport.FunctionalImportExportBaseTestCase');

// Input types (for settings tests).
define('TEST_INPUTTYPE_SELECT', 0x01);
define('TEST_INPUTTYPE_TEXT', 0x02);
define('TEST_INPUTTYPE_EMAIL', 0x03);

class FunctionalDoiExportTest extends FunctionalImportExportBaseTestCase {
	protected
		/**
		 * This variable will be read by cleanXml(), see there.
		 * @var boolean
		 */
		$expectJournalNameAsPublisher = false,

		/** Other internal test parameters */
		$pluginId, $pages, $defaultPluginSettings, $initialPluginSettings, $initialJournalSettings;


	//
	// Implement template methods from WebTestCase
	//
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

	//
	// Implement template methods from PHPUnit_Framework_TestCase
	//
	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$indexPage = $this->pages['index'];
		$this->pages += array(
			'all' => $indexPage . '/all',
			'issues' => $indexPage . '/issues',
			'articles' => $indexPage . '/articles',
			'galleys' => $indexPage . '/galleys',
		);

		// Store initial journal configuration.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		$this->initialJournalSettings = array(
			'doiPrefix', $journal->getSetting('doiPrefix'),
			'doiSuffix' => $journal->getSetting('doiSuffix'),
			'publisherInstitution' => $journal->getSetting('publisherInstitution'),
			'supportEmail' => $journal->getSetting('supportEmail')
		);

		// Store initial plug-in configuration.
		$settingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $settingsDao PluginSettingsDAO */
		$this->initialPluginSettings = array();
		foreach ($this->defaultPluginSettings as $settingName => $settingValue) {
			$this->initialPluginSettings[] = $settingsDao->getSetting(1, $this->pluginId . 'exportplugin', $settingName);
		}
	}

	/**
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		// Restoring the tables alone will not update the settings cache
		// so we have to do this manually.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		foreach($this->initialJournalSettings as $settingName => $settingValue) {
			$journal->updateSetting($settingName, $settingValue);
		}

		// Restore initial plug-in configuration.
		$settingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $settingsDao PluginSettingsDAO */
		foreach ($this->initialPluginSettings as $settingName => $settingValue) {
			$settingsDao->updateSetting(1, $this->pluginId . 'exportplugin', $settingName, $settingValue);
		}

		// Restore tables, etc.
		parent::tearDown();
	}


	/**
	 * SCENARIO: missing publisher will be replaced with journal name
	 *
	 *   GIVEN I did not configure a publisher in journal setup step 1
	 *    WHEN I export an object
	 *    THEN the O4DOI publisher field will be set to the journal name.
	 */
	protected function testExpectJournalNameAsPublisher() {
		// Test whether a missing publisher is being replaced
		// with the journal name.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		$journal->updateSetting('publisherInstitution', '');
		$this->expectJournalNameAsPublisher = true;
	}


	/**
	 * SCENARIO OUTLINE: Export OJS object(s).
	 *
	 *   GIVEN I navigate to the {export plug-in}'s settings page
	 *     AND I configure {options} (if any)
	 *     AND I assign a DOI to one or more OJS objects of type
	 *         {object type} with {object id(s)}
	 *     AND I navigate to that/these object(s) in the {export plug-in}
	 *    WHEN I click the "Export" button
	 *    THEN the object(s) will be exported as a {export format}
	 *         similarly to the example(s) given in {sample file(s)}.
	 *
	 * EXAMPLES: See sub-classes.
	 */
	protected function doExportObjectTest($objectType, $objectIds, $exportPlugin, $sampleFiles, $singleTest = false) {
		if (is_scalar($objectIds)) $objectIds = array($objectIds);

		if (!$singleTest || ($singleTest && count($objectIds) == 1)) {
			// Export single object.
			$sampleFile = (is_array($sampleFiles) ? $sampleFiles[0] : $sampleFiles);
			$xml = $this->getXmlOnExport($exportPlugin . '/export'. ucfirst($objectType) . '/' . $objectIds[0]);
			$this->assertXml($sampleFile, $xml);
		}

		if (!$singleTest || ($singleTest && count($objectIds) > 1)) {
			// Export via multi-object form request.
			$objectParams = '';
			foreach($objectIds as $objectId) {
				if (!empty($objectParams)) $objectParams .= '&';
				$objectParams .= "${objectType}Id[]=$objectId";
			}
			$xml = $this->getXmlOnExport($exportPlugin . '/export' . ucfirst($objectType) . 's', $objectParams);
			$this->assertXml($sampleFiles, $xml);
		}
	}


	/**
	 * SCENARIO OUTLINE: Export unregistered DOIs
	 *
	 *   GIVEN I assign DOIs to several OJS objects
	 *     BUT I have not yet registered these objects
	 *     AND I navigate to the "export all unregistered objects" page
	 *         in the {export plug-in}
	 *     AND I select {objects} by activating the checkbox next
	 *         to the object
	 *    WHEN I click the "Export" button
	 *    THEN all DOIs of selected unregistered OJS objects will be
	 *         exported into {XML files} in the given order which will
	 *         be streamed to the browser as a compressed tar file.
	 *
	 * EXAMPLES: See sub-classes.
	 */
	protected function testExportUnregisteredDois($exportPlugin, $objects, $xmlFiles) {
		// Export a selection of unregistered objects.
		// NB: getXmlOnExport() automatically unpacks the received tar archive.
		$objectParams = '';
		foreach($objects as $objectType => $objectIds) {
			if (is_scalar($objectIds)) $objectIds = array($objectIds);
			foreach($objectIds as $objectId) {
				if (!empty($objectParams)) $objectParams .= '&';
				$objectParams .= "${objectType}Id[]=$objectId";
			}
		}
		$xml = $this->getXmlOnExport($exportPlugin . '/exportAll', $objectParams);
		$this->assertXml($xmlFiles, $xml);
	}


	/**
	 * SCENARIO OUTLINE: Register/Export specific objects.
	 *
	 *   GIVEN I assign a DOI to an OJS {object} that has not been
	 *         registered with the DOI export plug-in before
	 *     AND I navigate to the corresponding {export page} in the
	 *         DOI export plug-in
	 *    WHEN I click the "{register or export}" button
	 *    THEN the DOIs of the selected objects will be
	 *         registered/exported as a new object.
	 *
	 * EXAMPLES: See sub-classes.
	 */
	protected function testRegisterOrExportSpecificObjects($objectTypes) {
		$this->logIn();
		foreach($objectTypes as $objectType) {
			try {
				// Navigate to the object's export page.
				$page = $objectType.'s';
				$this->open($this->pages[$page]);
				// Check whether clicking the export button in the table exports
				// a single element. We do not actually export as this is already
				// being tested elsewhere.
				$this->assertText('css=a.action[href="'.$this->pages['index'].'/export'.ucfirst($objectType).'/1"]', 'Export');

				// Check whether clicking the register button in the table registers
				// a single element.
				// FIXME: Need to implement registration to test this (see AP9).

				// Check whether submitting the form via export button exports
				// all selected elements. We do not actually export as this is
				// already being tested elsewhere.
				$this->assertElementPresent('css=form[name="'.$objectType.'s"][action="'.$this->pages['index'].'/export'.ucfirst($objectType).'s"]');
				$this->assertElementPresent('css=input[name="'.$objectType.'Id[]"]');
				$this->assertElementPresent('css=input.button[name="export"]');

				// Check whether submitting the form via register button registers
				// all selected elements.
				// FIXME: Need to implement registration to test this (see AP9).
			} catch(Exception $e) {
				throw $this->improveException($e, $objectType);
			}
		}

		self::markTestIncomplete('Need to implement registration to complete this test (see AP9).');
	}


	/**
	 * SCENARIO: Register unregistered DOIs - part 1
	 *
	 *   GIVEN I navigate to the DOI export plug-in home page
	 *    WHEN I click the "register all unregistered DOIs" button
	 *    THEN a list of all unregistered objects will be compiled and
	 *         displayed for confirmation
	 *     AND the user will be presented with an "Export" and a "Register" button.
	 *
	 * SCENARIO: Register unregistered DOIs - part 2
	 *
	 *   GIVEN I am presented with a list of unregistered objects after
	 *         having clicked the "register all unregistered DOIs" button
	 *    WHEN I click the "Register" button
	 *    THEN all DOIs of issues, articles and galleys on that list
	 *         will be automatically registered with the DOI agency as new objects.
	 *     AND a notification will inform the user about the successful
	 *         registration.
	 */
	protected function testRegisterUnregisteredDois($expectedObjectCaptions) {
		$this->logIn();

		// Part 1:
		// Navigate to the mEDRA export plug-in home page.
		$this->open($this->pages['index']);
		// Click the "register all unregistered DOIs" button.
		$this->clickAndWait('//a[@href="'.$this->pages['all'].'"]');
		// Check whether the list of unregistered objects is there including
		// at least an issue, an article and several galleys.
		$objectTypeColumn = '//div[@id="allUnregistered"]//tr/td[2]';
		foreach($expectedObjectCaptions as $expectedObjectCaption) {
			$this->assertElementPresent($objectTypeColumn . '[text()="' . $expectedObjectCaption . '"]');
		}

		// Part 2:
		self::markTestIncomplete('Need to implement registration to complete this test (see AP9).');
	}


	/**
	 * SCENARIO: Update button.
	 *
	 *    WHEN I navigate to an object in the DOI export plug-in
	 *         that has already been transmitted to the DOI agency
	 *    THEN there will be an "Update" rather than a "Register" button
	 *
	 * SCENARIO: Update specific issues/articles (DOI unchanged).
	 *
	 *   GIVEN I navigate to an object in the DOI export plug-in
	 *         that has already been transmitted to the DOI agency
	 *     AND the DOI has not changed
	 *    WHEN I click the "Update" button
	 *    THEN the meta-data of the selected object will be automatically
	 *         registered with the DOI agency as an updated version of a
	 *         previously transmitted object.
	 *
	 * SCENARIO: Update specific issues/articles (DOI changed).
	 *
	 *   GIVEN I navigate to an object in the DOI export plug-in
	 *         that has already been transmitted to the DOI agency
	 *     AND the DOI for the object has changed since its first registration
	 *    WHEN I click the "Update" button
	 *    THEN the new DOI will be automatically registered with the DOI
	 *         agency as a new object with a relation to the object identified
	 *         by the previous DOI.
	 */
	public function testUpdate() {
		self::markTestIncomplete('Need to implement registration to test this (see AP9).');
	}


	/**
	 * SCENARIO OUTLINE: Objects without DOIs cannot be selected for export.
	 *
	 *   GIVEN I configure custom DOIs
	 *     AND I've not yet assigned a DOI to an object
	 *    THEN the object will not appear on the corresponding {export page}.
	 *
	 * EXAMPLES: See sub-classes.
	 */
	protected function testObjectsWithoutDOICannotBeSelectedForExport($exportPages) {
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
		$suppFileDao = DAORegistry::getDAO('SuppFileDAO'); /* @var $suppFileDao SuppFileDAO */
		$suppFiles = $suppFileDao->getSuppFilesByArticle(1);
		foreach($suppFiles as $suppFile) {
			$suppFile->setData('doiSuffix', '');
			$suppFileDao->updateSuppFile($suppFile);
		}

		foreach($exportPages as $exportPage) {
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
	protected function testPluginSettings($tests, $inputTypes) {
		$this->logIn();
		foreach($tests as $test) {
			// Change settings.
			$this->open($this->pages['settings']);
			foreach($test as $setting => $value) {
				switch ($inputTypes[$setting]) {
					case TEST_INPUTTYPE_SELECT:
						$this->select($setting, 'value='.$value);
						break;

					case TEST_INPUTTYPE_TEXT:
					case TEST_INPUTTYPE_EMAIL:
						$this->type($setting, $value);
						break;

					default:
						$this->fail('Unknown input type.');
				}

			}
			$this->clickAndWait('css=input.button.defaultButton');

			// Check whether settings are correctly displayed. This implicitly checks
			// that the settings have been stored to the database.
			$this->open($this->pages['settings']);
			foreach($test as $setting => $value) {
				switch ($inputTypes[$setting]) {
					case TEST_INPUTTYPE_SELECT:
						$this->assertIsSelected($setting, $value);
						break;

					case TEST_INPUTTYPE_TEXT:
						$this->assertValue($setting, 'exact:' . $value);
						break;
				}
			}
		}
	}


	/**
	 * SCENARIO: Disable plug-in when .
	 *
	 *   GIVEN I have {configuration error}
	 *    WHEN I navigate to the plug-in home page
	 *    THEN I'll see an error message
	 *     AND there'll be no links to the export pages.
	 *
	 * EXAMPLES:
	 *   configuration error
	 *   ==========================
	 *   no DOI prefix configured
	 *   not configured the plug-in
	 */
	protected function testConfigurationError($exportPages, $sampleConfigParam) {
		$this->logIn();

		// Make sure that no DOI prefix is configured.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal = $journalDao->getJournal(1);
		$journal->updateSetting('doiPrefix', '');

		// Assert that the error is being discovered by the plug-in.
		$this->assertConfigurationError($exportPages, 'A valid DOI prefix must be specified');

		// Now configure a prefix but make sure that the given
		// sample configuration parameter is empty.
		$journal->updateSetting('doiPrefix', '10.1234');
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(1, $this->pluginId . 'exportplugin', $sampleConfigParam, '');
		$this->assertConfigurationError($exportPages, 'The plug-in is not fully set up');

		// With the default configuration export should be allowed.
		$this->configurePlugin();
		$this->assertConfigurationError($exportPages);
	}


	/**
	 * SCENARIO OUTLINE: Export objects on the command line.
	 *
	 *  GIVEN I am in the applications base directory
	 *    AND I configured the DOI plug-in with {settings}
	 *   WHEN I enter the following command on the command line:
	 *        > php tools/importExport.php {export plug-in} \
	 *        > export files/test test \
	 *        > {export object type} {object ids}
	 *   THEN files/test.xml or files/test.tar.gz respectively
	 *        will contain the XML specified in {XML file(s)}.
	 *
	 * EXAMPLES: See sub-classes.
	 */
	protected function testExportObjectsViaCLI($exportPlugin, $exportObjectType, $objectIds, $xmlFiles) {
		$this->fakeRouter();

		// Immutable test parameters.
		$outputFile = Config::getVar('files', 'files_dir') . '/test';
		$journalPath = 'test';

		try {
			// Construct the command line arguments.
			$args = array('export', $outputFile, $journalPath, $exportObjectType);
			$args = array_merge($args, explode(' ', $objectIds));

			// Call the CLI.
			$result = $this->executeCLI($exportPlugin, $args);

			// Check that we didn't get any error messages.
			$this->assertEquals('', $result);

			// Check the existence of the output file.
			$realOutputFiles = glob("$outputFile.{xml,tar.gz}", GLOB_BRACE);
			try {
				self::assertEquals(1, count($realOutputFiles));
			} catch (Exception $e) {
				foreach($realOutputFiles as $realOutputFile) {
					unlink($realOutputFile);
				}
				throw $e;
			}
			$realOutputFile = realpath(array_pop($realOutputFiles));

			// Check the XML.
			if (pathinfo($realOutputFile, PATHINFO_EXTENSION) == 'xml') {
				$exportedXml = file_get_contents($realOutputFile);
			} else {
				$exportedXml = $this->extractTarFile($realOutputFile);
			}
			$this->assertXml($xmlFiles, $exportedXml);
			unlink($realOutputFile);
		} catch(Exception $e) {
			$commandLine = "'php tools /importExport.php $exportPlugin "
				. "files/test.xml test $exportObjectType "
				. "$objectIds'";
			throw $this->improveException($e, $commandLine);
		}
	}


	/**
	 * SCENARIO OUTLINE: Register objects on the command line.
	 *
	 *   WHEN I enter the following command on the command line:
	 *        > php tools/importExport.php {export plug-in} \
	 *        > register test {export object type} {object ids}
	 *   THEN the specified objects will be automatically registered
	 *        with mEDRA.
	 *
	 * EXAMPLES: See sub-classes.
	 */
	protected function testRegisterObjectViaCLI() {
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
	protected function testUnsupportedObjectTypeCliError($exportPlugin) {
		$this->fakeRouter();

		// Construct the command line arguments.
		$unsupportedObjectType = 'journal';
		$args = array('export', 'files/test', 'test', $unsupportedObjectType, '1');

		// Call the CLI.
		// NB: We check the translation key rather than the string. This is a limitation
		// of the test environment, not of the implementation.
		$result = $this->executeCLI($exportPlugin, $args);
		$this->assertRegExp('/##plugins.importexport.common.export.error.unknownObjectType##/', $result);
	}


	/**
	 * SCENARIO: non-existent journal (CLI error)
	 *
	 *    WHEN I enter a non-existent journal path on
	 *         the CLI command line
	 *    THEN I'll receive a CLI error "No journal matched
	 *         the specified journal path: ...."
	 */
	protected function testNonExistentJournalPathCliError($exportPlugin) {
		$this->fakeRouter();

		// Construct the command line arguments.
		$args = array('export', 'files/test', 'non-existent-journal', 'issues', '1');

		// Call the CLI.
		// NB: We check the translation key rather than the string. This is a limitation
		// of the test environment, not of the implementation.
		$result = $this->executeCLI($exportPlugin, $args);
		$this->assertRegExp('/##plugins.importexport.common.export.error.unknownJournal##/', $result);
	}


	/**
	 * SCENARIO: output file not writable (CLI error)
	 *
	 *    WHEN I enter an output file that is not writable
	 *    THEN I'll receive a CLI error "The output file ...
	 *         is not writable."
	 */
	protected function testOutputFileNotWritableCliError($exportPlugin) {
		$this->fakeRouter();

		// Construct the command line arguments.
		$args = array('export', 'files/some-non-existent-path/test', 'test', 'issues', '1');

		// Call the CLI.
		// NB: We check the translation key rather than the string. This is a limitation
		// of the test environment, not of the implementation.
		$result = $this->executeCLI($exportPlugin, $args);
		$this->assertRegExp('/##plugins.importexport.common.export.error.outputFileNotWritable##/', $result);
	}


	/**
	 * SCENARIO: non-existent object id (CLI error)
	 *
	 *    WHEN I enter a non-existent journal path on
	 *         the CLI command line
	 *    THEN I'll receive a CLI error "No journal matched
	 *         the specified journal path: ...."
	 */
	protected function testNonExistentObjectIdCliError($exportPlugin) {
		$this->fakeRouter();

		// Construct the command line arguments.
		foreach(array('issue', 'article', 'galley') as $objectType) {
			$args = array('export', 'files/medra/test.xml', 'test', "${objectType}s", '999');

			// Call the CLI.
			// NB: We check the translation key rather than the string. This is a limitation
			// of the test environment, not of the implementation.
			$result = $this->executeCLI($exportPlugin, $args);
			$this->assertRegExp("/##plugins.importexport.common.export.error.${objectType}NotFound##/", $result);
		}
	}


	//
	// Protected helper methods
	//
	/**
	 * Normalize the XML.
	 * @param $xml string
	 * @return string
	 */
	protected function cleanXml($xml) {
		// Fix missing translations. This is a problem of the test environment not of the implementation.
		$xml = str_replace('##issue.vol##', 'Vol', $xml);
		$xml = str_replace('##issue.no##', 'No', $xml);

		return $xml;
	}

	/**
	 * Test XML against file.
	 * @param $files string|array
	 * @param $xml string|array
	 */
	protected function assertXml($files, $xml) {
		// Normalize parameters.
		if (is_scalar($files)) $files = array($files);
		if (is_scalar($xml)) $xml = array($xml);

		self::assertEquals(count($files), count($xml));
		foreach($files as $file) {
			if (is_array($file)) {
				// Recursively check XML if we have a sub-array (=sub-tar) of files.
				$this->assertXml($file, array_shift($xml));
			} else {
				$xmlString = array_shift($xml);

				if ($this->expectJournalNameAsPublisher) {
					$xmlString = $this->checkThatPublisherIsJournalName($xmlString);
				}

				$this->assertXmlStringEqualsXmlFile(
					'./tests/functional/plugins/importexport/' . $this->pluginId . '/' . $file,
					$this->cleanXml($xmlString), 'Error while checking ' . $file
				);
			}
		}
	}

	/**
	 * Check whether the publisher has been correctly
	 * replaced by the journal name.
	 * @param $xml string
	 * @return string
	 */
	protected function checkThatPublisherIsJournalName($xml) {
		self::fail('Must be implemented by subclass');
	}

	/**
	 * Alter the plugin-configuration directly in the database.
	 *
	 * NB: We do not use Selenium here to improve performance.
	 *
	 * @param $settings array
	 */
	protected function configurePlugin($settings = array()) {
		$settings = $settings + $this->defaultPluginSettings;
		$settingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $settingsDao PluginSettingsDAO */
		foreach($settings as $settingName => $settingValue) {
			$settingsDao->updateSetting(1, $this->pluginId . 'exportplugin', $settingName, $settingValue);
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Fake a router for CLI tests.
	 */
	private function fakeRouter() {
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		import('classes.core.PageRouter');
		$router = new PageRouter();
		$router->setApplication($application);
		$request->setRouter($router);
	}

	/**
	 * Test configuration error.
	 * @param $exportPages array
	 * @param $expectedErrorMessage string
	 */
	private function assertConfigurationError($exportPages, $expectedErrorMessage = null) {
		// Navigate to the plug-in home page.
		$this->open($this->pages['index']);

		// Check the error message.
		if (!is_null($expectedErrorMessage)) {
			$this->assertText('content', $expectedErrorMessage);
		}

		// Make sure that (no) export links are present.
		foreach ($exportPages as $page) {
			try {
				$locator = 'css=a[href="'.$this->pages[$page].'"]';
				if (is_null($expectedErrorMessage)) {
					$this->assertElementPresent($locator);
				} else {
					$this->assertElementNotPresent($locator);
				}
			} catch(Exception $e) {
				throw $this->improveException($e, "$page page");
			}
		}
	}
}
?>