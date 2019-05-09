<?php

/**
 * @file tests/data/60-content/ImportIssueTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ImportIssueTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Import an issue
 */

import('tests.ContentBaseTestCase');

use Facebook\WebDriver\Interactions\WebDriverActions;

class ImportIssueTest extends ContentBaseTestCase {
	/**
	 * Import an issue.
	 */
	function testImportIssue() {
		$this->logIn('dbarnes');

		$actions = new WebDriverActions(self::$driver);
		$actions->moveToElement($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[text()="Tools"]'))
			->click($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[text()="Import/Export"]'))
			->perform();

		$this->waitForElementPresent($selector='//a[text()=\'Native XML Plugin\']');
		$this->click($selector);

		$this->uploadFile(dirname(__FILE__) . '/issue.xml');
		$this->waitForElementPresent($selector='//input[@name=\'temporaryFileId\' and string-length(@value)>0]');
		$this->click('//form[@id=\'importXmlForm\']//button[starts-with(@id,\'submitFormButton-\')]');

		// Ensure that the import was listed as completed.
		$this->waitForElementPresent('//*[contains(text(),\'The import completed successfully.\')]//li[contains(text(),\'Vol 1 No 1\')]');

		$this->logOut();
	}
}
