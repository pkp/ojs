<?php

/**
 * @file tests/data/60-content/BvemerSubmissionTest.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BvemerSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

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

		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => 'A Review of Information Systems and Corporate Memory: design for staff turn-over',
		));

		$this->logOut();
	}
}
