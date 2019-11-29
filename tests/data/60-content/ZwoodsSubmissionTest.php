<?php

/**
 * @file tests/data/60-content/ZwoodsSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ZwoodsSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class ZwoodsSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'zwoods',
			'givenName' => 'Zita',
			'familyName' => 'Woods',
			'affiliation' => 'CUNY',
			'country' => 'United States',
		));

		$title = 'Finocchiaro: Arguments About Arguments';
		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => $title,
			'abstract' => 'None.',
		));

		$this->logOut();
	}
}
