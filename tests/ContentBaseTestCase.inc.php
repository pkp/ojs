<?php

/**
 * @file tests/ContentBaseTestCase.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContentBaseTestCase
 * @ingroup tests_data
 *
 * @brief Data build suite: Base class for content creation tests
 */

import('lib.pkp.tests.PKPContentBaseTestCase');

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;

class ContentBaseTestCase extends PKPContentBaseTestCase {
	/**
	 * Handle any section information on submission step 1
	 * @return string
	 */
	protected function _handleStep1($data) {
		$section = 'Articles'; // Default
		if (isset($data['section'])) $section = $data['section'];

		// Page 1
		$this->waitForElementPresent('id=sectionId');
		$this->select('id=sectionId', 'label=' . $this->escapeJS($section));
	}

	/**
	 * Get the submission element's name
	 * @return string
	 */
	protected function _getSubmissionElementName() {
		return 'Article Text';
	}

	/**
	 * Send to review.
	 */
	protected function sendToReview() {
		$this->waitForElementPresent($selector = 'css=[id^=externalReview-button-]');
		$this->click($selector);
		$this->waitForElementPresent('//form[@id=\'initiateReview\']//input[@type=\'checkbox\']');
		$this->waitForElementPresent($selector = '//form[@id=\'initiateReview\']//button[contains(., \'Send to Review\')]');
		$this->click($selector);
		self::$driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('div.pkp_modal_panel')));
	}

	/**
	 * Schedule for publication in an issue
	 */
	function publish($issueTitle) {
		$this->click('//button[@id="publication-button"]');
		$this->click('//button[@id="issue-button"]');
		$this->select('id=journalEntry-issueId-control', 'value=' . $this->escapeJS($issueTitle));
		$this->click('//div[@id="issue"]//button[contains(text(),"Save")]');
		$this->waitForTextPresent('The journal entry details have been updated.');
		sleep(1);
		$this->waitForElementPresent($selector = '//div[@id="publication"]//button[contains(text(),"Schedule For Publication")]');
		$this->click($selector);
		$this->waitForTextPresent('All publication requirements have been met. Are you sure you want to publish this?');
		$this->click('//div[@class="pkpWorflow__publishModal"]//button[contains(text(),"Publish")]');
	}

	/**
	 * Check if a submission appears in an issue
	 */
	function isInIssue($submissionTitle, $issueTitle) {
		$this->open(self::$baseUrl);
		$this->waitForElementPresent($selector = '//a[contains(text(), "Archives")]');
		$this->click($selector);
		$this->waitForElementPresent($selector = '//a[contains(text(),' . $this->quoteXpath($issueTitle) . ')]');
		$this->click($selector);
		$this->waitForElementPresent('//a[contains(text(),' . $this->quoteXpath($submissionTitle) . ')]');
	}
}
