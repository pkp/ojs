<?php

/**
 * @file controllers/grid/issues/form/IssueGalleyForm.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
	function IssueGalleyForm($request, $issue, $issueGalley = null) {
		parent::Form('controllers/grid/issueGalleys/form/issueGalleyForm.tpl');
		$this->_issue = $issue;
		$this->_issueGalley = $issueGalley;

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION);

		$this->addCheck(new FormValidator($this, 'label', 'required', 'editor.issues.galleyLabelRequired'));
		$this->addCheck(new FormValidatorPost($this));

		// Ensure a locale is provided and valid
		$journal = $request->getJournal();
		$this->addCheck(
			new FormValidator(
				$this,
				'galleyLocale',
				'required',
				'editor.issues.galleyLocaleRequired'
			),
			create_function(
				'$galleyLocale, $availableLocales',
				'return in_array($galleyLocale, $availableLocales);'
			),
			array_keys($journal->getSupportedLocaleNames())
		);

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
		$templateMgr->assign('enablePublicGalleyId', $journal->getSetting('enablePublicGalleyId'));

		return parent::fetch($request);
	}

	/**
	 * Validate the form
	 */
	function validate($request) {
		// Check if public galley ID is already being used
		$journal = $request->getJournal();
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */

		$publicGalleyId = $this->getData('publicGalleyId');
		if ($publicGalleyId && $journalDao->anyPubIdExists($journal->getId(), 'publisher-id', $publicGalleyId, ASSOC_TYPE_ISSUE_GALLEY, $this->_issueGalley?$this->_issueGalley->getId():null)) {
			$this->addError('publicGalleyId', __('editor.publicIdentificationExists', array('publicIdentifier' => $publicGalleyId)));
			$this->addErrorField('publicGalleyId');
		}

		return parent::validate();
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
					$issueFileManager->deleteFile($issueGalley->getFileId());
				}
				// Upload new file
				$issueFile = $issueFileManager->fromTemporaryFile($temporaryFile);
				$issueGalley->setFileId($issueFile->getFileId());
			}

			$issueGalley->setLabel($this->getData('label'));
			if ($journal->getSetting('enablePublicGalleyId')) {
				$issueGalley->setStoredPubId('publisher-id', $this->getData('publicGalleyId'));
			}
			$issueGalley->setLocale($this->getData('galleyLocale'));

			// Update galley in the db
			$issueGalleyDao->updateObject($issueGalley);
		} else {
			// Create a new galley
			$issueGalleyFile = $issueFileManager->fromTemporaryFile($temporaryFile);

			$issueGalley = $issueGalleyDao->newDataObject();
			$issueGalley->setIssueId($this->_issue->getId());
			$issueGalley->setFileId($issueGalleyFile->getId());

			$enablePublicGalleyId = $journal->getSetting('enablePublicGalleyId');

			if ($this->getData('label') == null) {
				// Generate initial label based on file type
				if (isset($fileType)) {
					if(strstr($fileType, 'pdf')) {
						$issueGalley->setLabel('PDF');
						if ($enablePublicGalleyId) $issueGalley->setStoredPubId('publisher-id', 'pdf');
					} else if (strstr($fileType, 'postscript')) {
						$issueGalley->setLabel('PostScript');
						if ($enablePublicGalleyId) $issueGalley->setStoredPubId('publisher-id', 'ps');
					} else if (strstr($fileType, 'xml')) {
						$issueGalley->setLabel('XML');
						if ($enablePublicGalleyId) $issueGalley->setStoredPubId('publisher-id', 'xml');
					}
				}

				if ($issueGalley->getLabel() == null) {
					$issueGalley->setLabel(__('common.untitled'));
				}

			} else {
				$issueGalley->setLabel($this->getData('label'));
			}
			$issueGalley->setLocale($this->getData('galleyLocale'));

			if ($enablePublicGalleyId) {
				// Ensure the assigned public id doesn't already exist
				$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
				$publicGalleyId = $issueGalley->getPubId('publisher-id');
				$suffix = '';
				$i = 1;
				while ($journalDao->anyPubIdExists($journal->getId(), 'publisher-id', $publicGalleyId . $suffix)) {
					$suffix = '_'.$i++;
				}

				$issueGalley->setStoredPubId('publisher-id', $publicGalleyId . $suffix);
			}

			// Insert new galley into the db
			$issueGalleyDao->insertObject($issueGalley);
			$this->_issueGalley = $issueGalley;
		}

		return $this->_issueGalley->getId();
	}
}

?>
