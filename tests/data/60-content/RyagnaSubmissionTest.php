<?php

/**
 * @file tests/data/60-content/RyagnaSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RyagnaSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class RyagnaSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'ryagna',
			'firstName' => 'Raj',
			'lastName' => 'Yagna',
			'affiliation' => 'Bangalore University',
			'country' => 'India',
			'roles' => array('Author'),
		));

		$this->createSubmission(array(
			'title' => 'Whistleblowing: an ethical dilemma',
			'abstract' => 'ABSTRACT GOES HERE',
		));

		$this->logOut();
	}
}
