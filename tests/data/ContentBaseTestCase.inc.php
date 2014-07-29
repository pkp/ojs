<?php

/**
 * @file tests/data/ContentBaseTestCase.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContentBaseTestCase
 * @ingroup tests_data
 *
 * @brief Data build suite: Base class for content creation tests
 */

import('lib.pkp.tests.WebTestCase');

class ContentBaseTestCase extends WebTestCase {
	/**
	 * Create a submission with the supplied data.
	 */
	protected function createSubmission($data) {
		// Check that the required parameters are provided
		foreach (array(
			'title',
		) as $paramName) {
			$this->assertTrue(isset($data[$paramName]));
		}

		$data = array_merge(array(
			'section' => 'Articles',
			'file' => getenv('DUMMYFILE'),
			'fileTitle' => $data['title'],
			'keywords' => array(),
			'additionalAuthors' => array(),
		), $data);

		sleep(1); // Avoid apparent race condition

		// Find the "start a submission" button
		$this->click('link=Dashboard');
		$this->waitForElementPresent('//span[starts-with(., \'Start a New Submission\')]/..');
		$this->click('//span[starts-with(., \'Start a New Submission\')]/..');

		// Page 1
		$this->waitForElementPresent('id=sectionId');
		$this->select('id=sectionId', 'label=' . htmlspecialchars($data['section']));
		// By default there are 6 checklist items; check them.
		for ($i=0; $i<6; $i++) $this->click('id=checklist-' . $i);
		$this->click('css=[id^=submitFormButton-]');

		// Page 2
		$this->waitForElementPresent('id=genreId');
		$this->select('id=genreId', 'label=Submission');
		$this->uploadFile($data['file']);
		$this->click('id=continueButton');
		$this->waitForElementPresent('css=[id^=name-]');
		$this->type('css=[id^=name-]', $data['fileTitle']);
		$this->runScript('$(\'#metadataForm\').valid();');
		$this->click('//span[text()=\'Continue\']/..');
		$this->waitJQuery();
		$this->waitForElementPresent('//span[text()=\'Complete\']/..');
		$this->click('//span[text()=\'Complete\']/..');
		$this->waitJQuery();
		$this->waitForElementPresent('//span[text()=\'Save and continue\']/..');
		$this->click('//span[text()=\'Save and continue\']/..');

		// Page 3
		$this->waitForElementPresent('css=[id^=title-]');
		$this->type('css=[id^=title-]', $data['title']);
		if (isset($data['abstract'])) $this->typeTinyMCE('abstract', $data['abstract']);
		foreach ($data['keywords'] as $tag) $this->addTag('keyword', $tag);

		foreach ($data['additionalAuthors'] as $authorData) {
			$this->addAuthor($authorData);
		}

		// Finish
		$this->waitForElementPresent('//span[text()=\'Finish Submission\']/..');
		$this->click('//span[text()=\'Finish Submission\']/..');
		$this->waitForText('css=div.pkp_controllers_modal_titleBar > h2', 'Confirm');
		$this->waitForElementPresent("//span[text()='OK']/..");
		$this->click("//span[text()='OK']/..");
		$this->waitForText('css=#ui-tabs-4 > h2', 'Submission complete');
		$this->waitJQuery();
	}

	/**
	 * Add an author to the submission's author list.
	 * @param $data array
	 */
	protected function addAuthor($data) {
		// Check that the required parameters are provided
		foreach (array(
			'firstName', 'lastName', 'email', 'country',
		) as $paramName) {
			$this->assertTrue(isset($data[$paramName]));
		}

		$data = array_merge(array(
			'role' => 'Author',
		), $data);

		$this->click('css=[id^=component-grid-users-author-authorgrid-addAuthor-button-]');
		$this->waitForElementPresent('css=[id^=firstName-]');
		$this->type('css=[id^=firstName-]', $data['firstName']);
		$this->type('css=[id^=lastName-]', $data['lastName']);
		$this->select('id=country', $data['country']);
		$this->type('css=[id^=email-]', $data['email']);
		if (isset($data['affiliation'])) $this->type('css=[id^=affiliation-]', $data['affiliation']);
		$this->click('//label[text()=\'' . htmlspecialchars($data['role']) . '\']');
		$this->click('//span[text()=\'Save\']/..');
		$this->waitForElementNotPresent('css=.ui-widget-overlay');
		$this->waitJQuery();
	}

	/**
	 * Log in as an Editor and find the specified submission.
	 * @param $title string
	 */
	protected function findSubmissionAsEditor($title) {
		$this->logIn('dbarnes', 'dbarnesdbarnes');
		$this->waitForElementPresent('link=Dashboard');
		$this->click('link=Dashboard');
		$this->waitForElementPresent('xpath=(//a[contains(text(),\'Submissions\')])[2]');
		$this->click('xpath=(//a[contains(text(),\'Submissions\')])[2]');
		$this->waitForElementPresent('//a[text()=\'' . $title . '\']');
		$this->click('//a[text()=\'' . $title . '\']');
	}

	/**
	 * Record an editorial decision
	 * @param $decision string
	 */
	protected function recordEditorialDecision($decision) {
		$this->waitForElementPresent('//span[text()=\'' . $decision . '\']/..');
		$this->click('//span[text()=\'' . $decision . '\']/..');
		$this->waitForElementPresent('//span[text()=\'Record Editorial Decision\']/..');
		$this->click('//span[text()=\'Record Editorial Decision\']/..');
		$this->waitForElementNotPresent('css=.ui-widget-overlay');
	}

	/**
	 * Send to review.
	 */
	protected function sendToReview() {
		$this->waitForElementPresent('//span[text()=\'Send to External Review\']/..');
		$this->click('//span[text()=\'Send to External Review\']/..');
		$this->waitForElementPresent('//form[@id=\'initiateReview\']//input[@type=\'checkbox\']');
		$this->waitForElementPresent('//form[@id=\'initiateReview\']//span[text()=\'Send to External Review\']/..');
		$this->click('//form[@id=\'initiateReview\']//span[text()=\'Send to External Review\']/..');
		$this->waitForElementNotPresent('css=.ui-widget-overlay');
		$this->waitJQuery();
	}

	/**
	 * Assign a participant
	 * @param $role string
	 * @param $name string
	 */
	protected function assignParticipant($role, $name) {
		$this->waitForElementPresent('css=[id^=component-grid-users-stageparticipant-stageparticipantgrid-requestAccount-button-]');
		$this->click('css=[id^=component-grid-users-stageparticipant-stageparticipantgrid-requestAccount-button-]');
		$this->waitJQuery();
		$this->select('id=userGroupId', 'label=' . $role);
		$this->waitForElementPresent('//select[@name=\'userId\']//option[text()=\'' . $name . '\']');
		$this->select('id=userId', 'label=' . $name);
		$this->click('//span[text()=\'OK\']/..');
		$this->waitForText('css=div.ui-pnotify-text', 'User added as a stage participant.');
		$this->waitJQuery();
	}
}
