<?php

/**
 * @file tests/data/60-content/LkumiegaSubmissionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LkumiegaSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class LkumiegaSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'lkumiega',
			'givenName' => 'Lise',
			'familyName' => 'Kumiega',
			'affiliation' => 'University of Cape Town',
			'country' => 'South Africa',
		));

		$title = 'Hydrologic Connectivity in the Edwards Aquifer between San Marcos Springs and Barton Springs during 2009 Drought Conditions';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'The Edwards Aquifer serves as the primary water supply in South-Central Texas and is the source for several major springs. In developing a plan to protect endangered species immediately downstream of San Marcos Springs, questions have been raised regarding the established concept of a hydrologic divide between the San Antonio and Barton Springs segments of the Edwards Aquifer during drought conditions. To address these questions, a water-level data collection program and a hydrogeologic study was conducted. An analysis of groundwater-level data indicate that a groundwater divide exists in the vicinity of the surface drainage divide between Onion Creek and Blanco River during wet and normal hydrologic conditions. However, analysis of data collected during the 2009 drought suggests that the groundwater divide dissipated and no longer hydrologically separated the two segments. As a result, there is potential for groundwater to flow past San Marcos Springs toward Barton Springs during major droughts. The implications for this have bearings on the management and availability of groundwater in the Edwards Aquifer. Assessments of simulations from a numerical model suggest 5 cfs could be flowing past San Marcos toward Barton springs under drought conditions. The groundwater divide appears to be influenced by recharge along Onion Creek and Blanco River and appears to be vulnerable to extended periods of little or no recharge and extensive pumping in the vicinity of Kyle and Buda. The 2009 data set shows a very low gradient in the potentiometric surface between San Marcos Springs and Kyle with very little variation in levels between drought and non-drought periods. From Kyle toward Barton Springs, the potentiometric surface slopes significantly to the north and has dramatic changes in levels between drought and non-drought periods. The source and nature of the discontinuity of the change in potentiometric gradients and dynamic water level response at Kyle is unknown. Structural influences or hydraulic properties inherent in the aquifer could be the cause of this discontinuity and may also influence the degree of hydrologic connection between San Marcos and Barton Springs. Rapid population growth and increased water demands in the Kyle and Buda areas necessitates a continual groundwater level monitoring program between San Marcos Springs and Buda to provide data for future hydrogeologic and trend analyses.',
			'keywords' => array(
				'water',
			),
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->sendToReview();
		$this->waitForElementPresent('//a[contains(text(), \'Review\')]/*[contains(text(), \'Initiated\')]');
		$this->assignReviewer('Julie Janssen');
		$this->assignReviewer('Aisla McCrae');
		$this->assignReviewer('Adela Gallego');
		$this->logOut();
		$this->performReview('jjanssen', null, $title, 'Revisions Required');
		$this->performReview('amccrae', null, $title, 'Revisions Required');
		$this->performReview('agallego', null, $title, 'Resubmit for Review');
		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->recordEditorialDecision('Request Revisions');
		$this->logOut();
	}
}
