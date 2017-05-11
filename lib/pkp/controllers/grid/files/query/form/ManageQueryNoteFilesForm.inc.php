<?php

/**
 * @file controllers/grid/files/query/form/ManageQueryNoteFilesForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageQueryNoteFilesForm
 * @ingroup controllers_grid_files_query
 *
 * @brief Form to add files to the query files grid
 */

import('lib.pkp.controllers.grid.files.form.ManageSubmissionFilesForm');

class ManageQueryNoteFilesForm extends ManageSubmissionFilesForm {
	/** @var int Query ID */
	var $_queryId;

	/** @var int Note ID */
	var $_noteId;

	/** @var array Extra parameters to actions. */
	var $_actionArgs;

	/**
	 * Constructor.
	 * @param $submissionId int Submission ID.
	 * @param $queryId int Query ID.
	 * @param $noteId int Note ID.
	 * @param $actionArgs array Optional list of extra request parameters.
	 */
	function __construct($submissionId, $queryId, $noteId, $actionArgs = array()) {
		parent::__construct($submissionId, 'controllers/grid/files/query/manageQueryNoteFiles.tpl');
		$this->_queryId = $queryId;
		$this->_noteId = $noteId;
		$this->_actionArgs = $actionArgs;
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'queryId' => $this->_queryId,
			'noteId' => $this->_noteId,
			'actionArgs' => $this->_actionArgs,
		));
		return parent::fetch($request, $template, $display);
	}

	/**
	 * Save selection of query files
	 * @param $args array
	 * @param $request PKPRequest
	 * @param $stageSubmissionFiles array The list of submission files in the stage.
	 */
	function execute($args, $request, $stageSubmissionFiles) {
		parent::execute($args, $request, $stageSubmissionFiles, SUBMISSION_FILE_QUERY);
	}

	/**
	 * @copydoc ManageSubmissionFilesForm::fileExistsInStage 
	 */
	protected function fileExistsInStage($submissionFile, $stageSubmissionFiles, $fileStage) {
		if (!parent::fileExistsInStage($submissionFile, $stageSubmissionFiles, $fileStage)) return false;
		foreach ($stageSubmissionFiles[$submissionFile->getFileId()] as $stageFile) {
			if (
				$stageFile->getFileStage() == $submissionFile->getFileStage() &&
				$stageFile->getFileStage() == $fileStage &&
				($stageFile->getAssocType() != ASSOC_TYPE_NOTE || $stageFile->getAssocId() == $this->_noteId)
			) return true;
		}
		return false;
	}

	/**
	 * @copydoc ManageSubmissionFilesForm::importFile()
	 */
	protected function importFile($context, $submissionFile, $fileStage) {
		$submissionFile = parent::importFile($context, $submissionFile, $fileStage);
		$submissionFile->setAssocType(ASSOC_TYPE_NOTE);
		$submissionFile->setAssocId($this->_noteId);
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFileDao->updateObject($submissionFile);
		return $submissionFile;
	}
}

?>
