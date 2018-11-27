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
		$this->goToHostedJournals();

		$this->waitForElementPresent('css=[id^=component-grid-admin-journal-journalgrid-createContext-button-]');
		$this->click('css=[id^=component-grid-admin-journal-journalgrid-createContext-button-]');

		// Test required fields
		$this->setInputValue('[name="name-fr_CA"]', 'Journal de la connaissance du public');
		$this->click('css=#editContext button:contains(\'Save\')');
		$this->waitForElementPresent('css=#context-name-error-en_US:contains(\'This field is required.\')');
		$this->waitForElementPresent('css=#context-acronym-error-en_US:contains(\'This field is required.\')');
		$this->waitForElementPresent('css=#context-path-error:contains(\'This field is required.\')');
		$this->setInputValue('[name="name-en_US"]', 'Journal of Public Knowledge');
		$this->setInputValue('[name="acronym-en_US"]', 'JPK');

		// Test invalid path characters
		$this->setInputValue('[name="path"]', 'public&-)knowledge');
		$this->click('css=#editContext button:contains(\'Save\')');
		$this->waitForElementPresent('css=#context-path-error:contains(\'The path can only include letters\')');
		$this->setInputValue('[name="path"]', 'publicknowledge');

		$this->typeTinyMCE('context-description-control-en_US', 'The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.');
		$this->typeTinyMCE('context-description-control-fr_CA', 'Le Journal de Public Knowledge est une publication trimestrielle évaluée par les pairs sur le thème de l\'accès du public à la science.');
		$this->clickAndWait('css=#editContext button:contains(\'Save\')');
		$this->waitForElementPresent('css=h1:contains(\'Settings Wizard\')');
	}

	/**
	 * Test the settings wizard
	 */
	function testSettingsWizardContext() {
		$this->goToHostedJournals();

		$this->waitForElementPresent($selector = 'css=a.show_extras');
		$this->click($selector);
		$this->waitForElementPresent($selector = 'link=Settings wizard');
		$this->clickAndWait($selector);
		$this->waitForElementPresent('css=h1:contains(\'Settings Wizard\')');

		$this->setInputValue('[name="abbreviation-en_US"]', 'publicknowledge');
		$this->click('css=#journal button:contains(\'Save\')');
		$this->waitForTextPresent('Journal of Public Knowledge was edited successfully.');

		$this->click('css=a:contains(\'Appearance\')');
		$this->waitForElementPresent($selector = 'css=#appearance button:contains(\'Save\')');
		$this->click($selector);
		$this->waitForTextPresent('The theme has been updated.');

		$this->click('css=a:contains(\'Languages\')');
		$this->waitForElementPresent($selector = 'css=input#select-cell-fr_CA-contextPrimary');
		$this->click($selector);
		$this->waitForTextPresent('Locale settings saved.');
		$this->click('css=input#select-cell-en_US-contextPrimary');

		$this->click('css=a:contains(\'Search Indexing\')');
		$this->setInputValue('[name="searchDescription-en_US"]', 'The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.');
		$this->setInputValue('[name="customHeaders-en_US"]', '<meta name="pkp" content="Test metatag.">');
		$this->click('css=#search-indexing button:contains(\'Save\')');
		$this->waitForTextPresent('The search engine index settings have been updated.');

		// Test the form tooltip
		$this->click('css=label[for="searchIndexing-searchDescription-control-en_US"] + button.tooltipButton');
		$this->waitForElementPresent('css=div[id^="tooltip_"]:contains(\'Provide a brief description\')');
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

		// Settings > Journal > Contact
		$this->click('link=Contact');

		// Required fields
		$this->waitForElementPresent($selector = 'css=#contact button:contains(\'Save\')');
		$this->click($selector);
		$this->waitForElementPresent('css=#contact-contactName-error:contains(\'This field is required.\')');
		$this->waitForElementPresent('css=#contact-contactEmail-error:contains(\'This field is required.\')');
		$this->waitForElementPresent('css=#contact-mailingAddress-error:contains(\'This field is required.\')');
		$this->waitForElementPresent('css=#contact-supportName-error:contains(\'This field is required.\')');
		$this->waitForElementPresent('css=#contact-supportEmail-error:contains(\'This field is required.\')');

		$this->setInputValue('[name="contactName"]', 'Ramiro Vaca');
		$this->setInputValue('[name="mailingAddress"]', "123 456th Street\nBurnaby, British Columbia\nCanada");
		$this->setInputValue('[name="supportName"]', 'Ramiro Vaca');

		// Invalid emails
		$this->setInputValue('[name="contactEmail"]', 'rvacamailinator.com');
		$this->setInputValue('[name="supportEmail"]', 'rvacamailinator.com');
		$this->click($selector);
		$this->waitForElementPresent('css=#contact-contactEmail-error:contains(\'This is not a valid email address.\')');
		$this->waitForElementPresent('css=#contact-supportEmail-error:contains(\'This is not a valid email address.\')');

		$this->setInputValue('[name="contactEmail"]', 'rvaca@mailinator.com');
		$this->setInputValue('[name="supportEmail"]', 'rvaca@mailinator.com');
		$this->click($selector);
		$this->waitForTextPresent('The contact details for this journal have been updated.');
	}

	/**
	 * Helper function to go to the hosted journals page
	 */
	function goToHostedJournals() {
		$this->open(self::$baseUrl);
		$this->waitForElementPresent('link=Administration');
		$this->click('link=Administration');
		$this->waitForElementPresent('link=Hosted Journals');
		$this->click('link=Hosted Journals');
	}
}
