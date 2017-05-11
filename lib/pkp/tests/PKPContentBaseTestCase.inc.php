<?php

/**
 * @file tests/PKPContentBaseTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPContentBaseTestCase
 * @ingroup tests_data
 *
 * @brief Base class for content-based tests (PKP base)
 */

import('lib.pkp.tests.WebTestCase');

define('DUMMY_PDF', 0);
define('DUMMY_ZIP', 1);

abstract class PKPContentBaseTestCase extends WebTestCase {
	/**
	 * Handle any section information on submission step 1
	 * @return string
	 */
	protected function _handleStep1($data) {
	}

	/**
	 * Handle any section information on submission step 3
	 * @return string
	 */
	protected function _handleStep3($data) {
	}

	/**
	 * Get the number of items in the default submission checklist
	 * @return int
	 */
	abstract protected function _getChecklistLength();

	/**
	 * Get the submission submission element's name
	 * @return string
	 */
	abstract protected function _getSubmissionElementName();

	/**
	 * Create a submission with the supplied data.
	 * @param $data array Associative array of submission information
	 * @param $location string Whether or not the submission wll be created
	 *   from the frontend or backend
	 */
	protected function createSubmission($data, $location = 'frontend') {
		// Check that the required parameters are provided
		foreach (array(
			'title',
		) as $paramName) {
			$this->assertTrue(isset($data[$paramName]));
		}

		$data = array_merge(array(
			'files' => array(
				array(
					'file' => DUMMY_PDF,
					'fileTitle' => $data['title']
				)
			),
			'keywords' => array(),
			'additionalAuthors' => array(),
		), $data);

		// Find the "Make a New Submission" link
		if ($location == 'frontend') {
			$this->waitForElementPresent($selector='//a[contains(text(), \'Make a New Submission\')]');
		} else {
			$this->waitForElementPresent($selector='//button[starts-with(., \'New Submission\')]');
		}
		$this->click($selector);

		// Check the default checklist items.
		$this->waitForElementPresent('id=checklist-0');
		for ($i=0; $i<$this->_getChecklistLength(); $i++) {
			$id = 'checklist-' . $i;
			if ($this->getXpathCount("//input[@id='$id' and not(@checked)]")==1) $this->click("id=$id");
		}

		// Permit the subclass to handle any series/section data
		$this->_handleStep1($data);

		$this->click('css=[id^=submitFormButton-]');

		// Page 2: File wizard
		$this->waitForElementPresent($selector = 'id=cancelButton');
		$this->click($selector); // Thanks but no thanks
		$this->waitForElementNotPresent('css=div.pkp_modal_panel'); // pkp/pkp-lib#655

		foreach ($data['files'] as $file) {
			if (!isset($file['file'])) $file['file'] = DUMMY_PDF;
			$this->click('css=[id^=component-grid-files-submission-submissionwizardfilesgrid-addFile-button-]');
			$metadata = isset($file['metadata'])?$file['metadata']:array();
			$this->uploadWizardFile($file['fileTitle'], $file['file'], $metadata);
		}
		$this->waitForElementPresent($selector='//form[@id=\'submitStep2Form\']//button[text()=\'Save and continue\']');
		$this->click($selector);

		// Page 3
		$this->waitForElementPresent('css=[id^=title-]');
		$this->type('css=[id^=title-]', $data['title']);
		if (isset($data['abstract'])) $this->typeTinyMCE('abstract', $data['abstract']);
		foreach ($data['keywords'] as $tag) $this->addTag('keyword', $tag);

		foreach ($data['additionalAuthors'] as $authorData) {
			$this->addAuthor($authorData);
		}
		// Permit the subclass to handle any extra step 3 actions
		$this->_handleStep3($data);
		$this->waitForElementPresent($selector='//form[@id=\'submitStep3Form\']//button[text()=\'Save and continue\']');
		$this->click($selector);

		// Page 4
		$this->waitForElementPresent($selector='//form[@id=\'submitStep4Form\']//button[text()=\'Finish Submission\']');
		$this->click($selector);
		$this->waitForElementPresent($selector="//a[text()='OK']");
		$this->click($selector);
		$this->waitForElementPresent('//h2[contains(text(), \'Submission complete\')]');
	}

