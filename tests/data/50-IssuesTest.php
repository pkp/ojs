<?php

/**
 * @file tests/data/50-IssuesTest.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssuesTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create issues
 */

import('lib.pkp.tests.WebTestCase');

use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverBy;

class IssuesTest extends WebTestCase {
	/**
	 * Configure section editors
	 */
	function testCreateIssues() {
		$this->open(self::$baseUrl);

		// Management > Issues
		$actions = new WebDriverActions(self::$driver);
		$actions->moveToElement($this->waitForElementPresent('css=ul#navigationUser>li.profile>a'))
			->click($this->waitForElementPresent('//ul[@id="navigationUser"]//a[contains(text(),"Dashboard")]'))
			->perform();
		$actions = new WebDriverActions(self::$driver);
		$actions->moveToElement($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[text()="Issues"]'))
			->click($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[text()="Future Issues"]'))
			->perform();

		// Create issue
		$this->waitForElementPresent($selector='css=[id^=component-grid-issues-futureissuegrid-addIssue-button-]');
		$this->click($selector);
		$this->waitForElementPresent($selector='css=[id^=volume-]');
		$this->type($selector, '1');
		$this->type('css=[id^=number-]', '2');
		$this->type('css=[id^=year-]', '2014');
		$this->click('id=showTitle');
		$this->click('//button[text()=\'Save\']');
		self::$driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('div.pkp_modal_panel')));

		// Publish first issue
		$this->waitForElementPresent($selector = 'css=a.show_extras');
		$this->click($selector);
		$this->waitForElementPresent($selector='//a[text()=\'Publish Issue\']');
		$this->click($selector);
		$this->waitForElementPresent($selector='css=[id^=submitFormButton-]');
		$this->click($selector);
		self::$driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('div.pkp_modal_panel')));
	}
}
