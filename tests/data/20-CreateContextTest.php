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

import('lib.pkp.tests.WebTestCase');

class CreateContextTest extends WebTestCase {
	/**
	 * Prepare for tests.
	 */
	function testCreateContextLogin() {
		parent::logIn('admin', 'admin');
	}

	/**
	 * Create and set up test data journal.
	 */
	function testCreateContext() {
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
		$this->waitForElementPresent('css=div.header:contains(\'Settings Wizard\')');
		$this->waitJQuery();
	}

	/**
	 * Set up the test journal.
	 */
	function testSetupContext() {
		$this->open(self::$baseUrl);

		// Management > Settings > Journal
		$this->waitForElementPresent($selector='css=li.profile a:contains(\'Dashboard\')');
		$this->clickAndWait($selector);
		$this->waitForElementPresent($selector='css=ul#navigationPrimary a:contains(\'Journal\')');
		$this->clickAndWait($selector);
		$this->waitForElementPresent('css=[id^=abbreviation-]');
		$this->type('css=[id^=abbreviation-]', 'J Pub Know');
		$this->type('css=[id^=acronym-]', 'PK');
		$this->click('//form[@id=\'mastheadForm\']//button[text()=\'Save\']');
		$this->waitForTextPresent('Your changes have been saved.');

		// Management > Settings > Contact
		$this->click('link=Contact');
		$this->waitForElementPresent($selector='css=[id^=contactEmail-]');
		$this->type($selector, 'rvaca@mailinator.com');
		$this->type('css=[id^=contactName-]', 'Ramiro Vaca');
		$this->type('css=[id^=supportEmail-]', 'rvaca@mailinator.com');
		$this->type('css=[id^=supportName-]', 'Ramiro Vaca');
		$this->type('css=[id^=mailingAddress-]', "123 456th Street\nBurnaby, British Columbia\nCanada");
		$this->click('//form[@id=\'contactForm\']//button[text()=\'Save\']');
		$this->waitForTextPresent('Your changes have been saved.');
	}
}