	/**
	 * Upload a file via the file wizard.
	 * @param $fileTitle string
	 * @param $file string|int Path to file to upload, or one of the DUMMY_ constants (default DUMMY_PDF)
	 * @param $metadata array Optional set of metadata for the upload
	 */
	protected function uploadWizardFile($fileTitle, $file = DUMMY_PDF, $metadata = array()) {
		if (is_numeric($file)) {
			// Determine which dummy file to use.
			switch($file) {
				case DUMMY_ZIP:
					$dummyfile = getenv('DUMMY_ZIP');
					$extension = 'zip';
					break;
				case DUMMY_PDF:
				default:
					$dummyfile = getenv('DUMMY_PDF');
					$extension = 'pdf';
			}
			$file = sys_get_temp_dir() . '/' . preg_replace('/[^a-z0-9\.]/', '', strtolower($fileTitle)) . '.' . $extension;

			// Generate a copy of the file to use with a unique-ish filename.
			copy($dummyfile, $file);
		}

		// Provide defaults for metadata
		$metadata = array_merge(
			array(
				'genre' => $this->_getSubmissionElementName(),
			),
			$metadata
		);

		// Unpack pieces for later use outside $metadata
		$genreName = $metadata['genre'];
		unset($metadata['genre']);

		// Pick the genre and upload the file
		$this->waitForElementPresent('id=genreId');
		$this->select('id=genreId', "label=$genreName");
		$this->uploadFile($file);
		$this->waitForElementPresent('css=button[id=continueButton]:enabled');
		$this->click('id=continueButton');

		// Enter the title into the metadata form
		$this->waitForElementPresent('css=[id^=name-]');
		$this->type('css=[id^=name-]', $fileTitle);

		// Enter remaining metadata into the form fields
		foreach ($metadata as $name => $value) {
			$this->type('css=[id^=' . $name . '-]', $value);
		}

		// Validate the form and finish
		$this->runScript('$(\'#metadataForm\').valid();');
		$this->click('css=[id=continueButton]');
		$this->waitJQuery();
		$this->waitForElementPresent($selector = 'css=[id=continueButton]');
		$this->click($selector);
		$this->waitJQuery();
		$this->waitForElementNotPresent('css=div.pkp_modal_panel'); // pkp/pkp-lib#655
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
		$this->click('//label[contains(.,\'' . $this->escapeJS($data['role']) . '\')]');
		$this->click('//button[text()=\'Save\']');
		$this->waitForElementNotPresent('css=div.pkp_modal_panel');
	}

	/**
	 * Log in as an Editor and find the specified submission.
	 * @param $username string
	 * @param $password string (null to presume twice-username)
	 * @param $title string
	 */
	protected function findSubmissionAsEditor($username, $password = null, $title) {
		if ($password === null) $password = $username . $username;
		$this->logIn($username, $password);
		$this->waitForElementPresent('css=#dashboardTabs');
		$this->click('css=[name=active]');
		$this->waitForElementPresent('css=[id^=component-grid-submissions-activesubmissions-activesubmissionslistgrid-]');
		$this->scrollPageDown();
		$xpath = '//span[contains(text(),' . $this->quoteXpath($title) .')]/../../..//a[contains(@id, "-stage-itemWorkflow-button-")]';
		$this->waitForElementPresent($xpath);
		$this->click($xpath);
	}

	protected function quoteXpath($string) {
		// Use an xpath concat to escape quotes in literals.
		// http://kushalm.com/the-perils-of-xpath-expressions-specifically-escaping-quotes
		return 'concat(\'' . strtr($this->escapeJS($string),
			array(
				'\\\'' => '\', "\'", \''
			)
		) . '\',\'\')';
	}

