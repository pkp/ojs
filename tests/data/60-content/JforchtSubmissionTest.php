<?php

/**
 * @file tests/data/60-content/JforchtSubmissionTest.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JforchtSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class JforchtSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'jforcht',
			'firstName' => 'June',
			'lastName' => 'Forcht',
			'affiliation' => 'Colorado State University',
			'country' => 'United States',
			'roles' => array('Author'),
		));

		$title = 'Cyclomatic Complexity: theme and variations';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'Focussing on the "McCabe family" of measures for the decision/logic structure of a program, leads to an evaluation of extensions to modularization, nesting and, potentially, to object-oriented program structures. A comparison of rated, operating and essential complexities of programs suggests two new metrics: "inessential complexity" as a measure of unstructuredness and "product complexity" as a potential objective measure of structural complexity. Finally, nesting and abstraction levels are considered, especially as to how metrics from the "McCabe family" might be applied in an object-oriented systems development environment.',
		));
		$this->logOut();

		$this->findSubmissionAsEditor('dbarnes', null, $title);
		$this->waitForElementPresent($selector = 'css=[id^=expedite-button-]');
		$this->click($selector);
		$this->waitForElementPresent($selector = 'id=issueId');
		$this->select($selector, 'Vol 1 No 1 (2014)');
		$this->waitForElementPresent($selector = '//button[text()=\'Save\']');
		$this->click($selector);
		$this->waitForElementNotPresent('css=div.pkp_modal_panel');
		$this->logOut();
	}
}
