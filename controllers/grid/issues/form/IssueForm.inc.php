<?php

/**
 * @file controllers/grid/issues/form/IssueForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueForm
 * @ingroup controllers_grid_issues_form
 * @see Issue
 *
 * @brief Form to create or edit an issue
 */

import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.linkAction.LinkAction');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

import('classes.issue.Issue'); // Bring in constants

class IssueForm extends Form {
	/** @var Issue current issue */
	var $issue;

	/**
	 * Constructor.
	 * @param $issue Issue (optional)
	 */
	function __construct($issue = null) {
		parent::__construct('controllers/grid/issues/form/issueForm.tpl');

		$form = $this;
		$this->addCheck(new FormValidatorRegExp($this, 'volume', 'optional', 'editor.issues.volumeRequired', '/^[0-9]+$/i'));
		$this->addCheck(new FormValidatorCustom($this, 'showVolume', 'optional', 'editor.issues.volumeRequired', function($showVolume) use ($form) {
			return !$showVolume || $form->getData('volume') ? true : false;
		}));
		$this->addCheck(new FormValidatorCustom($this, 'showNumber', 'optional', 'editor.issues.numberRequired', function($showNumber) use ($form) {
			return !$showNumber || $form->getData('number') ? true : false;
		}));
		$this->addCheck(new FormValidatorCustom($this, 'showYear', 'optional', 'editor.issues.yearRequired', function($showYear) use ($form) {
			return !$showYear || $form->getData('year') ? true : false;
		}));
		$this->addCheck(new FormValidatorCustom($this, 'showTitle', 'optional', 'editor.issues.titleRequired', function($showTitle) use ($form) {
			return !$showTitle || implode('', $form->getData('title'))!='' ? true : false;
		}));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
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
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		if ($this->issue) {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign(array(
				'issue' => $this->issue,
				'issueId' => $this->issue->getId(),
			));

			// Cover image delete link action
			if ($coverImage = $this->issue->getCoverImage(AppLocale::getLocale())) $templateMgr->assign(
				'deleteCoverImageLinkAction',
				new LinkAction(
					'deleteCoverImage',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('common.confirmDelete'), null,
						$request->getRouter()->url(
							$request, null, null, 'deleteCoverImage', null, array(
								'coverImage' => $coverImage,
								'issueId' => $this->issue->getId(),
							)
						),
						'modal_delete'
					),
					__('common.delete'),
					null
				)
			);
		}

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::validate()
	 */
	function validate($callHooks = true) {
		if ($temporaryFileId = $this->getData('temporaryFileId')) {
			$request = Application::get()->getRequest();
			$user = $request->getUser();
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			if (!$publicFileManager->getImageExtension($temporaryFile->getFileType())) {
				$this->addError('coverImage', __('editor.issues.invalidCoverImageFormat'));
			}
		}

		return parent::validate($callHooks);
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData() {
		if (isset($this->issue)) {
			$locale = AppLocale::getLocale();
			$this->_data = array(
				'title' => $this->issue->getTitle(null), // Localized
				'volume' => $this->issue->getVolume(),
				'number' => $this->issue->getNumber(),
				'year' => $this->issue->getYear(),
				'datePublished' => $this->issue->getDatePublished(),
				'description' => $this->issue->getDescription(null), // Localized
				'showVolume' => $this->issue->getShowVolume(),
				'showNumber' => $this->issue->getShowNumber(),
				'showYear' => $this->issue->getShowYear(),
				'showTitle' => $this->issue->getShowTitle(),
				'coverImage' => $this->issue->getCoverImage($locale),
				'coverImageAltText' => $this->issue->getCoverImageAltText($locale),
			);
			parent::initData();
		} else {
			$this->_data = array(
				'showVolume' => 1,
				'showNumber' => 1,
				'showYear' => 1,
				'showTitle' => 1,
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
			'showVolume',
			'showNumber',
			'showYear',
			'showTitle',
			'temporaryFileId',
			'coverImageAltText',
			'datePublished',
		));

		$form = $this;
		$this->addCheck(new FormValidatorCustom($this, 'issueForm', 'required', 'editor.issues.issueIdentificationRequired', function() use ($form) {
			return $form->getData('showVolume') || $form->getData('showNumber') || $form->getData('showYear') || $form->getData('showTitle');
		}));
	}

	/**
	 * Save issue settings.
	 */
	function execute(...$functionArgs) {
		parent::execute(...$functionArgs);

		$request = Application::get()->getRequest();
		$journal = $request->getJournal();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		if ($this->issue) {
			$isNewIssue = false;
			$issue = $this->issue;
		} else {
			$issue = $issueDao->newDataObject();
			switch ($journal->getData('publishingMode')) {
				case PUBLISHING_MODE_SUBSCRIPTION:
				case PUBLISHING_MODE_NONE:
					$issue->setAccessStatus(ISSUE_ACCESS_SUBSCRIPTION);
					break;
				case PUBLISHING_MODE_OPEN:
				default:
					$issue->setAccessStatus(ISSUE_ACCESS_OPEN);
					break;
			}
			$isNewIssue = true;
		}
		$volume = $this->getData('volume');
		$number = $this->getData('number');
		$year = $this->getData('year');

		$issue->setJournalId($journal->getId());
		$issue->setTitle($this->getData('title'), null); // Localized
		$issue->setVolume(empty($volume) ? null : $volume);
		$issue->setNumber(empty($number) ? null : $number);
		$issue->setYear(empty($year) ? null : $year);
		if (!$isNewIssue) {
			$issue->setDatePublished($this->getData('datePublished'));
		}
		$issue->setDescription($this->getData('description'), null); // Localized
		$issue->setShowVolume($this->getData('showVolume'));
		$issue->setShowNumber($this->getData('showNumber'));
		$issue->setShowYear($this->getData('showYear'));
		$issue->setShowTitle($this->getData('showTitle'));

		// If it is a new issue, first insert it, then update the cover
		// because the cover name needs an issue id.
		if ($isNewIssue) {
			$issue->setPublished(0);
			$issue->setCurrent(0);
			$issueDao->insertObject($issue);
		}

		$locale = AppLocale::getLocale();
		// Copy an uploaded cover file for the issue, if there is one.
		if ($temporaryFileId = $this->getData('temporaryFileId')) {
			$user = $request->getUser();
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$newFileName = 'cover_issue_' . $issue->getId() . '_' . $locale . $publicFileManager->getImageExtension($temporaryFile->getFileType());
			$journal = $request->getJournal();
			$publicFileManager->copyContextFile($journal->getId(), $temporaryFile->getFilePath(), $newFileName);
			$issue->setCoverImage($newFileName, $locale);
			$issueDao->updateObject($issue);
		}

		$issue->setCoverImageAltText($this->getData('coverImageAltText'), $locale);

		HookRegistry::call('issueform::execute', array($this, $issue));

		$issueDao->updateObject($issue);
	}
}


