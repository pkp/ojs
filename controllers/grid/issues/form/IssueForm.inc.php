<?php

/**
 * @file controllers/grid/issues/form/IssueForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueForm
 * @ingroup controllers_grid_issues_form
 * @see Issue
 *
 * @brief Form to create or edit an issue
 */

import('lib.pkp.classes.form.Form');
import('classes.issue.Issue'); // Bring in constants

class IssueForm extends Form {
	/** @var Issue current issue */
	var $issue;

	/**
	 * Constructor.
	 */
	function IssueForm($issue = null) {
		parent::Form('controllers/grid/issues/form/issueForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
		$this->issue = $issue;
	}

	/**
	 * Get a list of fields for which localization should be used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$issueDao = DAORegistry::getDAO('IssueDAO');
		return $issueDao->getLocaleFieldNames();
	}

	/**
	 * Fetch the form.
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$journal = $request->getJournal();

		// set up the accessibility options pulldown
		$templateMgr->assign('enableDelayedOpenAccess', $journal->getSetting('enableDelayedOpenAccess'));

		$templateMgr->assign('accessOptions', array(
			ISSUE_ACCESS_OPEN => AppLocale::Translate('editor.issues.openAccess'),
			ISSUE_ACCESS_SUBSCRIPTION => AppLocale::Translate('editor.issues.subscription')
		));

		$templateMgr->assign('enablePublicIssueId', $journal->getSetting('enablePublicIssueId'));
		if ($this->issue) {
			$templateMgr->assign('issue', $this->issue);
			$templateMgr->assign('issueId', $this->issue->getId());
		}

		// consider public identifiers
		$templateMgr->assign('pubIdPlugins', PluginRegistry::loadCategory('pubIds', true));

		return parent::fetch($request);
	}

	/**
	 * Validate the form
	 */
	function validate($request) {
		if ($this->getData('showVolume')) {
			$this->addCheck(new FormValidatorCustom($this, 'volume', 'required', 'editor.issues.volumeRequired', create_function('$volume', 'return ($volume > 0);')));
		}

		if ($this->getData('showNumber')) {
			$this->addCheck(new FormValidatorCustom($this, 'number', 'required', 'editor.issues.numberRequired', create_function('$number', 'return ($number > 0);')));
		}

		if ($this->getData('showYear')) {
			$this->addCheck(new FormValidatorCustom($this, 'year', 'required', 'editor.issues.yearRequired', create_function('$year', 'return ($year > 0);')));
		}

		if ($this->getData('showTitle')) {
			$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'editor.issues.titleRequired'));
		}

		// check if public issue ID has already been used
		$journal = $request->getJournal();
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */

		$publicIssueId = $this->getData('publicIssueId');
		if ($this->issue && $publicIssueId && $journalDao->anyPubIdExists($journal->getId(), 'publisher-id', $publicIssueId, ASSOC_TYPE_ISSUE, $this->issue->getId())) {
			$this->addError('publicIssueId', __('editor.publicIdentificationExists', array('publicIdentifier' => $publicIssueId)));
			$this->addErrorField('publicIssueId');
		}

