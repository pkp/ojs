<?php

/**
 * @file tests/data/20-CreateJournalTest.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateJournalTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create and configure a test journal
 */

import('lib.pkp.tests.WebTestCase');

class CreateJournalTest extends WebTestCase {
	/**
	 * Prepare for tests.
	 */
	function testCreateJournalLogin() {
		parent::logIn('admin', 'admin');
	}

	/**
	 * Create and set up test data journal.
	 */
	function testCreateJournal() {
		$this->open(self::$baseUrl);
		$this->waitForElementPresent('link=Administration');
		$this->click('link=Administration');
		$this->waitForElementPresent('link=Hosted Journals');
		$this->click('link=Hosted Journals');
		$this->waitForElementPresent('css=[id^=component-grid-admin-journal-journalgrid-createContext-button-]');
		$this->click('css=[id^=component-grid-admin-journal-journalgrid-createContext-button-]');

		// Enter journal data
		$this->waitForElementPresent('css=[id^=name-en_US-]');
		$this->type('css=[id^=name-en_US-]', 'Journal of Public Knowledge');
		$this->type('css=[id^=name-fr_CA-]', 'Journal de la connaissance du public');
		$this->typeTinyMCE('description-en_US', 'The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.');
		$this->typeTinyMCE('description-fr_CA', 'Le Journal de Public Knowledge est une publication trimestrielle évaluée par les pairs sur le thème de l\'accès du public à la science.');
		$this->type('css=[id^=path-]', 'publicknowledge');
		$this->clickAndWait('css=[id^=submitFormButton-]');
		$this->waitForElementPresent('css=h2:contains(\'Settings Wizard\')');
		$this->waitJQuery();
	}

	/**
	 * Set up the test journal.
	 */
	function testSetupJournal() {
		$this->open(self::$baseUrl);

		// Management > Settings > Journal
		$this->waitForElementPresent('//ul[contains(@class, \'sf-js-enabled\')]//a[text()=\'Journal\']');
		$this->clickAndWait('//ul[contains(@class, \'sf-js-enabled\')]//a[text()=\'Journal\']');
    		$this->waitForElementPresent('css=[id^=abbreviation-]');
    		$this->type('css=[id^=abbreviation-]', 'PK');
		$this->click('//form[@id=\'mastheadForm\']//span[text()=\'Save\']/..');
		$this->waitJQuery();

		// Management > Settings > Contact
		$this->click('link=Contact');
		$this->waitForElementPresent('css=[id^=contactEmail-]');
		$this->type('css=[id^=contactEmail-]', 'rvaca@mailinator.com');
		$this->type('css=[id^=contactName-]', 'Ramiro Vaca');
		$this->type('css=[id^=supportEmail-]', 'rvaca@mailinator.com');
		$this->type('css=[id^=supportName-]', 'Ramiro Vaca');
		$this->click('//form[@id=\'contactForm\']//span[text()=\'Save\']/..');
		$this->waitJQuery();

		// Management > Settings > Website
		$this->click('link=Website');
		$this->waitForElementPresent('css=[id^=pageHeaderTitle-]');
		$this->type('css=[id^=pageHeaderTitle-]', 'Journal of Public Knowledge');
		$this->click('//form[@id=\'appearanceForm\']//span[text()=\'Save\']/..');
		$this->waitJQuery();
	}
}
