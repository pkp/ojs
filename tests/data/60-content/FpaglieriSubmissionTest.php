<?php

/**
 * @file tests/data/60-content/FpaglieriSubmissionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
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

		$title = 'Hansen & Pinto: Reason Reclaimed';
		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => $title,
			'abstract' => 'None.',
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);

		// Go to review
		$this->clickAndWait('link=Review');
		$this->assignReviewer('jjanssen', 'Julie Janssen');
		$this->assignReviewer('agallego', 'Adela Gallego');
		$this->recordEditorialDecision('Accept Submission');

		$this->assignCopyeditor('Vogt, Sarah');
		$this->assignLayoutEditor('Hellier, Stephen');
		$this->assignProofreader('Kumar, Sabine');
		$this->logOut();
	}
}
