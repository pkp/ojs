<?php

/**
 * IssueForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue.form
 *
 * Form to create or edit an issue
 *
 * $Id$
 */

import('form.Form');

class IssueForm extends Form {

	/**
	 * Constructor.
	 */
	function IssueForm($template) {
		parent::Form($template);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

		$journal = Request::getJournal();
		$journalId = $journal->getJournalId();
		$templateMgr->assign('journalId', $journalId);
		
		// set up the accessibility options pulldown
		$templateMgr->assign('enableSubscriptions',$journalSettingsDao->getSetting($journalId,'enableSubscriptions'));
		$accessOptions[OPEN_ACCESS] = Locale::Translate('editor.issues.openAccess');
		$accessOptions[SUBSCRIPTION] = Locale::Translate('editor.issues.subscription');
		$templateMgr->assign('accessOptions', $accessOptions);

		// set up the label options pulldown
		$labelOptions[1] = Locale::Translate('editor.issues.labelOption1');
		$labelOptions[2] = Locale::Translate('editor.issues.labelOption2');
		$labelOptions[3] = Locale::Translate('editor.issues.labelOption3');
		$labelOptions[4] = Locale::Translate('editor.issues.labelOption4');
		$templateMgr->assign('labelOptions',$labelOptions);
		$templateMgr->assign('labelFormat',$journalSettingsDao->getSetting($journalId,'publicationFormat'));

		// set up the default values for volume, number and year
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getLastCreatedIssue($journalId);

		if ($issue->getIssueId()) {
			$volumePerYear = $journalSettingsDao->getSetting($journalId,'volumePerYear');
			$issuePerVolume = $journalSettingsDao->getSetting($journalId,'issuePerVolume');
			$number = $issue->getNumber();
			$volume = $issue->getVolume();
			$year = $issue->getYear();

			if ($issuePerVolume && ($issuePerVolume <= $number)) {
				$number = 1;

				if ($volumePerYear && ($volumePerYear <= $volume)) {
					$volume = 1;
					$year++;
				} else {
					$volume++;
				}

			} else {
				$number++;
			}

		} else {
			$volume = $journalSettingsDao->getSetting($journalId,'initialVolume');
			$number = $journalSettingsDao->getSetting($journalId,'initialNumber');
			$year = $journalSettingsDao->getSetting($journalId,'initialYear');
		}
		$templateMgr->assign('volume', $volume);
		$templateMgr->assign('number', $number);
		$templateMgr->assign('year', $year);

		$templateMgr->assign('enablePublicIssueId', $journalSettingsDao->getSetting($journalId,'enablePublicIssueId'));

		parent::display();	
	}

	/**
	 * Validate the form
	 */
	function validate($issueId = 0) {
		switch ($this->getData('labelFormat')) {
			case 1:
				$this->addCheck(new FormValidator(&$this, 'number', 'required', 'editor.issues.numberRequired'));
			case 2:
				$this->addCheck(new FormValidator(&$this, 'volume', 'required', 'editor.issues.volumeRequired'));
			case 3:
				$this->addCheck(new FormValidator(&$this, 'year', 'required', 'editor.issues.yearRequired'));
		}

		// check if volume, number, and year combo have already been used
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$journalId = $this->getData('journalId');
		$volume = $this->getData('volume');
		$number = $this->getData('number');
		$year = $this->getData('year');
		if ($issueDao->issueExists($journalId,$volume,$number,$year,$issueId)) {
			$this->addError('issueLabel', 'editor.issues.issueIdentifcationExists');
			$this->addErrorField('volume');
			$this->addErrorField('number');
			$this->addErrorField('year');
		}

		$publicIssueId = $this->getData('publicIssueId');
		if ($publicIssueId && $issueDao->publicIssueIdExists($publicIssueId, $issueId)) {
			$this->addError('publicIssueId', 'editor.issues.issuePublicIdentifcationExists');
			$this->addErrorField('publicIssueId');
		}

		// check if date open access date is correct if subscription is selected and enabled
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$subscription = $journalSettingsDao->getSetting($journalId,'enableSubscriptions');

		if ($subscription) {
			$month = $this->getData('Date_Month');
			$day = $this->getData('Date_Day');
			$year = $this->getData('Date_Year');
			if (!checkdate($month,$day,$year)) {
				$this->addError('openAccessDate', 'editor.issues.invalidAccessDate');
				$this->addErrorField('openAccessDate');		
			}
		}

		import('file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->uploadedFileExists('coverPage')) {
			$type = $publicFileManager->getUploadedFileType('coverPage');
			if (!$publicFileManager->getImageExtension($type)) {
				$this->addError('coverPage', 'editor.issues.invalidCoverPageFormat');
				$this->addErrorField('coverPage');		
			}
		}

		return parent::validate();
	}

