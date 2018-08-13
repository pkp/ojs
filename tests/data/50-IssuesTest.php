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

class IssuesTest extends WebTestCase {
	/**
	 * Configure section editors
	 */
	function testCreateIssues() {
		$this->open(self::$baseUrl);

		// Management > Issues
		$this->waitForElementPresent($selector='css=li.profile a:contains(\'Dashboard\')');
		$this->clickAndWait($selector);
		$this->waitForElementPresent($selector='link=Future Issues');
		$this->click($selector);

		// Create issue
		$this->waitForElementPresent($selector='css=[id^=component-grid-issues-futureissuegrid-addIssue-button-]');
		$this->click($selector);
		$this->waitForElementPresent($selector='css=[id^=volume-]');
		$this->type($selector, '1');
		$this->type('css=[id^=number-]', '2');
		$this->type('css=[id^=year-]', '2014');
		$this->click('id=showTitle');
		$this->click('//button[text()=\'Save\']');
		$this->waitJQuery();
		$this->waitForElementNotPresent('css=div.pkp_modal_panel'); // pkp/pkp-lib#655

		// Publish first issue
		$this->waitForElementPresent($selector='//a[text()=\'Publish Issue\']');
		$this->click($selector);
		$this->waitForElementPresent($selector='css=[id^=submitFormButton-]');
		$this->click($selector);
		$this->waitJQuery();
	}
}