		if ($temporaryFileId = $this->getData('temporaryFileId')) {
			$user = $request->getUser();
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());
			if (!in_array($temporaryFile->getFileType(), array('text/plain', 'text/css'))) {
				$this->addError('styleFile', __('editor.issues.invalidStyleFormat'));
			}
		}

		// Verify additional fields from public identifer plug-ins.
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->validate($journal->getId(), $this, $this->issue);

		return parent::validate();
	}

	/**
	 * Initialize form data from current issue.
	 */
	function initData($request) {
		if (isset($this->issue)) {
			$this->_data = array(
				'title' => $this->issue->getTitle(null), // Localized
				'volume' => $this->issue->getVolume(),
				'number' => $this->issue->getNumber(),
				'year' => $this->issue->getYear(),
				'datePublished' => $this->issue->getDatePublished(),
				'description' => $this->issue->getDescription(null), // Localized
				'publicIssueId' => $this->issue->getPubId('publisher-id'),
				'accessStatus' => $this->issue->getAccessStatus(),
				'openAccessDate' => $this->issue->getOpenAccessDate(),
				'showVolume' => $this->issue->getShowVolume(),
				'showNumber' => $this->issue->getShowNumber(),
				'showYear' => $this->issue->getShowYear(),
				'showTitle' => $this->issue->getShowTitle(),
				'styleFileName' => $this->issue->getStyleFileName(),
				'originalStyleFileName' => $this->issue->getOriginalStyleFileName()
			);
			// consider the additional field names from the public identifer plugins
			import('classes.plugins.PubIdPluginHelper');
			$pubIdPluginHelper = new PubIdPluginHelper();
			$pubIdPluginHelper->init($this, $this->issue);

			parent::initData();
		} else {
			$journal = $request->getJournal();
			switch ($journal->getSetting('publishingMode')) {
				case PUBLISHING_MODE_SUBSCRIPTION:
				case PUBLISHING_MODE_NONE:
					$accessStatus = ISSUE_ACCESS_SUBSCRIPTION;
					break;
				case PUBLISHING_MODE_OPEN:
				default:
					$accessStatus = ISSUE_ACCESS_OPEN;
					break;
			}

			$this->_data = array(
				'showVolume' => 1,
				'showNumber' => 1,
				'showYear' => 1,
				'showTitle' => 1,
				'accessStatus' => $accessStatus
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
			'enableOpenAccessDate',
			'showVolume',
			'showNumber',
			'showYear',
			'showTitle',
			'temporaryFileId'
		));
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->readInputData($this);

		$this->readUserDateVars(array('datePublished', 'openAccessDate'));

		$this->addCheck(new FormValidatorCustom($this, 'showVolume', 'required', 'editor.issues.issueIdentificationRequired', create_function('$showVolume, $showNumber, $showYear, $showTitle', 'return $showVolume || $showNumber || $showYear || $showTitle ? true : false;'), array($this->getData('showNumber'), $this->getData('showYear'), $this->getData('showTitle'))));

	}

	/**
	 * Save issue settings.
	 * @param $request PKPRequest
	 * @return int Issue ID for created/updated issue
	 */
	function execute($request) {
		$journal = $request->getJournal();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		if ($this->issue) {
			$isNewIssue = false;
			$issue = $this->issue;
		} else {
			$issue = $issueDao->newDataObject();
			$isNewIssue = true;
		}
		$volume = $this->getData('volume');
		$number = $this->getData('number');
		$year = $this->getData('year');

		$issue->setJournalId($journal->getId());
		$issue->setTitle($this->getData('title'), null); // Localized
		$issue->setVolume(empty($volume) ? 0 : $volume);
		$issue->setNumber(empty($number) ? 0 : $number);
		$issue->setYear(empty($year) ? 0 : $year);
		if (!$isNewIssue) {
			$issue->setDatePublished($this->getData('datePublished'));
		}
		$issue->setDescription($this->getData('description'), null); // Localized
		$issue->setStoredPubId('publisher-id', $this->getData('publicIssueId'));
		$issue->setShowVolume($this->getData('showVolume'));
		$issue->setShowNumber($this->getData('showNumber'));
		$issue->setShowYear($this->getData('showYear'));
		$issue->setShowTitle($this->getData('showTitle'));

		$issue->setAccessStatus($this->getData('accessStatus') ? $this->getData('accessStatus') : ISSUE_ACCESS_OPEN); // See bug #6324
		if ($this->getData('enableOpenAccessDate')) $issue->setOpenAccessDate($this->getData('openAccessDate'));
		else $issue->setOpenAccessDate(null);

		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->execute($this, $issue);

		// if issueId is supplied, then update issue otherwise insert a new one
		if (!$isNewIssue) {
			parent::execute();
			$issueDao->updateObject($issue);
		} else {
			$issue->setPublished(0);
			$issue->setCurrent(0);

			$issueDao->insertObject($issue);
		}

		// Copy an uploaded CSS file for the issue, if there is one.
		// (Must be done after insert for new issues as issue ID is in the filename.)
		if ($temporaryFileId = $this->getData('temporaryFileId')) {
			$user = $request->getUser();
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$newFileName = 'style_' . $issue->getId() . '.css';
			$publicFileManager->copyJournalFile($journal->getId(), $temporaryFile->getFilePath(), $newFileName);
			$issue->setStyleFileName($newFileName);
			$issue->setOriginalStyleFileName($publicFileManager->truncateFileName($temporaryFile->getOriginalFileName(), 127));
			$issueDao->updateObject($issue);
		}
	}
}

?>