	/**
	 * Initialize form data from current issue.
	 * returns issue id that it initialized the page with
	 */
	function initData($issueId) {
		$journal = Request::getJournal();
		$journalId = $journal->getJournalId();

		$issueDao = &DAORegistry::getDAO('IssueDAO');

		// retrieve issue by id, if not specified, then select first unpublished issue
		if ($issueId) {
			$issue = &$issueDao->getIssueById($issueId);
		} else {
			$issuesIterator = &$issueDao->getUnpublishedIssues($journalId);
			if (!$issuesIterator->eof()) {
				$issue = $issuesIterator->next();
			}
		}
		if (isset($issue)) {
			$openAccessDate = getdate(strtotime($issue->getOpenAccessDate()));

			$this->_data = array(
				'journalId' => $issue->getJournalId(),
				'title' => $issue->getTitle(),
				'volume' => $issue->getVolume(),
				'number' => $issue->getNumber(),
				'year' => $issue->getYear(),
				'description' => $issue->getDescription(),
				'publicIssueId' => $issue->getPublicIssueId(),
				'accessStatus' => $issue->getAccessStatus(),
				'Date_Month' => $openAccessDate['mon'],
				'Date_Day' => $openAccessDate['mday'],
				'Date_Year' => $openAccessDate['year'],
				'labelFormat' => $issue->getLabelFormat(),
				'fileName' => $issue->getFileName(),
				'originalFileName' => $issue->getOriginalFileName(),
				'coverPageDescription' => $issue->getCoverPageDescription(),
				'showCoverPage' => $issue->getShowCoverPage()
			);
			return $issue->getIssueId();
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'journalId',
			'title',
			'volume',
			'number',
			'year',
			'description',
			'publicIssueId',
			'accessStatus',
			'Date_Month',
			'Date_Day',
			'Date_Year',
			'labelFormat',
			'fileName',
			'originalFileName',
			'coverPageDescription',
			'showCoverPage'
		));

	}
	
	/**
	 * Save issue settings.
	 */
	function execute($issueId = 0) {
		$issueDao = &DAORegistry::getDAO('IssueDAO');

		if ($issueId) {
			$issue = $issueDao->getIssueById($issueId);
		} else {
			$issue = &new Issue();
		}

		$issue->setJournalId($this->getData('journalId'));
		$issue->setTitle($this->getData('title'));
		$issue->setVolume($this->getData('volume'));
		$issue->setNumber($this->getData('number'));
		$issue->setYear($this->getData('year'));
		$issue->setDescription($this->getData('description'));
		$issue->setPublicIssueId($this->getData('publicIssueId'));
		$issue->setLabelFormat($this->getData('labelFormat'));
		$issue->setCoverPageDescription($this->getData('coverPageDescription'));
		$issue->setShowCoverPage((int)$this->getData('showCoverPage'));

		$month = $this->getData('Date_Month');
		$day = $this->getData('Date_Day');
		$year = $this->getData('Date_Year');

		if ($this->getData('accessStatus')) {
			$issue->setAccessStatus($this->getData('accessStatus'));
			$issue->setOpenAccessDate(date('Y-m-d H:i:s',mktime(0,0,0,$month,$day,$year)));
		} else {
			$issue->setAccessStatus(1);
			$issue->setOpenAccessDate(Core::getCurrentDate());		
		}

		// if issueId is supplied, then update issue otherwise insert a new one
		if ($issueId) {
			$issue->setIssueId($issueId);
			$issueDao->updateIssue($issue);
		} else {
			$issue->setPublished(0);
			$issue->setCurrent(0);

			$issueId = $issueDao->insertIssue($issue);
			$issue->setIssueId($issueId);
		}

		import('file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->uploadedFileExists('coverPage')) {
			$journal = Request::getJournal();
			$originalFileName = $publicFileManager->getUploadedFileName('coverPage');
			$newFileName = 'cover_' . $issueId . '.' . $publicFileManager->getExtension($originalFileName);
			$publicFileManager->uploadJournalFile($journal->getJournalId(),'coverPage', $newFileName);
			$issue->setOriginalFileName($originalFileName);
			$issue->setFileName($newFileName);
			$issueDao->updateIssue($issue);
		}

		return $issueId;
	}
	
}

?>
