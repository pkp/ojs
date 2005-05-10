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
		$this->addCheck(new FormValidatorInSet(&$this, 'labelFormat', 'required', 'editor.issues.labelFormatRequired', array(1, 2, 3, 4)));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$journal = &Request::getJournal();
		
		// set up the accessibility options pulldown
		$templateMgr->assign('enableSubscriptions', $journal->getSetting('enableSubscriptions'));
		$accessOptions[OPEN_ACCESS] = Locale::Translate('editor.issues.openAccess');
		$accessOptions[SUBSCRIPTION] = Locale::Translate('editor.issues.subscription');
		$templateMgr->assign('accessOptions', $accessOptions);

		// set up the label options pulldown
		$labelOptions[1] = Locale::Translate('editor.issues.labelOption1');
		$labelOptions[2] = Locale::Translate('editor.issues.labelOption2');
		$labelOptions[3] = Locale::Translate('editor.issues.labelOption3');
		$labelOptions[4] = Locale::Translate('editor.issues.labelOption4');
		$templateMgr->assign('labelOptions', $labelOptions);

		$templateMgr->assign('enablePublicIssueId', $journal->getSetting('enablePublicIssueId'));

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
		$journal = &Request::getJournal();
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$volume = $this->getData('volume');
		$number = $this->getData('number');
		$year = $this->getData('year');
		if ($issueDao->issueExists($journal->getJournalId(), $volume, $number, $year, $issueId)) {
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
		$subscription = $journal->getSetting('enableSubscriptions');

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
	function initData($issueId = null) {
		$issueDao = &DAORegistry::getDAO('IssueDAO');

		// retrieve issue by id, if not specified, then select first unpublished issue
		if (isset($issueId)) {
			$issue = &$issueDao->getIssueById($issueId);
		}
		
		if (isset($issue)) {
			$openAccessDate = getdate(strtotime($issue->getOpenAccessDate()));

			$this->_data = array(
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
			
		} else {
			$journal = &Request::getJournal();
			$labelFormat = $journal->getSetting('publicationFormat');
			$this->setData('labelFormat', $labelFormat);

			// set up the default values for volume, number and year
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getLastCreatedIssue($journal->getJournalId());
	
			if (isset($issue)) {
				$volumePerYear = $journal->getSetting('volumePerYear');
				$issuePerVolume = $journal->getSetting('issuePerVolume');
				$number = $issue->getNumber();
				$volume = $issue->getVolume();
				$year = $issue->getYear();
			
				switch ($labelFormat) {
					case 4:
						$year = $volume = $number = 0;
						break;
					case 3:
						$volume = $number = 0;
						$year++;
						break;
					case 2:
						$number = 0;
						$volume++;
						if ($volumePerYear && $volume > $volumePerYear) {
							$volume = 1;
							$year++;
						}
						break;
					case 1:
						$number++;
						if ($issuePerVolume && $number > $issuePerVolume) {
							$number = 1;
							$volume++;
							if ($volumePerYear && $volume > $volumePerYear) {
								$volume = 1;
								$year++;
							}
						}
						break;
				}
				
			} else {
				$volume = $journal->getSetting('initialVolume');
				$number = $journal->getSetting('initialNumber');
				$year = $journal->getSetting('initialYear');
			}
			
			$this->_data = array(
				'labelFormat' => $labelFormat,
				'volume' => $volume,
				'number' => $number,
				'year' => $year
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
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
			'showCoverPage',
			'articles'
		));
	}
	
	/**
	 * Save issue settings.
	 */
	function execute($issueId = 0) {
		$journal = &Request::getJournal();
		$issueDao = &DAORegistry::getDAO('IssueDAO');

		if ($issueId) {
			$issue = $issueDao->getIssueById($issueId);
		} else {
			$issue = &new Issue();
		}
		
		$volume = $this->getData('volume');
		$number = $this->getData('number');
		$year = $this->getData('year');

		$issue->setJournalId($journal->getJournalId());
		$issue->setTitle($this->getData('title'));
		$issue->setVolume(empty($volume) ? 0 : $volume);
		$issue->setNumber(empty($number) ? 0 : $number);
		$issue->setYear(empty($year) ? 0 : $year);
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
			$publicFileManager->uploadJournalFile($journal->getJournalId(), 'coverPage', $newFileName);
			$issue->setOriginalFileName($originalFileName);
			$issue->setFileName($newFileName);
			$issueDao->updateIssue($issue);
		}

		return $issueId;
	}
	
}

?>
