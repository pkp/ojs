<?php

/**
 * @file controllers/grid/articleGalleys/form/ArticleGalleyForm.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyForm
 * @ingroup controllers_grid_articleGalleys_form
 * @see ArticleGalley
 *
 * @brief Article galley editing form.
 */

import('lib.pkp.classes.form.Form');

class ArticleGalleyForm extends Form {
	/** @var the article */
	var $_submission = null;

	/** @var ArticleGalley current galley */
	var $_articleGalley = null;

	/**
	 * Constructor.
	 * @param $submission Submission
	 * @param $articleGalley ArticleGalley (optional)
	 */
	function ArticleGalleyForm($request, $submission, $articleGalley = null) {
		parent::Form('controllers/grid/articleGalleys/form/articleGalleyForm.tpl');
		$this->_submission = $submission;
		$this->_articleGalley = $articleGalley;

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
	}

	/**
	 * Display the form.
	 */
	function fetch($request) {
		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);
		if ($this->_articleGalley) $templateMgr->assign(array(
			'representationId' => $this->_articleGalley->getId(),
			'articleGalley' => $this->_articleGalley,
			'articleGalleyFile' => $this->_articleGalley->getFile(),
		));
		$templateMgr->assign(array(
			'supportedLocales' => $journal->getSupportedLocaleNames(),
			'enablePublicGalleyId' => $journal->getSetting('enablePublicGalleyId'),
			'submissionId' => $this->_submission->getId(),
		));

		return parent::fetch($request);
	}

	/**
	 * Validate the form
	 */
	function validate($request) {
		// Check if public galley ID is already being used
		$journal = $request->getJournal();
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $journalDao JournalDAO */

		$publicGalleyId = $this->getData('publicGalleyId');
		if ($publicGalleyId && $articleGalleyDao->pubIdExists('publisher-id', $publicGalleyId, $this->_articleGalley?$this->_articleGalley->getId():null, $journal->getId())) {
			$this->addError('publicGalleyId', __('editor.publicIdentificationExists', array('publicIdentifier' => $publicGalleyId)));
			$this->addErrorField('publicGalleyId');
		}

		return parent::validate();
	}

	/**
	 * Initialize form data from current galley (if applicable).
	 */
	function initData() {
		if ($this->_articleGalley) {
			$this->_data = array(
				'label' => $this->_articleGalley->getLabel(),
				'publicGalleyId' => $this->_articleGalley->getStoredPubId('publisher-id'),
				'galleyLocale' => $this->_articleGalley->getLocale(),
				'remoteURL' => $this->_articleGalley->getRemoteURL(),
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
				'remoteURL',
			)
		);
	}

	/**
	 * Save changes to the galley.
	 * @param $request PKPRequest
	 * @return ArticleGalley The resulting article galley.
	 */
	function execute($request) {
		import('classes.file.IssueFileManager');

		$journal = $request->getJournal();
		$articleGalley = $this->_articleGalley;
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');

		if ($articleGalley) {
			$articleGalley->setLabel($this->getData('label'));
			if ($journal->getSetting('enablePublicGalleyId')) {
				$articleGalley->setStoredPubId('publisher-id', $this->getData('publicGalleyId'));
			}
			$articleGalley->setLocale($this->getData('galleyLocale'));
			$articleGalley->setRemoteURL($this->getData('remoteURL'));

			// Update galley in the db
			$articleGalleyDao->updateObject($articleGalley);
		} else {
			// Create a new galley
			$articleGalley = $articleGalleyDao->newDataObject();
			$articleGalley->setSubmissionId($this->_submission->getId());
			$articleGalley->setLabel($this->getData('label'));
			if ($journal->getSetting('enablePublicGalleyId')) {
				$articleGalley->setStoredPubId('publisher-id', $this->getData('publicGalleyId'));
			}

			$articleGalley->setLocale($this->getData('galleyLocale'));
			$articleGalley->setRemoteURL($this->getData('remoteURL'));

			// Insert new galley into the db
			$articleGalleyDao->insertObject($articleGalley);
			$this->_articleGalley = $articleGalley;
		}

		return $articleGalley;
	}
}

?>
