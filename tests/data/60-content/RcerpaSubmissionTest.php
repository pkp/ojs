<?php

/**
 * @file tests/data/60-content/RcerpaSubmissionTest.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RcerpaSubmissionTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

import('tests.ContentBaseTestCase');

class RcerpaSubmissionTest extends ContentBaseTestCase {
	/**
	 * Create a submission.
	 */
	function testSubmission() {
		$this->register(array(
			'username' => 'rcerpa',
			'firstName' => 'Roy',
			'lastName' => 'Cerpa',
			'affiliation' => 'Universidade Aberta Lisboa',
			'country' => 'Portugal',
			'roles' => array('Author'),
		));

		$title = 'A Review of Object Oriented Database Concepts and their Implementation';
		$this->createSubmission(array(
			'section' => 'Reviews',
			'title' => $title,
			'abstract' => 'Object Oriented design and databases has attracted a great deal of attention in recent years. This article outlines and discusses the semantic data principles used inter alia in understanding Object Oriented concepts. To illustrate and lend substance to this discussion a list is presented of OODBMS implementations. Their weaknesses and strengths are analysed. And their suitability for specific applications is assessed. Finally we offer some conclusions about research in this area and the directions in which further development should proceed.',
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
