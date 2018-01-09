<?php

/**
 * @file tests/data/60-content/ZwoodsSubmissionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
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

		$title = 'Finocchiaro: Arguments About Arguments';
		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => $title,
			'abstract' => 'None.',
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);

		// Send to Review
		$this->clickAndWait('link=Review');

		$this->assignReviewer('phudson', 'Paul Hudson');
		$this->assignReviewer('amccrae', 'Aisla McCrae');
		$this->recordEditorialDecision('Accept Submission');

		$this->clickAndWait('link=Editing');
		$this->assignCopyeditor('Vogt, Sarah');
		$this->logOut();
	}
}
