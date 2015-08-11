<?php

/**
 * @file tests/data/60-content/BvemerSubmissionTest.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BvemerSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class BvemerSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'bvemer',
			'firstName' => 'Brian',
			'lastName' => 'Vemer',
			'affiliation' => 'University of Oslo',
			'country' => 'Norway',
			'roles' => array('Author'),
		));

		$title = 'A Review of Information Systems and Corporate Memory: design for staff turn-over';
		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => $title,
		));

		$this->logOut();

		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->waitForElementPresent($selector = 'css=[id^=expedite-button-]');
		$this->click($selector);
		$this->waitForElementPresent($selector = 'id=issueId');
		$this->select($selector, 'Vol 1 No 1 (2014)');
		$this->waitForElementPresent($selector = '//button[text()=\'Save\']');
		$this->click($selector);
		$this->waitJQuery();
		$this->logOut();
	}
}
