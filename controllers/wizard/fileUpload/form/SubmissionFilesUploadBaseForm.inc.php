<?php

/**
 * @file controllers/wizard/fileUpload/form/SubmissionFilesUploadBaseForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFilesUploadBaseForm
 * @ingroup controllers_wizard_fileUpload_form
 *
 * @brief Form for adding/editing a submission file
 */


import('lib.pkp.controllers.wizard.fileUpload.form.PKPSubmissionFilesUploadBaseForm');

class SubmissionFilesUploadBaseForm extends PKPSubmissionFilesUploadBaseForm {

	/**
	 * Constructor.
	 * @param $request Request
	 * @param $template string
	 * @param $submissionId integer
	 * @param $stageId integer One of the WORKFLOW_STAGE_ID_* constants.
	 * @param $fileStage integer
	 * @param $revisionOnly boolean
	 * @param $reviewRound ReviewRound
	 * @param $revisedFileId integer
	 */
	function __construct($request, $template, $submissionId, $stageId, $fileStage,
			$revisionOnly = false, $reviewRound = null, $revisedFileId = null, $assocType = null, $assocId = null) {
		parent::__construct($request, $template, $submissionId, $stageId, $fileStage,
				$revisionOnly, $reviewRound, $revisedFileId, $assocType, $assocId);
	}

	/**
	 * @copydoc PKPSubmissionFilesUploadBaseForm::getSubmissionFiles()
	 * This function exists in this subclass for OJS-specific submission files.
	 *
	 * For now, this simply uses the parent method.  Having this allows for different sets of submission files
	 * depending on stageId or fileStage.  See e.g. this class in the OMP codebase as an example.
	 */
	function getSubmissionFiles() {
		if (is_null($this->_submissionFiles)) {
			parent::getSubmissionFiles();
		}

		return $this->_submissionFiles;
	}
}

?>
