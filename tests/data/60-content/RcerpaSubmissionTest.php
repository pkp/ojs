<?php

/**
 * @file tests/data/60-content/RcerpaSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RcerpaSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class RcerpaSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'rcerpa',
			'firstName' => 'Roy',
			'lastName' => 'Cerpa',
			'affiliation' => 'Universidade Aberta Lisboa',
			'country' => 'Portugal',
			'roles' => array('Author'),
		));

		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => 'A Review of Object Oriented Database Concepts and their Implementation',
		));

		$this->logOut();
	}
}
