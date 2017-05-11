<?php

/**
 * @file tests/functional/LensFunctionalTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LensFunctionalTest
 * @package plugins.generic.staticPages
 *
 * @brief Functional tests for the static pages plugin.
 */

import('tests.ContentBaseTestCase');

class LensFunctionalTest extends ContentBaseTestCase {
	/**
	 * @copydoc WebTestCase::getAffectedTables
	 */
	protected function getAffectedTables() {
		return PKP_TEST_ENTIRE_DB;
	}

	/**
	 * Enable the plugin
	 */
	function testLens() {
		$this->logIn('dbarnes');

		$this->waitForElementPresent($selector='//a[text()=\'Import/Export\']');
		$this->click($selector);

		$this->waitForElementPresent($selector='//a[text()=\'Native XML Plugin\']');
		$this->click($selector);

		$this->uploadFile(dirname(__FILE__) . '/issue.xml');
		$this->waitForElementPresent($selector='//input[@name=\'temporaryFileId\' and string-length(@value)>0]');
		$this->click('//form[@id=\'importXmlForm\']//button[starts-with(@id,\'submitFormButton-\')]');

		// Ensure that the import was listed as completed.
		$this->waitForElementPresent('//*[contains(text(),\'The import completed successfully.\')]//li[contains(text(),\'Vol 1 No 3\')]');

		// View the associated issue
		$this->waitForElementPresent($selector='link=View Site');
		$this->clickAndWait($selector);
		$this->clickAndWait('link=Archives');
		$this->clickAndWait('link=Vol 1 No 3 (2014)');

		// Find the published XML galley
		$this->waitForElementPresent($selector='link=XML');
		$this->click($selector);

		// Ensure the article was rendered
		$this->waitForElementPresent('//span[contains(@class,\'title\') and contains(text(), \'Direct single molecule measurement of TCR triggering by agonist pMHC in living primary T cells\')]');

		$this->logOut();
	}
}
