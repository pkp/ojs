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
	function CoverForm($issue) {
		parent::Form('controllers/grid/issues/form/coverForm.tpl');
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

		if ($this->issue) {
			$templateMgr->assign('issue', $this->issue);
			$templateMgr->assign('issueId', $this->issue->getId());
		}

		return parent::fetch($request);
	}

	/**
	 * Validate the form
	 */
	function validate($request) {
		if ($temporaryFileId = $this->getData('temporaryFileId')) {
			$user = $request->getUser();
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			if (!$publicFileManager->getImageExtension($temporaryFile->getFileType())) {
				$this->addError('coverPage', __('editor.issues.invalidCoverPageFormat'));
			}
		}

		return parent::validate();
	}

	/**
	 * Initialize form data from current issue.
	 */
	function initData($request) {
		$this->_data = array(
			'fileName' => $this->issue->getFileName(null), // Localized
			'originalFileName' => $this->issue->getOriginalFileName(null), // Localized
			'coverPageDescription' => $this->issue->getCoverPageDescription(null), // Localized
			'coverPageAltText' => $this->issue->getCoverPageAltText(null), // Localized
			'showCoverPage' => $this->issue->getShowCoverPage(null), // Localized
			'hideCoverPageArchives' => $this->issue->getHideCoverPageArchives(null), // Localized
			'hideCoverPageCover' => $this->issue->getHideCoverPageCover(null), // Localized
		);

		parent::initData();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'temporaryFileId',
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
	function execute($request) {
		$issue = $this->issue;
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

		parent::execute();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateObject($issue);

		// Copy an uploaded cover file for the issue, if there is one.
		if ($temporaryFileId = $this->getData('temporaryFileId')) {
			$user = $request->getUser();
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$newFileName = 'cover_issue_' . $issue->getId() . '_' . $this->getFormLocale() . $publicFileManager->getImageExtension($temporaryFile->getFileType());
			$journal = $request->getJournal();
			$publicFileManager->copyJournalFile($journal->getId(), $temporaryFile->getFilePath(), $newFileName);
			$issue->setFileName($newFileName, $this->getFormLocale());
			$issue->setOriginalFileName($publicFileManager->truncateFileName($temporaryFile->getOriginalFileName(), 127), $this->getFormLocale());
			$issueDao->updateObject($issue);
		}

		return $issue->getId();
	}
}

?>
