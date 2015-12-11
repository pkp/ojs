<?php

/**
 * @file tests/data/60-content/RyagnaSubmissionTest.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RyagnaSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class RyagnaSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'ryagna',
			'firstName' => 'Raj',
			'lastName' => 'Yagna',
			'affiliation' => 'Bangalore University',
			'country' => 'India',
			'roles' => array('Author'),
		));

		$title = 'Whistleblowing: an ethical dilemma';
		$this->createSubmission(array(
			'title' => $title,
			'abstract' => 'Because most organizations depend on computer systems that electronically store important data to perform crucial business functions, the integrity of these information systems is paramount. Securing company systems, however, is not always an easy task. More sophisticated systems often provide widespread access to computer resources and increased user knowledge, which may lead to added difficulties in maintaining security. This paper explores whistleblowing employees\' exposing illegal or unethical computer practices taking place in the organization as a method of computer security and the support for whistleblowing found in codes of ethical conduct formulated by professional societies.',
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
