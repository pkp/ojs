<?php

/**
 * @file tests/data/60-content/BvemerSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BvemerSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class BvemerSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'bvemer',
			'firstName' => 'Brian',
			'lastName' => 'Vemer',
			'affiliation' => 'University of Oslo',
			'country' => 'Norway',
			'roles' => array('Author'),
		));

		$title = 'A Review of Information Systems and Corporate Memory: design for staff turn-over';
		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => $title,
			'keywords' => array(
				'information technology',
				'knowledge preservation',
			),
		));
		$this->logOut();

		$this->findSubmissionAsEditor('dbarnes', null, $title);

		// Go to review; accept submission
		$this->clickAndWait('link=Review');
		$this->recordEditorialDecision('Accept Submission');

		// Go to editing
		$this->clickAndWait('link=Editing');

		// Upload a galley file
		$this->click('id=layoutFileTypeGalley');
		$this->attachFile('name=layoutFile', "file://" . getenv('DUMMYFILE'));
		$this->clickAndWait('//input[@name=\'layoutFile\']/..//input[@value=\'Upload\']');
		$this->clickAndWait('//input[@value=\'Save\']');

		// Schedule for an issue
		$this->select('id=issueId', 'label=Vol 1, No 1 (2014)');
		$this->clickAndWait('//div[@id=\'scheduling\']//input[@value=\'Record\']');

		$this->logOut();
	}
}
