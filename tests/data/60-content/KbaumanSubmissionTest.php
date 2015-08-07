<?php

/**
 * @file tests/data/60-content/KbaumanSubmissionTest.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class KbaumanSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class KbaumanSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'kbauman',
			'firstName' => 'Karen',
			'lastName' => 'Bauman',
			'affiliation' => 'Chapman University',
			'country' => 'United States',
			'roles' => array('Author'),
		));

		$title = 'Data Modelling and Conceptual Modelling: a comparative analysis of functionality and roles';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'This paper reviews the functionality associated "data modelling" and "conceptual modelling". The history and origins of each term are considered, together with the current interpretation of each term. The term "information modelling" is also taken into account. Alternative representation forms are presented and reviewed. The merit of diagrams as a basis for a dialogue with a subject area expert is indicated. The paper suggests that a clear distinction is needed between data modelling and conceptual modelling. Both analytic modelling and prescriptive modelling are reviewed. The requirements for a conceptual schema modelling facility over and above the functionality provided by currently available data modelling facilities are presented. The need is emphasized for a conceptual schema modelling facility to support a representation form easily assimilatable by a subject area expert not familiar with information system. Based on the distinctions made, the paper suggests a way in which a data modelling facility and a conceptual schema modelling facility can be positioned in an information systems life cycle.',
		));
		$this->logOut();

		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->waitForElementPresent($selector = 'css=[id^=expedite-button-]');
		$this->click($selector);
		$this->waitForElementPresent($selector='link=OK');
		$this->click($selector);
		$this->waitForElementPresent($selector = 'css=[id^=issueEntry-button-]');
		$this->click($selector);
		$this->waitForElementPresent($selector = '//a[@name=\'catalog\']');
		$this->click($selector);
		$this->waitForElementPresent($selector = 'id=issueId');
		$this->select($selector, 'Vol 1 No 1 (2014)');
		$this->waitForElementPresent($selector = '//button[text()=\'Save\']');
		$this->click($selector);
		$this->logOut();
	}
}
