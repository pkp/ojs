<?php

/**
 * @file tests/data/20-CreateJournalTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
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
		parent::logIn('admin', 'adminadmin');
	}

	/**
	 * Create and set up test data journal.
	 */
	function testCreateJournal() {
		$this->open(self::$baseUrl);
		$this->waitForElementPresent('link=User Home');
		$this->clickAndWait('link=User Home');
		$this->waitForElementPresent('link=Site Administrator');
		$this->clickAndWait('link=Site Administrator');
		$this->waitForElementPresent('link=Hosted Journals');
		$this->clickAndWait('link=Hosted Journals');
		$this->waitForElementPresent('link=Create Journal');
		$this->click('link=Create Journal');

		// Enter journal data (English / non-localized)
		$this->waitForElementPresent('id=title');
		$this->type('id=title', 'Journal of Public Knowledge');
		$this->typeTinyMCE('description', 'The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.');
		$this->type('id=journalPath', 'publicknowledge');

		// Flip to French and enter data there
		$this->select('id=formLocale', 'value=fr_CA');
		$this->clickAndWait('css=#languageSelector > input.button');
		$this->type('id=title', 'Journal de la connaissance du public');
		$this->typeTinyMCE('description', 'Le Journal de Public Knowledge est une publication trimestrielle évaluée par les pairs sur le thème de l\'accès du public à la science.');
		$this->clickAndWait('id=saveJournal');

		// Turn on single-journal redirect
		$this->clickAndWait('link=Site Administration');
		$this->clickAndWait('link=Site Settings');
		$this->select('id=redirect', 'label=Journal of Public Knowledge');
		$this->clickAndWait('css=input.button.defaultButton');
	}

	/**
	 * Set up the test journal.
	 */
	function testSetupJournal() {
		$this->open(self::$baseUrl);

		// Setup
		$this->clickAndWait('link=User Home');
		$this->clickAndWait('link=Setup');

		// Page 1
    		$this->waitForElementPresent('id=initials');
    		$this->type('id=initials', 'PK');
		$this->type('id=contactEmail', 'rvaca@mailinator.com');
		$this->type('id=contactName', 'Ramiro Vaca');
		$this->type('id=supportEmail', 'rvaca@mailinator.com');
		$this->type('id=supportName', 'Ramiro Vaca');
		$this->clickAndWait('css=input.button.defaultButton');

		// Page 3
		// - Enable keywords in metadata
		$this->clickAndWait('link=3. Submissions');
		$this->click('id=metaSubject');
		// - Set up permissions
		$this->click('id=copyrightHolderType-author');
		$this->click('id=copyrightYearBasis-article');
		$this->select('id=licenseURLSelect', 'label=CC Attribution-NonCommercial-NoDerivatives 4.0');
		// - Save changes
		$this->clickAndWait('css=input.button.defaultButton');

		// Page 4: Turn on optional roles
		$this->clickAndWait('link=4. Management');
		$this->click('id=useCopyeditors-1');
		$this->click('id=useLayoutEditors-1');
		$this->click('id=useProofreaders-1');
		$this->clickAndWait('css=input.button.defaultButton');
	}
}
