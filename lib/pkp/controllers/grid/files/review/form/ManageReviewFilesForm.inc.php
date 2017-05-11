<?php

/**
 * @file controllers/grid/files/review/form/ManageReviewFilesForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageReviewFilesForm
 * @ingroup controllers_grid_files_review_form
 *
 * @brief Form for add or removing files from a review
 */

import('lib.pkp.controllers.grid.files.form.ManageSubmissionFilesForm');

class ManageReviewFilesForm extends ManageSubmissionFilesForm {

	/** @var int **/
	var $_stageId;

	/** @var int **/
	var $_reviewRoundId;


	/**
	 * Constructor.
	 */
	function __construct($submissionId, $stageId, $reviewRoundId) {
		parent::__construct($submissionId, 'controllers/grid/files/review/manageReviewFiles.tpl');
		$this->_stageId = (int)$stageId;
		$this->_reviewRoundId = (int)$reviewRoundId;
	}


	//
	// Getters / Setters
	//
	/**
	 * Get the review stage id
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the round
	 * @return int
	 */
	function getReviewRoundId() {
		return $this->_reviewRoundId;
	}

	/**
	 * @return ReviewRound
	 */
	function getReviewRound() {
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		return $reviewRoundDao->getById($this->getReviewRoundId());
	}


	//
	// Overridden template methods
	//
	/**
	 * Initialize variables
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, $request) {
		$this->setData('stageId', $this->getStageId());
		$this->setData('reviewRoundId', $this->getReviewRoundId());

		$reviewRound = $this->getReviewRound();
		$this->setData('round', $reviewRound->getRound());

		parent::initData($args, $request);
	}

	/**
	 * Save review round files
	 * @param $args array
	 * @param $request PKPRequest
	 * @stageSubmissionFiles array The files that belongs to a file stage
	 * that is currently being used by a grid inside this form.
	 */
	function execute($args, $request, $stageSubmissionFiles) {
		parent::execute($args, $request, $stageSubmissionFiles, SUBMISSION_FILE_REVIEW_FILE);
	}

	/**
	 * @copydoc ManageSubmissionFilesForm::importFile()
	 */
	protected function importFile($context, $submissionFile, $fileStage) {
		$newSubmissionFile = parent::importFile($context, $submissionFile, $fileStage);

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFileDao->assignRevisionToReviewRound($newSubmissionFile->getFileId(), $newSubmissionFile->getRevision(), $this->getReviewRound());
	}
}

?>
