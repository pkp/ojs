<?php

/**
 * @file tests/data/60-content/KbaumanSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class KbaumanSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class KbaumanSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'kbauman',
			'firstName' => 'Karen',
			'lastName' => 'Bauman',
			'affiliation' => 'Chapman University',
			'country' => 'United States',
			'roles' => array('Author'),
		));

		$this->createSubmission(array(
			'title' => 'Data Modelling and Conceptual Modelling: a comparative analysis of functionality and roles',
			'abstract' => 'ABSTRACT GOES HERE',
		));

		$this->logOut();
	}
}
