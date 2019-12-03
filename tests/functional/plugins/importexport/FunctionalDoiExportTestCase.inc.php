<?php

/**
 * @file tests/functional/plugins/importexport/FunctionalDoiExportTest.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
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
		$pluginId, $pages,
		$defaultPluginSettings, $initialPluginSettings,
		$initialJournalSettings, $initialDoiSettings,
		$doiPrefix;


	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'plugin_settings', 'issue_settings', 'submission_settings',
			'submission_galley_settings', 'article_supp_file_settings',
			'journal_settings', 'notifications', 'notification_settings'
		);
	}

	//
	// Implement template methods from PHPUnit_Framework_TestCase
	//
	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp($doiPrefix) {
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
		$journal = $journalDao->getById(1);
		$this->initialJournalSettings = array(
			'publisherInstitution' => $journal->getData('publisherInstitution'),
			'supportEmail' => $journal->getData('supportEmail')
		);

		// Store initial DOI settings.
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds');
		$doiPlugin = $pubIdPlugins['DOIPubIdPlugin'];
		$this->initialDoiSettings = array(
			'enabled' => $doiPlugin->getSetting(1, 'enabled'),
			'doiPrefix' => $doiPlugin->getSetting(1, 'doiPrefix'),
			'doiSuffix' => $doiPlugin->getSetting(1, 'doiSuffix')
		);

		// Reset DOI prefix and all DOIs.
		$this->doiPrefix = $doiPrefix;
		$doiPlugin->updateSetting(1, 'enabled', true);
		$doiPlugin->updateSetting(1, 'doiPrefix', $doiPrefix);
		$doiPlugin->updateSetting(1, 'doiSuffix', 'default');
		PKPTestHelper::xdebugScream(false);
		$journalDao->deleteAllPubIds(1, 'doi');
		PKPTestHelper::xdebugScream(true);

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
	protected function tearDown() : void {
		// Restoring the tables alone will not update the settings cache
		// so we have to do this manually.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var journalDao JournalDAO */
		$journal = $journalDao->getById(1);
		foreach($this->initialJournalSettings as $settingName => $settingValue) {
			$journal->updateSetting($settingName, $settingValue);
		}

		// Restore initial DOI configuration.
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds');
		$doiPlugin = $pubIdPlugins['DOIPubIdPlugin'];
		foreach($this->initialDoiSettings as $settingName => $settingValue) {
			$doiPlugin->updateSetting(1, $settingName, $settingValue);
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
	protected function doTestExpectJournalNameAsPublisher() {
		// Test whether a missing publisher is being replaced
		// with the journal name.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal = $journalDao->getById(1);
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
			$xml = $this->getXmlOnExport($exportPlugin . '/export'. ucfirst($objectType) . '/' . $objectIds[0], 'testMode=1');
			$this->assertXml($sampleFile, $xml);
		}

		if (!$singleTest || ($singleTest && count($objectIds) > 1)) {
			// Export via multi-object form request.
			$objectParams = 'testMode=1';
			foreach($objectIds as $objectId) {
				$objectParams .= "&${objectType}Id[]=$objectId";
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
		$objectParams = 'testMode=1';
		foreach($objects as $objectType => $objectIds) {
			if (is_scalar($objectIds)) $objectIds = array($objectIds);
			foreach($objectIds as $objectId) {
				$objectParams .= "&${objectType}Id[]=$objectId";
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
	 *
	 *
	 * SCENARIO: Registration notification.
	 *   GIVEN I clicked the register button on one of the
	 *         export pages
	 *    WHEN the registration was successful
	 *    THEN I'll be redirected to the plug-in's index page
	 *     AND I'll see a notification "Registration successful!"
	 *     AND the registration button of the registered object(s)
	 *         will change to "Update".
	 *
	 *
	 * SCENARIO: Export without registration account.
	 *   GIVEN I do not have a registration account
	 *     AND I therefore entered no credentials on the
	 *         plug-in's configuration page
	 *    WHEN I navigate to an {export page}
	 *    THEN I'll not see any "Registration" buttons
	 *     BUT "Export" still works.
	 */
	protected function testRegisterOrExportSpecificObjects($pluginName, $objectTypes, $testAccount, $testReset = false) {
		$this->logIn();
		$this->removeRegisteredDois($pluginName);
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		foreach($objectTypes as $objectType) {
			// Navigate to the object's export page (in test mode).
			$pageUrl = $this->pages[$objectType.'s'] . '?testMode=1';
			$this->open($pageUrl);

			// Single object:
			// - Export.
			// We do not actually export as this is already being tested elsewhere.
			$buttonLocator = 'css=a.action[href="'.$this->pages['index'].'/%action'.ucfirst($objectType).'/1?testMode=1"]';
			$exportButton = str_replace('%action', 'export', $buttonLocator);
			$this->assertText($exportButton, 'Export');

			// - Register.
			$registerButton = str_replace('%action', 'register', $buttonLocator);
			$this->assertText($registerButton, 'Register');
			$this->clickAndWait($registerButton);
			// When registration was successful then we should be
			// redirected to the index page and see a notification
			// "Registration Successful".
			$this->setTimeout(120000); // Registering can take a long time.
			$this->waitForElementPresent('css=.ui-pnotify-text');
			$this->assertText('css=.ui-pnotify-text', 'Registration successful!');
			$this->setTimeout(30000);

			// Make sure that the button for the registered object now reads "Update"
			// rather than "Register".
			$this->assertText($registerButton, 'Update');

			// Test the reset button.
			if ($testReset) {
				// There should be a "Reset" button for already registered objects.
				$resetButton = str_replace('%action', 'reset', $buttonLocator);
				$this->assertElementPresent($resetButton);
				$this->assertText($resetButton, 'Reset');
				// When I reset the object ...
				$this->clickAndWait($resetButton);
				$this->waitForLocation('exact:'.$pageUrl);
				$this->waitForElementPresent($registerButton);
				// ... then the registration button reads "Register" again ...
				$this->assertText($registerButton, 'Register');
				// ... and the "Reset" button should have disappeared.
				$this->assertElementNotPresent($resetButton);
			}

			// Several objects:
			// - Export.
			// We do not actually export as this is being tested elsewhere.
			$this->assertElementPresent('css=form[id="'.$objectType.'sForm"][action="'.$this->pages['index'].'/export'.ucfirst($objectType).'s"]');
			$this->assertElementPresent('css=input[name="'.$objectType.'Id[]"]');
			$this->assertElementPresent('css=input.button[name="export"]');

			// -Register.
			$this->click('css=input.button[value="Select All"]');
			$this->clickAndWait('css=input.button[name="register"]');
			$this->setTimeout(120000); // Registering can take a long time.
			$this->waitForElementPresent('css=.ui-pnotify-text');
			$this->assertText('css=.ui-pnotify-text', 'Registration successful!');
			$this->setTimeout(30000);

			// Export without registration account:
			// Delete the account setting.
			$pluginSettingsDao->updateSetting(1, $this->pluginId . 'exportplugin', 'username', '');
			// Reload the page.
			$this->open($pageUrl);
			// Check that only export buttons are visible now.
			$this->assertElementPresent($exportButton);
			$this->assertElementNotPresent($registerButton);
			$this->assertElementPresent('css=input.button[name="export"]');
			$this->assertElementNotPresent('css=input.button[name="register"]');
			// Reconfigure the account setting.
			$pluginSettingsDao->updateSetting(1, $this->pluginId . 'exportplugin', 'username', $testAccount);
		}
	}


	/**
	 * SCENARIO: Register unregistered DOIs - part 1
	 *
	 *   GIVEN I navigate to the DOI export plug-in home page
	 *    WHEN I click the "register all unregistered DOIs" button
	 *    THEN I'll see a list of all unregistered objects
	 *     AND I'll be presented with an "Export" and a "Register" button.
	 *
	 * SCENARIO: Register unregistered DOIs - part 2
	 *
	 *   GIVEN I am presented with a list of unregistered objects after
	 *         having clicked the "register all unregistered DOIs" button
	 *     AND I have selected all objects on that page
	 *    WHEN I click the "Register" button
	 *    THEN all DOIs of issues, articles and galleys on that list
	 *         will be automatically registered with the DOI agency as new objects.
	 *     AND I'll be redirected to the plug-ins home page
	 *     AND I'll see a notification 'Registration successful!'
	 *     AND the list with unregistered objects will be empty.
	 *
	 * SCENARIO: Export without registration account, see self::testRegisterOrExportSpecificObjects()
	 */
	protected function testRegisterUnregisteredDois($pluginName, $expectedObjectCaptions, $testAccount) {
		$this->logIn();
		$this->removeRegisteredDois($pluginName);

		// Part 1:
		// Navigate to the mEDRA export plug-in home page.
		$this->open($this->pages['index']);
		// Click the "register all unregistered DOIs" button.
		$this->clickAndWait('//a[@href="'.$this->pages['all'].'"]');
		// Check whether the list of unregistered objects is there including
		// at least an issue, an article and several galleys.
		$objectTypeColumn = '//div[@id="allUnregistered"]//tr/td[2]/label';
		foreach($expectedObjectCaptions as $expectedObjectCaption) {
			$this->assertElementPresent($objectTypeColumn . '[normalize-space(text())="' .$expectedObjectCaption .'"]');
		}
		// Check whether we have an export (register) button.
		$this->assertElementPresent('css=input.button[name="export"]');
		$this->assertElementPresent('css=input.button[name="register"]');

		// Export without registration account:
		// Delete the account setting.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(1, $this->pluginId . 'exportplugin', 'username', '');
		// Reload the page.
		$pageUrl = $this->pages['all'] . '?testMode=1';
		$this->open($pageUrl);
		// Check that only export buttons are visible now.
		$this->assertElementPresent('css=input.button[name="export"]');
		$this->assertElementNotPresent('css=input.button[name="register"]');
		// Reconfigure the account setting.
		$pluginSettingsDao->updateSetting(1, $this->pluginId . 'exportplugin', 'username', $testAccount);

		// Part 2:
		// We have to re-open the page in test mode.
		$this->open($pageUrl);
		$this->setTimeout(180000); // Registering can take a long time.
		$this->clickAndWait('css=input.button[name="register"]');
		$this->setTimeout(30000);
		$this->waitForLocation('exact:'.$pageUrl);
		$this->waitForElementPresent('css=.ui-pnotify-text');
		$this->assertText('css=.ui-pnotify-text', 'Registration successful!');

		// Check that all newly registered objects have disappeared
		// from the list of unregistered objects.
		$this->assertElementPresent('css=td.nodata');
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
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds');
		$doiPlugin = $pubIdPlugins['DOIPubIdPlugin'];
		$doiPlugin->updateSetting(1, 'doiSuffix', 'customId');

		// Delete all existing DOIs.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		PKPTestHelper::xdebugScream(false);
		$journalDao->deleteAllPubIds(1, 'doi');
		PKPTestHelper::xdebugScream(true);

		// Make sure that no custom suffix is saved for our test objects.
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issue = $issueDao->getById(1, 1);
		$issue->setData('doiSuffix', '');
		$issueDao->updateObject($issue);
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$article = $submissionDao->getById(1, 1);
		$article->setData('doiSuffix', '');
		$submissionDao->updateObject($article); // Do not use PublishedSubmissionDAO::updatePublishedSubmission() for this, otherwise the ADODB cache flush there may cause a permission error.
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$galleys = $galleyDao->getByPublicationId(1);
		while ($galley = $galleys->next()) {
			$galley->setData('doiSuffix', '');
			$galleyDao->updateObject($galley);
		}

		foreach($exportPages as $exportPage) {
			// Check that the object does not appear on the export page.
			$this->open($this->pages[$exportPage]);

			// Check that we get an empty list. This implicitly tests
			// that the "no data" marker is correctly displayed.
			$this->assertElementPresent('css=td.nodata');
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
	 * SCENARIO OUTLINE: Disable plug-in when .
	 *
	 *   GIVEN I have {configuration error}
	 *    WHEN I navigate to the plug-in home page
	 *    THEN I'll see an error message
	 *     AND there'll be no links to the export pages.
	 *
	 * EXAMPLES: See sub-classes.
	 */
	protected function testConfigurationError($exportPages) {
		$this->logIn();
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds');
		$doiPlugin = $pubIdPlugins['DOIPubIdPlugin'];
		$noDoiError = 'specify a valid DOI prefix';

		// Disable the DOI plug-in.
		$doiPlugin->updateSetting(1, 'enabled', false);

		// Assert that the error is being discovered by the plug-in.
		$this->assertConfigurationError($exportPages, $noDoiError);

		// Enable the DOI plug-in but make sure that no DOI prefix is configured.
		$doiPlugin->updateSetting(1, 'enabled', true);
		$doiPlugin->updateSetting(1, 'doiPrefix', '');

		// Assert that the error is being discovered by the plug-in.
		$this->assertConfigurationError($exportPages, $noDoiError);

		// Export should be allowed even when non-mandatory settings are not set.
		$doiPlugin->updateSetting(1, 'doiPrefix', $this->doiPrefix);
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(1, $this->pluginId . 'exportplugin', 'username', '');
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
	 *
	 *
	 * SCENARIO OUTLINE: Register objects on the command line.
	 *
	 *  GIVEN I am in the applications base directory
	 *    AND I configured the DOI plug-in with {settings}
	 *   WHEN I enter the following command on the command line:
	 *        > php tools/importExport.php {export plug-in} \
	 *        > register test {export object type} {object ids}
	 *   THEN the given objects will be registered with the
	 *        registration agency.
	 *    AND the script will return "Registration successful!".
	 *
	 * EXAMPLES: See sub-classes.
	 */
	protected function testExportAndRegisterObjectsViaCli($exportPlugin, $command, $exportObjectType, $objectIds, $xmlFiles = null) {
		$request = $this->fakeRouter();
		$request->_requestVars['testMode'] = 1;

		// Immutable test parameters.
		$outputFile = Config::getVar('files', 'files_dir') . '/test';
		$journalPath = 'test';

		// Construct the command line arguments.
		$args = array($command);
		if ($command == 'export') {
			$args[] = $outputFile;
		}
		$args = array_merge($args, array($journalPath, $exportObjectType), explode(' ', $objectIds));

		// Call the CLI.
		$result = $this->executeCli($exportPlugin, $args);

		if ($command == 'export') {
			// Check that we didn't get any error messages.
			$this->assertEquals('', $result);
		} else {
			// We should get feedback that the registration was successful.
			$this->assertRegExp('/##plugins.importexport.common.register.success##/', $result);
		}

		if ($command == 'export') {
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
		}
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
		$result = $this->executeCli($exportPlugin, $args);
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
		$result = $this->executeCli($exportPlugin, $args);
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
		$result = $this->executeCli($exportPlugin, $args);
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
			$result = $this->executeCli($exportPlugin, $args);
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
		$xml = str_replace('##issue.vol##', 'Vol.', $xml);
		$xml = str_replace('##issue.no##', 'No.', $xml);

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
					$this->getSampleFileLocation($file),
					$this->cleanXml($xmlString), 'Error while checking ' . $file
				);
			}
		}
	}

	/**
	 * Return the path of a sample file relative
	 * to the application base directory.
	 * @param $fileName string
	 * @return string
	 */
	protected function getSampleFileLocation($fileName) {
		return './tests/functional/plugins/importexport/' . $this->pluginId . '/' . $fileName;
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

	/**
	 * Check whether the given DOI resolves correctly to the
	 * given target URL and has the meta-data from the sample
	 * file registered.
	 * @param $objectType string
	 * @param $sampleFile string
	 * @param $expectedTargetUrl string
	 */
	protected function checkDoiRegistration($doi, $sampleFile, $expectedTargetUrl = null) {
		self::fail('not implemented');
	}

	/**
	 * Fake a router for CLI tests.
	 * @return Request
	 */
	protected function fakeRouter($host = null) {
		if ($host) $_SERVER['HTTP_HOST'] = $host;
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PATH_INFO'] = '/test';
		$application = Application::getApplication();
		$request = $application->getRequest();
		import('classes.core.PageRouter');
		$router = new PageRouter();
		$router->setApplication($application);
		$request->setRouter($router);
		return $request;
	}

	/**
	 * Remove registered DOIs for all out test objects.
	 * @param $pluginName string
	 */
	protected function removeRegisteredDois($pluginName) {
		// Mark all our test objects as "unregistered".
		$configurations = array(
			'Issue' => array('IssueDAO', 'updateObject', 'getById', 1),
			'Article' => array('SubmissionDAO', 'updateObject', 'getArticle', 1),
			'ArticleGalley' => array('ArticleGalleyDAO', 'updateObject', 'getById', array(1,2,3)),
		);
		$pluginInstance = $this->instantiatePlugin($pluginName);
		foreach($configurations as $objectType => $configuration) {
			list($daoName, $updateMethod, $getMethod, $testIds) = $configuration;
			$dao = DAORegistry::getDAO($daoName);
			if (is_scalar($testIds)) $testIds = array($testIds);

			$hookName = strtolower_codesafe($daoName) . '::getAdditionalFieldNames';
			HookRegistry::register($hookName, array($pluginInstance, 'getAdditionalFieldNames'));
			foreach($testIds as $testId) {
				// Retrieve the test object.
				$testObject = $dao->$getMethod($testId);

				// Remove the registered DOI.
				$testObject->setData($this->pluginId . '::' . DOI_EXPORT_REGDOI, '');
				$dao->$updateMethod($testObject);
			}

			$hooks = HookRegistry::getHooks();
			foreach($hooks[$hookName] as $index => $hook) {
				if (is_a($hook[0], $pluginName)) {
					unset($hooks[$hookName][$index]);
					break;
				}
			}
		}
	}

	/**
	 * Test configuration error.
	 * @param $exportPages array
	 * @param $expectedErrorMessage string
	 */
	protected function assertConfigurationError($exportPages, $expectedErrorMessage = null) {
		// Navigate to the plug-in home page.
		$this->open($this->pages['index']);

		// Check the error message.
		if (!is_null($expectedErrorMessage)) {
			$errorMessage = $this->getText('content');
			$this->assertContains($expectedErrorMessage, $errorMessage);
		}

		// Make sure that (no) export links are present.
		foreach ($exportPages as $page) {
			$locator = 'css=a[href="'.$this->pages[$page].'"]';
			if (is_null($expectedErrorMessage)) {
				$this->assertElementPresent($locator);
			} else {
				$this->assertElementNotPresent($locator);
			}
		}
	}
}

