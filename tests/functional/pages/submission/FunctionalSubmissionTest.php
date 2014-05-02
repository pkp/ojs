<?php

/**
 * @file tests/functional/pages/submission/FunctionalSubmissionBaseTestCase.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalSubmissionBaseTestCase
 * @ingroup tests_functional_pages_submission
 *
 * @brief Test the submission process.
 */


import('lib.pkp.tests.functional.pages.submission.FunctionalSubmissionBaseTestCase');

class FunctionalSubmissionTest extends FunctionalSubmissionBaseTestCase {

	/**
	 * Test the submission process.
	 */
	public function testSubmitSubmission() {
		return $this->submitSubmission();
	}

	/**
	 * @see FunctionalSubmissionBaseTestCase::doActionsOnStep1()
	 */
	protected function doActionsOnStep1() {
		// Make sure the section element is there.
		$this->isElementPresent("css=div.section");

		// Select the section.
		$this->click("css=div.section.");
		$this->select("id=sectionId", "label=Articles");
	}
}
?>