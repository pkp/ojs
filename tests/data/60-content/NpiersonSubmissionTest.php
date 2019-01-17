<?php

/**
 * @file tests/data/60-content/NpiersonSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NpiersonSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class NpiersonSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'npierson',
			'firstName' => 'Narciso',
			'lastName' => 'Pierson',
			'affiliation' => 'Keele University',
			'country' => 'United Kingdom',
			'roles' => array('Author'),
		));

		$this->createSubmission(array(
			'title' => 'Cyberspace Versus Citizenship: IT and emerging non space communities',
			'abstract' => 'ABSTRACT GOES HERE',
		));

		$this->logOut();
	}
}
