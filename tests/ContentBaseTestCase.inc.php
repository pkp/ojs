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
}
