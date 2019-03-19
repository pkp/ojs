<?php

/**
 * @file tests/data/20-CreateContextTest.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateContextTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create and configure a test journal
 */

import('lib.pkp.tests.data.PKPCreateContextTest');

use Facebook\WebDriver\Interactions\WebDriverActions;

class CreateContextTest extends PKPCreateContextTest {
	/** @var array */
	public $contextName = [
		'en_US' => 'Journal of Public Knowledge',
		'fr_CA' => 'Journal de la connaissance du public',
	];

	/** @var string journal or press*/
	public $contextType = 'journal';

	/** @var array */
	public $contextDescription = [
		'en_US' => 'The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.',
		'fr_CA' => 'Le Journal de Public Knowledge est une publication trimestrielle évaluée par les pairs sur le thème de l\'accès du public à la science.',
	];

	/** @var array */
	public $contextAcronym = [
		'en_US' => 'JPK',
		'fr_CA' => 'JCP',
	];

	/**
	 * Prepare for tests.
	 */
	function testCreateContextLogin() {
		parent::logIn('admin', 'admin');
	}

	/**
	 * Create and set up test data
	 */
	function testCreateContext() {
		$this->createContext();
	}

	/**
	 * Test the settings wizard
	 */
	function testSettingsWizard() {
		parent::settingsWizard();

		self::$driver->executeScript('window.scrollTo(0,0);'); // Scroll to top of page
		$this->click('//a[text()="Journal"]');
		$this->setInputValue('[name="abbreviation-en_US"]', 'publicknowledge');
		$this->click('//*[@id="journal"]//button[contains(text(),"Save")]');
		$this->waitForTextPresent($this->contextName['en_US'] . ' was edited successfully.');
	}

	/**
	 * Test the Settings > Journal forms
	 */
	function testSetupContext() {
		$this->open(self::$baseUrl);

		// Settings > Journal > Masthead
		$actions = new WebDriverActions(self::$driver);
		$actions->moveToElement($this->waitForElementPresent('css=ul#navigationUser>li.profile>a'))
			->click($this->waitForElementPresent('//ul[@id="navigationUser"]//a[contains(text(),"Dashboard")]'))
			->perform();
		$actions = new WebDriverActions(self::$driver);
		$actions->moveToElement($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[text()="Settings"]'))
			->click($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[text()="Journal"]'))
			->perform();
		$this->setInputValue('[name="abbreviation-en_US"]', 'J Pub Know');
		$this->setInputValue('[name="acronym-en_US"]', 'PK');
		$this->setInputValue('[name="publisherInstitution"]', 'Public Knowledge Project');

		// Invalid onlineIssn
		$this->setInputValue('[name="onlineIssn"]', '0378-5955x');
		$this->click('//*[@id="masthead"]//button[contains(text(),"Save")]');
		$this->waitForElementPresent('//*[@id="masthead-onlineIssn-error"]//*[contains(text(),"This is not a valid ISSN.")]');
		$this->setInputValue('[name="onlineIssn"]', '0378-5955');

		// Invalid printIssn
		$this->setInputValue('[name="printIssn"]', '03785955');
		$this->click('//*[@id="masthead"]//button[contains(text(),"Save")]');
		$this->waitForElementPresent('//*[@id="masthead-printIssn-error"]//*[contains(text(),"This is not a valid ISSN.")]');
		$this->setInputValue('[name="printIssn"]', '0378-5955');

		$this->click('//*[@id="masthead"]//button[contains(text(),"Save")]');
		$this->waitForTextPresent('The masthead details for this journal have been updated.');

		$this->contactSettings();
	}
}
