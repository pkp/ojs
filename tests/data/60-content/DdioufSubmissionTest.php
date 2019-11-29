<?php

/**
 * @file tests/data/60-content/DdioufSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DdioufSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class DdioufSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'ddiouf',
			'givenName' => 'Diaga',
			'familyName' => 'Diouf',
			'affiliation' => 'Alexandria University',
			'country' => 'Egypt',
		));

		$title = 'Genetic transformation of forest trees';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'In this review, the recent progress on genetic transformation of forest trees were discussed. Its described also, different applications of genetic engineering for improving forest trees or understanding the mechanisms governing genes expression in woody plants.',
		));

		$this->logOut();
	}
}
