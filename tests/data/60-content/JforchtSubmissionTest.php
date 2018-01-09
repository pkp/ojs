<?php

/**
 * @file tests/data/60-content/JforchtSubmissionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JforchtSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class JforchtSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'jforcht',
			'firstName' => 'June',
			'lastName' => 'Forcht',
			'affiliation' => 'Colorado State University',
			'country' => 'United States',
			'roles' => array('Author'),
		));

		$this->createSubmission(array(
			'title' => 'Cyclomatic Complexity: theme and variations',
			'abstract' => 'ABSTRACT GOES HERE',
		));

		$this->logOut();
	}
}
