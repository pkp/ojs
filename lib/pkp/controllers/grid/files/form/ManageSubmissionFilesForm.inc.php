<?php

/**
 * @file controllers/grid/files/form/ManageSubmissionFilesForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageSubmissionFilesForm
 * @ingroup controllers_grid_files_form
 *
 * @brief Form for add or removing files from a review
 */

import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.submission.SubmissionFile');

class ManageSubmissionFilesForm extends Form {
	/** @var int **/
	var $_submissionId;

	/**
	 * Constructor.
	 * @param $submissionId int Submission ID
	 * @param $template string Template filename
	 */
	function __construct($submissionId, $template) {
		parent::__construct($template);
		$this->_submissionId = (int)$submissionId;

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}


	//
	// Getters / Setters
	//
	/**
	 * Get the submission id
	 * @return int
	 */
	function getSubmissionId() {
		return $this->_submissionId;
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
		$this->setData('submissionId', $this->_submissionId);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('selectedFiles'));
	}

	/**
	 * Save selection of submission files
	 * @param $args array
	 * @param $request PKPRequest
	 * @param $stageSubmissionFiles array The files that belongs to a file stage
	 * that is currently being used by a grid inside this form.
	 * @param $fileStage int SUBMISSION_FILE_...
	 */
	function execute($args, $request, $stageSubmissionFiles, $fileStage) {
		$selectedFiles = (array)$this->getData('selectedFiles');
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFiles = $submissionFileDao->getLatestRevisions($this->getSubmissionId());

		foreach ($submissionFiles as $submissionFile) {
			// Get the viewable flag value.
			$isViewable = in_array(
				$submissionFile->getFileId(),
				$selectedFiles
			);

			// If this is a submission file that's already in this listing...
			if ($this->fileExistsInStage($submissionFile, $stageSubmissionFiles, $fileStage)) {
				// ...update the "viewable" flag accordingly.
				if ($isViewable != $submissionFile->getViewable()) {
					$submissionFile->setViewable($isViewable);
					$submissionFileDao->updateObject($submissionFile);
				}
			} elseif ($isViewable) {
				// Import a file from a different workflow area
				$context = $request->getContext();
				$submissionFile = $this->importFile($context, $submissionFile, $fileStage);
			}
		}
	}

	/**
	 * Determine if a file with the same file stage is already present in the workflow stage.
	 * @param $submissionFile SubmissionFile The submission file
	 * @param $stageSubmissionFiles array The list of submission files in the stage.
	 * @param $fileStage int FILE_STAGE_...
	 */
	protected function fileExistsInStage($submissionFile, $stageSubmissionFiles, $fileStage) {
		if (!isset($stageSubmissionFiles[$submissionFile->getFileId()])) return false;
		foreach ($stageSubmissionFiles[$submissionFile->getFileId()] as $stageFile) {
			if ($stageFile->getFileStage() == $submissionFile->getFileStage() && $stageFile->getFileStage() == $fileStage) return true;
		}
		return false;
	}

	/**
	 * Make a copy of the file to the specified file stage.
	 * @param $context Context
	 * @param $submissionFile SubmissionFile
	 * @param $fileStage int SUBMISSION_FILE_...
	 * @return SubmissionFile Resultant new submission file
	 */
	protected function importFile($context, $submissionFile, $fileStage) {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileManager = new SubmissionFileManager($context->getId(), $submissionFile->getSubmissionId());
		// Split the file into file id and file revision.
		$fileId = $submissionFile->getFileId();
		$revision = $submissionFile->getRevision();
		list($newFileId, $newRevision) = $submissionFileManager->copyFileToFileStage($fileId, $revision, $fileStage, null, true);
		return $submissionFileDao->getRevision($newFileId, $newRevision);
	}
}

?>
