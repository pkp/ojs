<?php

/**
 * @file controllers/grid/issues/form/IssueGalleyForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyForm
 * @ingroup issue_galley
 * @see IssueGalley
 *
 * @brief Issue galley editing form.
 */

import('lib.pkp.classes.form.Form');

class IssueGalleyForm extends Form {
	/** @var Issue the issue the galley belongs to */
	var $_issue = null;

	/** @var IssueGalley current galley */
	var $_issueGalley = null;

	/**
	 * Constructor.
	 * @param $issue Issue
	 * @param $issueGalley IssueGalley (optional)
	 */
	function __construct($request, $issue, $issueGalley = null) {
		parent::__construct('controllers/grid/issueGalleys/form/issueGalleyForm.tpl');
		$this->_issue = $issue;
		$this->_issueGalley = $issueGalley;

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION);

		$this->addCheck(new FormValidator($this, 'label', 'required', 'editor.issues.galleyLabelRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

		// Ensure a locale is provided and valid
		$journal = $request->getJournal();
		$this->addCheck(new FormValidatorCustom(
			$this, 'galleyLocale', 'required', 'editor.issues.galleyLocaleRequired',
			function($galleyLocale) use ($journal) {
				return in_array($galleyLocale, $journal->getSupportedLocales());
			}
		));

		if (!$issueGalley) {
			// A file must be uploaded with a newly-created issue galley.
			$this->addCheck(new FormValidator($this, 'temporaryFileId', 'required', 'form.fileRequired'));
		}
	}

	/**
	 * Display the form.
	 */
	function fetch($request) {
		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);

		$templateMgr->assign('issueId', $this->_issue->getId());
		if ($this->_issueGalley) {
			$templateMgr->assign('issueGalleyId', $this->_issueGalley->getId());
			$templateMgr->assign('issueGalley', $this->_issueGalley);
		}
		$templateMgr->assign('supportedLocales', $journal->getSupportedLocaleNames());

		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::validate
	 */
	function validate($callHooks = true) {
		// Check if public galley ID is already being used
		$request = Application::getRequest();
		$journal = $request->getJournal();
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */

		$publicGalleyId = $this->getData('publicGalleyId');
		if ($publicGalleyId) {
			if (is_numeric($publicGalleyId)) {
				$this->addError('publicGalleyId', __('editor.publicIdentificationNumericNotAllowed', array('publicIdentifier' => $publicGalleyId)));
				$this->addErrorField('publicGalleyId');
			} elseif ($journalDao->anyPubIdExists($journal->getId(), 'publisher-id', $publicGalleyId, ASSOC_TYPE_ISSUE_GALLEY, $this->_issueGalley?$this->_issueGalley->getId():null, true)) {
				$this->addError('publicGalleyId', __('editor.publicIdentificationExistsForTheSameType', array('publicIdentifier' => $publicGalleyId)));
				$this->addErrorField('publicGalleyId');
			}
		}

		return parent::validate($callHooks);
	}

	/**
	 * Initialize form data from current galley (if applicable).
	 */
	function initData() {
		if ($this->_issueGalley) {
			$this->_data = array(
				'label' => $this->_issueGalley->getLabel(),
				'publicGalleyId' => $this->_issueGalley->getStoredPubId('publisher-id'),
				'galleyLocale' => $this->_issueGalley->getLocale()
			);
		} else {
			$this->_data = array();
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'label',
				'publicGalleyId',
				'galleyLocale',
				'temporaryFileId'
			)
		);
	}

	/**
	 * Save changes to the galley.
	 * @param $request PKPRequest
	 * @return int the galley ID
	 */
	function execute($request) {
		import('classes.file.IssueFileManager');
		$issueFileManager = new IssueFileManager($this->_issue->getId());

		$journal = $request->getJournal();
		$user = $request->getUser();

		$issueGalley = $this->_issueGalley;
		$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');

		// If a temporary file ID was specified (i.e. an upload occurred), get the file for later.
		$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
		$temporaryFile = $temporaryFileDao->getTemporaryFile($this->getData('temporaryFileId'), $user->getId());

		if ($issueGalley) {
			// Update an existing galley
			if ($temporaryFile) {
				// Galley has a file, delete it before uploading new one
				if ($issueGalley->getFileId()) {
					$issueFileManager->deleteById($issueGalley->getFileId());
				}
				// Upload new file
				$issueFile = $issueFileManager->fromTemporaryFile($temporaryFile);
				$issueGalley->setFileId($issueFile->getId());
			}

			$issueGalley->setLabel($this->getData('label'));
			$issueGalley->setStoredPubId('publisher-id', $this->getData('publicGalleyId'));
			$issueGalley->setLocale($this->getData('galleyLocale'));

			// Update galley in the db
			$issueGalleyDao->updateObject($issueGalley);
		} else {
			// Create a new galley
			$issueGalleyFile = $issueFileManager->fromTemporaryFile($temporaryFile);

			$issueGalley = $issueGalleyDao->newDataObject();
			$issueGalley->setIssueId($this->_issue->getId());
			$issueGalley->setFileId($issueGalleyFile->getId());

			if ($this->getData('label') == null) {
				// Generate initial label based on file type
				if (isset($fileType)) {
					if(strstr($fileType, 'pdf')) {
						$issueGalley->setLabel('PDF');
					} else if (strstr($fileType, 'postscript')) {
						$issueGalley->setLabel('PostScript');
					} else if (strstr($fileType, 'xml')) {
						$issueGalley->setLabel('XML');
					}
				}

				if ($issueGalley->getLabel() == null) {
					$issueGalley->setLabel(__('common.untitled'));
				}

			} else {
				$issueGalley->setLabel($this->getData('label'));
			}
			$issueGalley->setLocale($this->getData('galleyLocale'));

			$issueGalley->setStoredPubId('publisher-id', $this->getData('publicGalleyId'));

			// Insert new galley into the db
			$issueGalleyDao->insertObject($issueGalley);
			$this->_issueGalley = $issueGalley;
		}

		return $this->_issueGalley->getId();
	}
}

?>
