<?php

/**
 * @file tests/data/60-content/CkwantesSubmissionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CkwantesSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.data.ContentBaseTestCase');

class CkwantesSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'ckwantes',
			'firstName' => 'Catherine',
			'lastName' => 'Kwantes',
			'affiliation' => 'University of Windsor',
			'country' => 'Canada',
			'roles' => array('Author'),
		));

		$title = 'The Facets Of Job Satisfaction: A Nine-Nation Comparative Study Of Construct Equivalence';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'Archival data from an attitude survey of employees in a single multinational organization were used to examine the degree to which national culture affects the nature of job satisfaction. Responses from nine countries were compiled to create a benchmark against which nations could be individually compared. Factor analysis revealed four factors: Organizational Communication, Organizational Efficiency/Effectiveness, Organizational Support, and Personal Benefit. Comparisons of factor structures indicated that Organizational Communication exhibited the most construct equivalence, and Personal Benefit the least. The most satisfied employees were those from China, and the least satisfied from Brazil, consistent with previous findings that individuals in collectivistic nations report higher satisfaction. The research findings suggest that national cultural context exerts an effect on the nature of job satisfaction.',
			'keywords' => array(
				'employees',
				'survey',
			),
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);

		// Remove auto-assigned David Buskins, leaving Stephanie Berardo
		$this->clickAndWait('//td[contains(text(),\'David Buskins\')]/..//a[text()=\'Delete\']');

		// Go to review
		$this->clickAndWait('link=Review');
		$this->assignReviewer('amccrae', 'Aisla McCrae');
		$this->assignReviewer('agallego', 'Adela Gallego');
		$this->recordEditorialDecision('Accept Submission');

		$this->assignCopyeditor('Fritz, Maria');

		$this->logOut();
	}
}
