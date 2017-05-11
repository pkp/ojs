<?php

/**
 * @file controllers/grid/files/dependent/DependentFilesGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DependentFilesGridDataProvider
 * @ingroup controllers_grid_files_dependent
 *
 * @brief Provide access to dependent file data for grids.
 */


import('lib.pkp.controllers.grid.files.SubmissionFilesGridDataProvider');

class DependentFilesGridDataProvider extends SubmissionFilesGridDataProvider {

	/**
	 * The submission file id for the parent file.
	 * @var int
	 */
	var $_assocId;

	/**
	 * Constructor
	 * @param $assocId int Association ID
	 */
	function __construct($assocId) {
		assert(is_numeric($assocId));
		$this->_assocId = (int) $assocId;
		parent::__construct(SUBMISSION_FILE_DEPENDENT);

	}

	/**
	 * @copydoc GridDataProvider::loadData()
	 */
	function loadData($filter = array()) {
		// Retrieve all dependent files for the given file stage and original submission file id (i.e. the main galley/production file)
		$submission = $this->getSubmission();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$submissionFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $this->getAssocId(), $submission->getId(), $this->getFileStage());
		return $this->prepareSubmissionFileData($submissionFiles, $this->_viewableOnly, $filter);
	}

	/**
	 * Overridden from SubmissionFilesGridDataProvider - we need to also include the assocType and assocId
	 * @copydoc FilesGridDataProvider::getAddFileAction()
	 */
	function getAddFileAction($request) {
		import('lib.pkp.controllers.api.file.linkAction.AddFileLinkAction');
		$submission = $this->getSubmission();
		return new AddFileLinkAction(
			$request, $submission->getId(), $this->getStageId(),
			$this->getUploaderRoles(), null, $this->getFileStage(),
			ASSOC_TYPE_SUBMISSION_FILE, $this->getAssocId(), null,
			null, $this->isDependent()
		);
	}

	/**
	 * returns the id of the parent submission file for these dependent files.
	 * @return int
	 */
	function getAssocId() {
		return $this->_assocId;
	}

	/**
	 * Convenience function to make the argument to the AddFileLinkAction more obvious.
	 * @return true
	 */
	function isDependent() {
		return true;
	}
}

?>
