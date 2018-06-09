<?php

/**
 * @file tests/data/60-content/CcorinoSubmissionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CcorinoSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class CcorinoSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'ccorino',
			'givenName' => 'Carlo',
			'familyName' => 'Corino',
			'affiliation' => 'University of Bologna',
			'country' => 'Italy',
		));

		$title = 'The influence of lactation on the quantity and quality of cashmere production';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'The effects of pressed beet pulp silage (PBPS) replacing barley for 10% and 20% (DM basis) were studied on heavy pigs fed dairy whey-diluted diets. 60 Hypor pigs (average initial weight of 28 kg), 30 barrows and 30 gilts, were homogeneously allocated to three exper- imental groups: T1 (control) in which pigs were fed a traditional sweet whey- diluted diet (the ratio between whey and dry matter was 4.5/1); T2 in which PBPS replaced barley for 10% (DM basis) during a first period (from the beginning to the 133rd day of trial) and thereafter for 20% (DM basis); T3 in which PBPS replaced barley for 20% (DM basis) throughout the experimental period. In diets T2 and T3 feed was dairy whey-diluted as in group T1. No significant (P>0.05) differences were observed concerning growth parameters (ADG and FCR). Pigs on diets contain- ing PBPS showed significantly higher (P<0.05) percentages of lean cuts and lower percentages of fat cuts. On the whole, ham weight losses during seasoning were moderate but significantly (P<0.05) more marked for PBPS-fed pigs as a prob- able consequence of their lower adiposity degree. Fatty acid composition of ham fat was unaffected by diets. With regard to m. Semimembranosus colour, pigs receiving PBPS showed lower (P<0.05) "L", "a" and "Chroma" values. From an economical point of view it can be concluded that the use of PBPS (partially replacing barley) and dairy whey in heavy pig production could be of particular interest in areas where both these by products are readily available.',
			'keywords' => array(
				'pigs',
				'food security',
			),
		));

		$this->logOut();
		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->sendToReview();
		$this->waitForElementPresent('//a[contains(text(), \'Review\')]/*[contains(text(), \'Initiated\')]');
		// Assign a recommendOnly section editor
		$this->assignParticipant('Section editor', 'Minoti Inoue', true);
		$this->logOut();
		// Find the submission as the section editor
		$username = 'minoue';
		$password = $username . $username;
		$this->logIn($username, $password);
		$xpath = '//div[contains(text(),' . $this->quoteXPath($title) . ')]';
		$this->waitForElementPresent($xpath);
		$this->click($xpath);
		// Recommend
		$this->recordEditorialRecommendation('Accept Submission');
		$this->logOut();
		// Log in as editor and see the existing recommendation
		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->waitForText('css=div.pkp_workflow_recommendations', 'Recommendations: Accept Submission');
		$this->logOut();
	}
}
