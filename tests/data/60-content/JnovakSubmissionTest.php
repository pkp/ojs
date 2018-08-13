<?php

/**
 * @file tests/data/60-content/JnovakSubmissionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JnovakSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class JnovakSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'jnovak',
			'givenName' => 'John',
			'familyName' => 'Novak',
			'affiliation' => 'Aalborg University',
			'country' => 'Denmark',
		));

		$title = 'Condensing Water Availability Models to Focus on Specific Water Management Systems';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'The Texas Water Availability Modeling System is routinely applied in administration of the water rights permit system, regional and statewide planning, and an expanding variety of other endeavors. Modeling water management in the 23 river basins of the state reflects about 8,000 water right permits and 3,400 reservoirs. Datasets are necessarily large and complex to provide the decision-support capabilities for which the modeling system was developed. New modeling features are being added, and the different types of applications are growing. Certain applications are enhanced by simplifying the simulation input datasets to focus on particular water management systems. A methodology is presented for developing a condensed dataset for a selected reservoir system that reflects the impacts of all the water rights and accompanying reservoirs removed from the original complete dataset. A set of streamflows is developed that represents flows available to the selected system considering the effects of all the other water rights in the river basin contained in the original complete model input dataset that are not included in the condensed dataset. The methodology is applied to develop a condensed model of the Brazos River Authority reservoir system based on modifying the Texas Water Availability Modeling System dataset for the Brazos River Basin.',
			'keywords' => array(
				'water',
			),
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->sendToReview();
		$this->waitForElementPresent('//a[contains(text(), \'Review\')]/*[contains(text(), \'Initiated\')]');
		$this->assignReviewer('Aisla McCrae');
		$this->assignReviewer('Adela Gallego');
		$this->logOut();
		$this->performReview('amccrae', null, $title, 'Revisions Required');
		$this->performReview('agallego', null, $title, 'Resubmit for Review');
	}
}