	/**
	 * Record an editorial decision
	 * @param $decision string
	 */
	protected function recordEditorialDecision($decision) {
		$this->waitForElementPresent($selector='//a[contains(.,\'' . $this->escapeJS($decision) . '\')]');
		$this->click($selector);
		$this->waitForElementPresent($selector='//button[contains(.,\'Record Editorial Decision\')]');
		$this->click($selector);
		$this->waitForElementNotPresent('css=div.pkp_modal_panel'); // pkp/pkp-lib#655
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
		$this->select('name=filterUserGroupId', 'label=' . $this->escapeJS($role));
		$this->waitJQuery();
		// Search by last name
		$names = explode(' ', $name);
		$this->waitForElementPresent($selector='//input[@id[starts-with(., \'namegrid-users-userselect-userselectgrid-\')]]');
		$this->type($selector, $names[1]);
		$this->click('//form[@id=\'searchUserFilter-grid-users-userselect-userselectgrid\']//button[@id[starts-with(., \'submitFormButton-\')]]');
		$this->waitJQuery();
		// Assume there is only one user with this last name and user group
		$this->waitForElementPresent($selector='//input[@name=\'userId\']');
		$this->click($selector);
		$this->click('//button[text()=\'OK\']');
		$this->waitForText('css=div.ui-pnotify-text', 'User added as a stage participant.');
		$this->waitForElementNotPresent('css=div.pkp_modal_panel');
	}

	/**
	 * Assign a reviewer.
	 * @param $username string
	 * @param $name string
	 */
	function assignReviewer($username, $name) {
		$this->waitForElementPresent('css=[id^=component-grid-users-reviewer-reviewergrid-addReviewer-button-]');
		$this->click('css=[id^=component-grid-users-reviewer-reviewergrid-addReviewer-button-]');
		$this->waitForElementPresent('css=[id^=name-]');
		$this->type('css=[id^=name-]', $username);
		$this->click('css=[id=submitFilter]');
		$this->waitJQuery();
		$this->click('css=[id^=reviewer_]');
		$this->click('css=[id^=selectReviewerButton]');

		$this->click('//button[text()=\'Add Reviewer\']');
		$this->waitForElementNotPresent('css=div.pkp_modal_panel'); // pkp/pkp-lib#655
	}

	/**
	 * Log in as a reviewer and perform a review.
	 * @param $username string
	 * @param $password string (or null to presume twice-username)
	 * @param $title string
	 * @param $recommendation string Optional recommendation label
	 * @param $comments string optional Optional comment text
	 */
	function performReview($username, $password, $title, $recommendation = null, $comments = 'Here are my review comments.') {
		if ($password===null) $password = $username . $username;
		$this->logIn($username, $password);

		// Use an xpath concat to permit apostrophes to appear in titles
		// http://kushalm.com/the-perils-of-xpath-expressions-specifically-escaping-quotes
		$this->scrollGridDown('assignedSubmissionsListGridContainer');
		$xpath = '//span[contains(text(),' . $this->quoteXpath($title) .')]/../../..//a[contains(@id, "-stage-itemWorkflow-button-")]';
		$this->waitForElementPresent($xpath);
		$this->click($xpath);

		$this->waitForElementPresent($selector='//button[text()=\'Accept Review, Continue to Step #2\']');
		$this->click($selector);

		$this->waitForElementPresent($selector='//button[text()=\'Continue to Step #3\']');
		$this->click($selector);
		$this->waitForElementPresent('css=[id^=comments-]');
		$this->typeTinyMCE('comments', $comments);

		if ($recommendation !== null) {
			$this->select('id=recommendation', 'label=' . $this->escapeJS($recommendation));
		}

		$this->waitForElementPresent($selector='//button[text()=\'Submit Review\']');
		$this->click($selector);
		$this->waitForElementPresent($selector='link=OK');
		$this->click($selector);
		$this->waitForElementPresent('//h2[contains(text(), \'Review Submitted\')]');
		$this->waitJQuery();
		$this->logOut();
	}
}

?>
