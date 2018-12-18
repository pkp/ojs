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

		$this->setInputValue('[name="abbreviation-en_US"]', 'publicknowledge');
		$this->click('css=#journal button:contains(\'Save\')');
		$this->waitForTextPresent($this->contextName['en_US'] . ' was edited successfully.');
	}

	/**
	 * Test the Settings > Journal forms
	 */
	function testSetupContext() {
		$this->open(self::$baseUrl);

		// Settings > Journal > Masthead
		$this->waitForElementPresent($selector='css=li.profile a:contains(\'Dashboard\')');
		$this->clickAndWait($selector);
		$this->waitForElementPresent($selector='css=ul#navigationPrimary a:contains(\'Journal\')');
		$this->clickAndWait($selector);
		$this->setInputValue('[name="abbreviation-en_US"]', 'J Pub Know');
		$this->setInputValue('[name="acronym-en_US"]', 'PK');
		$this->setInputValue('[name="publisherInstitution"]', 'Public Knowledge Project');

		// Invalid onlineIssn
		$this->setInputValue('[name="onlineIssn"]', '0378-5955x');
		$this->click('css=#masthead button:contains(\'Save\')');
		$this->waitForElementPresent('css=#masthead-onlineIssn-error:contains(\'This is not a valid ISSN.\')');
		$this->setInputValue('[name="onlineIssn"]', '0378-5955');

		// Invalid printIssn
		$this->setInputValue('[name="printIssn"]', '03785955');
		$this->click('css=#masthead button:contains(\'Save\')');
		$this->waitForElementPresent('css=#masthead-printIssn-error:contains(\'This is not a valid ISSN.\')');
		$this->setInputValue('[name="printIssn"]', '0378-5955');

		$this->click('css=#masthead button:contains(\'Save\')');
		$this->waitForTextPresent('The masthead details for this journal have been updated.');

		$this->contactSettings();
	}

	/**
	 * Helper function to go to the hosted journals page
	 */
	function goToHostedContexts() {
		$this->open(self::$baseUrl);
		$this->waitForElementPresent('link=Administration');
		$this->click('link=Administration');
		$this->waitForElementPresent('link=Hosted Journals');
		$this->click('link=Hosted Journals');
	}
}
