<?php

/**
 * @file tests/data/60-content/KalkhafajiSubmissionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class KalkhafajiSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class KalkhafajiSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'kalkhafaji',
			'givenName' => 'Karim',
			'familyName' => 'Al-Khafaji',
			'affiliation' => 'Stanford University',
			'country' => 'United States',
		));

		$this->createSubmission(array(
			'title' => 'Learning Sustainable Design through Service',
			'abstract' => 'Environmental sustainability and sustainable development principles are vital topics that engineering education has largely failed to address. Service-learning, which integrates social service into an academic setting, is an emerging tool that can be leveraged to teach sustainable design to future engineers. We present a model of using service-learning to teach sustainable design based on the experiences of the Stanford chapter of Engineers for a Sustainable World. The model involves the identification of projects and partner organizations, a student led, project-based design course, and internships coordinated with partner organizations. The model has been very successful, although limitations and challenges exist. These are discussed along with future directions for expanding the model.',
			'keywords' => array(
				'Development',
				'engineering education',
				'service learning',
				'sustainability',
			),
			'additionalAuthors' => array(
				array(
					'givenName' => 'Margaret',
					'familyName' => 'Morse',
					'country' => 'United States',
					'affiliation' => 'Stanford University',
					'email' => 'mmorse@mailinator.com',
				)
			),
		));

		$this->logOut();
	}
}
