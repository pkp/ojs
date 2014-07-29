<?php

/**
 * @file tests/data/60-content/FpaglieriSubmissionTest.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FpaglieriSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class FpaglieriSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'fpaglieri',
			'firstName' => 'Fabio',
			'lastName' => 'Paglieri',
			'affiliation' => 'University of Rome',
			'country' => 'Italy',
			'roles' => array('Author'),
		));

		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => 'Hansen & Pinto: Reason Reclaimed',
			'abstract' => 'None.',
		));

		$this->logOut();
	}
}
