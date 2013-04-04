<?php

/**
 * @defgroup controllers_grid_issues_form
 */

/**
 * @file controllers/grid/issues/form/CoverForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CoverForm
 * @ingroup controllers_grid_issues_form
 * @see Issue
 *
 * @brief Form to create or edit an issue
 */

import('lib.pkp.classes.form.Form');

class CoverForm extends Form {
	/** @var Issue current issue */
	var $issue;

	/**
	 * Constructor.
	 */
	function CoverForm() {
		parent::Form('controllers/grid/issues/form/coverForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
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

		if ($this->issue) {
			$templateMgr->assign('issue', $this->issue);
			$templateMgr->assign('issueId', $this->issue->getId());
		}

		return parent::fetch($request);
	}

	/**
	 * Validate the form
	 */
	function validate($request, $issue = null) {
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		if ($publicFileManager->uploadedFileExists('coverPage')) {
			$type = $publicFileManager->getUploadedFileType('coverPage');
			if (!$publicFileManager->getImageExtension($type)) {
				$this->addError('coverPage', __('editor.issues.invalidCoverPageFormat'));
				$this->addErrorField('coverPage');
			}
		}

		return parent::validate();
	}

	/**
	 * Initialize form data from current issue.
	 * returns issue id that it initialized the page with
	 */
	function initData($request, $issueId = null) {
		$issueDao = DAORegistry::getDAO('IssueDAO');

		// retrieve issue by id, if not specified, then select first unpublished issue
		if (isset($issueId)) {
			$issue = $issueDao->getById($issueId);
		}

		if (isset($issue)) {
			$this->issue = $issue;
			$this->_data = array(
				'fileName' => $issue->getFileName(null), // Localized
				'originalFileName' => $issue->getOriginalFileName(null), // Localized
				'coverPageDescription' => $issue->getCoverPageDescription(null), // Localized
				'coverPageAltText' => $issue->getCoverPageAltText(null), // Localized
				'showCoverPage' => $issue->getShowCoverPage(null), // Localized
				'hideCoverPageArchives' => $issue->getHideCoverPageArchives(null), // Localized
				'hideCoverPageCover' => $issue->getHideCoverPageCover(null), // Localized
			);

			parent::initData();
			return $issue->getId();
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'fileName',
			'originalFileName',
			'coverPageDescription',
			'coverPageAltText',
			'showCoverPage',
			'hideCoverPageArchives',
			'hideCoverPageCover',
		));
	}

	/**
	 * Save issue settings.
	 */
	function execute($request, $issueId = 0) {
		$issueDao = DAORegistry::getDAO('IssueDAO');

		$issue = $issueDao->getById($issueId);
		$issue->setCoverPageDescription($this->getData('coverPageDescription'), null); // Localized
		$issue->setCoverPageAltText($this->getData('coverPageAltText'), null); // Localized
		$showCoverPage = array_map(create_function('$arrayElement', 'return (int)$arrayElement;'), (array) $this->getData('showCoverPage'));
		foreach (array_keys($this->getData('coverPageDescription')) as $locale) {
			if (!array_key_exists($locale, $showCoverPage)) {
				$showCoverPage[$locale] = 0;
			}
		}
		$issue->setShowCoverPage($showCoverPage, null); // Localized

		$hideCoverPageArchives = array_map(create_function('$arrayElement', 'return (int)$arrayElement;'), (array) $this->getData('hideCoverPageArchives'));
		foreach (array_keys($this->getData('coverPageDescription')) as $locale) {
			if (!array_key_exists($locale, $hideCoverPageArchives)) {
				$hideCoverPageArchives[$locale] = 0;
			}
		}
		$issue->setHideCoverPageArchives($hideCoverPageArchives, null); // Localized

		$hideCoverPageCover = array_map(create_function('$arrayElement', 'return (int)$arrayElement;'), (array) $this->getData('hideCoverPageCover'));
		foreach (array_keys($this->getData('coverPageDescription')) as $locale) {
			if (!array_key_exists($locale, $hideCoverPageCover)) {
				$hideCoverPageCover[$locale] = 0;
			}
		}
		$issue->setHideCoverPageCover($hideCoverPageCover, null); // Localized

		$this->issue = $issue;
		parent::execute();
		$issueDao->updateObject($issue);

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->uploadedFileExists('coverPage')) {
			$journal = $request->getJournal();
			$originalFileName = $publicFileManager->getUploadedFileName('coverPage');
			$type = $publicFileManager->getUploadedFileType('coverPage');
			$newFileName = 'cover_issue_' . $issueId . '_' . $this->getFormLocale() . $publicFileManager->getImageExtension($type);
			$publicFileManager->uploadJournalFile($journal->getId(), 'coverPage', $newFileName);
			$issue->setOriginalFileName($publicFileManager->truncateFileName($originalFileName, 127), $this->getFormLocale());
			$issue->setFileName($newFileName, $this->getFormLocale());

			// Store the image dimensions.
			list($width, $height) = getimagesize($publicFileManager->getJournalFilesPath($journal->getId()) . '/' . $newFileName);
			$issue->setWidth($width, $this->getFormLocale());
			$issue->setHeight($height, $this->getFormLocale());

			$issueDao->updateObject($issue);
		}

		return $issueId;
	}
}

?>
