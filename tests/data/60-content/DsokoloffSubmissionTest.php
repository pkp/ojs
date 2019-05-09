<?php

/**
 * @file tests/data/60-content/DsokoloffSubmissionTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DsokoloffSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class DsokoloffSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'dsokoloff',
			'givenName' => 'Domatilia',
			'familyName' => 'Sokoloff',
			'affiliation' => 'University College Cork',
			'country' => 'Ireland',
		));

		$title = 'Developing efficacy beliefs in the classroom';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'A major goal of education is to equip children with the knowledge, skills and self-belief to be confident and informed citizens - citizens who continue to see themselves as learners beyond graduation. This paper looks at the key role of nurturing efficacy beliefs in order to learn and participate in school and society. Research findings conducted within a social studies context are presented, showing how strategy instruction can enhance self-efficacy for learning. As part of this research, Creative Problem Solving (CPS) was taught to children as a means to motivate and support learning. It is shown that the use of CPS can have positive effects on self-efficacy for learning, and be a valuable framework to involve children in decision-making that leads to social action. Implications for enhancing self-efficacy and motivation to learn in the classroom are discussed.',
			'keywords' => array(
				'education',
				'citizenship',
			),
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->sendToReview();
		$this->waitForElementPresent('//a[contains(text(), \'Review\')]/*[contains(text(), \'Initiated\')]');
		$this->assignReviewer('Paul Hudson');
		$this->assignReviewer('Aisla McCrae');
		$this->assignReviewer('Adela Gallego');
		$this->logOut();
		$this->performReview('phudson', null, $title, 'Decline Submission');
	}
}
