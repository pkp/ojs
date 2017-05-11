<?php

/**
 * @file controllers/wizard/fileUpload/form/SubmissionFilesMetadataForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFilesMetadataForm
 * @ingroup controllers_wizard_fileUpload_form
 *
 * @brief Form for editing a submission file's metadata
 */

import('lib.pkp.classes.form.Form');

class SubmissionFilesMetadataForm extends Form {

	/** @var SubmissionFile */
	var $_submissionFile;

	/** @var integer */
	var $_stageId;

	/** @var ReviewRound */
	var $_reviewRound;

	/**
	 * Constructor.
	 * @param $submissionFile SubmissionFile
	 * @param $stageId int One of the WORKFLOW_STAGE_ID_* constants.
	 * @param $reviewRound ReviewRound (optional) Current review round, if any.
	 * @param $template string Path and filename to template file (optional).
	 */
	function __construct($submissionFile, $stageId, $reviewRound = null, $template = null) {
		if ($template === null) $template = 'controllers/wizard/fileUpload/form/submissionFileMetadataForm.tpl';
		parent::__construct($template);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

		// Initialize the object.
		$this->_submissionFile = $submissionFile;
		$this->_stageId = $stageId;
		if (is_a($reviewRound, 'ReviewRound')) {
			$this->_reviewRound = $reviewRound;
		}

		$submissionLocale = $submissionFile->getSubmissionLocale();
		$this->setDefaultFormLocale($submissionLocale);

		// Add validation checks.
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'submission.submit.fileNameRequired', $submissionLocale));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the submission file.
	 * @return SubmissionFile
	 */
	function getSubmissionFile() {
		return $this->_submissionFile;
	}

	/**
	 * Get the workflow stage id.
	 * @return integer
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get review round.
	 * @return ReviewRound
	 */
	function getReviewRound() {
		return $this->_reviewRound;
	}

	/**
	 * Set the "show buttons" flag
	 * @param $showButtons boolean
	 */
	function setShowButtons($showButtons) {
		$this->setData('showButtons', $showButtons);
	}

	/**
	 * Get the "show buttons" flag
	 * @return boolean
	 */
	function getShowButtons() {
		return $this->getData('showButtons');
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('name');
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'showButtons'));
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$reviewRound = $this->getReviewRound();
		$templateMgr->assign(array(
			'submissionFile' => $this->getSubmissionFile(),
			'stageId' => $this->getStageId(),
			'reviewRoundId' => $reviewRound?$reviewRound->getId():null
		));
		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute($args, $request) {
		// Update the submission file with data from the form.
		$submissionFile = $this->getSubmissionFile();
		$submissionFile->setName($this->getData('name'), null); // Localized
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$submissionFileDao->updateObject($submissionFile);
	}
}

?>
