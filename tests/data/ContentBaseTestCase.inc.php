<?php

/**
 * @file tests/data/ContentBaseTestCase.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContentBaseTestCase
 * @ingroup tests_data
 *
 * @brief Data build suite: Base class for content creation tests
 */

import('lib.pkp.tests.data.PKPContentBaseTestCase');

class ContentBaseTestCase extends PKPContentBaseTestCase {
	/**
	 * Handle any section information on submission step 1
	 * @return string
	 */
	protected function _handleSection($data) {
		$section = 'Articles'; // Default
		if (isset($data['section'])) $section = $data['section'];

		// Page 1
		$this->waitForElementPresent('id=sectionId');
		$this->select('id=sectionId', 'label=' . $this->escapeJS($section));
	}

	/**
	 * Send to review.
	 */
	protected function sendToReview() {
		$this->waitForElementPresent('//span[text()=\'Send to External Review\']/..');
		$this->click('//span[text()=\'Send to External Review\']/..');
		$this->waitForElementPresent('//form[@id=\'initiateReview\']//input[@type=\'checkbox\']');
		$this->waitForElementPresent('//form[@id=\'initiateReview\']//span[text()=\'Send to External Review\']/..');
		$this->click('//form[@id=\'initiateReview\']//span[text()=\'Send to External Review\']/..');
		$this->waitForElementNotPresent('css=.ui-widget-overlay');
	}
}
