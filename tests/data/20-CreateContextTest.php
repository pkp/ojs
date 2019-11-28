<?php

/**
 * @file tests/data/20-CreateContextTest.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateContextTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create and configure a test server
 */

import('lib.pkp.tests.data.PKPCreateContextTest');

use Facebook\WebDriver\Interactions\WebDriverActions;

class CreateContextTest extends PKPCreateContextTest {
	/** @var array */
	public $contextName = [
		'en_US' => 'Public Knowledge Preprint Server',
		'fr_CA' => 'Serveur de prépublication de la connaissance du public',
	];

	/** @var string journal or press*/
	public $contextType = 'server';

	/** @var array */
	public $contextDescription = [
		'en_US' => 'The Public Knowledge Preprint Server is a preprint service on the subject of public access to science.',
		'fr_CA' => 'Le Serveur de prépublication de la connaissance du public est une service trimestrielle évaluée par les pairs sur le thème de l\'accès du public à la science.',
	];

	/** @var array */
	public $contextAcronym = [
		'en_US' => 'PKP',
		'fr_CA' => 'PCP',
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
		$this->click('//button[@id="context-button"]');
		$this->setInputValue('[name="abbreviation-en_US"]', 'publicknowledge');
		$this->click('//*[@id="context"]//button[contains(text(),"Save")]');
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
			->click($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[text()="Server"]'))
			->perform();
		$this->setInputValue('[name="abbreviation-en_US"]', 'Pub Know Pre');
		$this->setInputValue('[name="acronym-en_US"]', 'PK');

		$this->click('//*[@id="masthead"]//button[contains(text(),"Save")]');
		$this->waitForTextPresent('The masthead details for this server have been updated.');

		$this->contactSettings();
	}
}
