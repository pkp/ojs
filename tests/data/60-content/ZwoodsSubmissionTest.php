<?php

/**
 * @file tests/data/60-content/ZwoodsSubmissionTest.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ZwoodsSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class ZwoodsSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'zwoods',
			'firstName' => 'Zita',
			'lastName' => 'Woods',
			'affiliation' => 'CUNY',
			'country' => 'United States',
			'roles' => array('Author'),
		));

		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => 'Finocchiaro: Arguments About Arguments',
			'abstract' => 'None.',
		));

		$this->logOut();
	}
}
