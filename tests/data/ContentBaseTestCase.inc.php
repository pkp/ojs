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
		$this->waitForElementPresent('//span[text()=\'Complete\']/..');
		$this->click('//span[text()=\'Complete\']/..');
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
}
