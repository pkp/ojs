<?php

/**
 * @file controllers/grid/files/query/QueryNoteFilesGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryNoteFilesGridDataProvider
 * @ingroup controllers_grid_files_query
 *
 * @brief Provide access to query files management.
 */


import('lib.pkp.controllers.grid.files.SubmissionFilesGridDataProvider');

class QueryNoteFilesGridDataProvider extends SubmissionFilesGridDataProvider {
	/** @var int Note ID */
	var $_noteId;

	/**
	 * Constructor
	 * @param $noteId int Note ID
	 */
	function __construct($noteId) {
		parent::__construct(SUBMISSION_FILE_QUERY);
		$this->_noteId = $noteId;
	}

	//
	// Overridden public methods from FilesGridDataProvider
	//
	/**
	 * @copydoc GridDataProvider::getAuthorizationPolicy()
	 */
	function getAuthorizationPolicy($request, $args, $roleAssignments) {
		$this->setUploaderRoles($roleAssignments);

		import('lib.pkp.classes.security.authorization.QueryAccessPolicy');
		return new QueryAccessPolicy($request, $args, $roleAssignments, $this->getStageId());
	}

	/**
	 * @copydoc FilesGridDataProvider::getSelectAction()
	 */
	function getSelectAction($request) {
		$query = $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);
		import('lib.pkp.controllers.grid.files.fileList.linkAction.SelectFilesLinkAction');
		return new SelectFilesLinkAction(
			$request,
			$this->getRequestArgs(),
			__('editor.submission.selectFiles')
		);
	}

	/**
	 * @copydoc GridDataProvider::loadData()
	 */
	function loadData($filter = array()) {
		// Retrieve all submission files for the given file query.
		$submission = $this->getSubmission();
		$query = $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);

		$noteDao = DAORegistry::getDAO('NoteDAO');
		$note = $noteDao->getById($this->_noteId);
		if ($note->getAssocType() != ASSOC_TYPE_QUERY || $note->getAssocId() != $query->getId()) {
			fatalError('Invalid note ID specified!');
		}

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$submissionFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_NOTE, $this->_noteId, $submission->getId(), $this->getFileStage());
		return $this->prepareSubmissionFileData($submissionFiles, $this->_viewableOnly, $filter);
	}

	/**
	 * @copydoc GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		$query = $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);
		$representation = $this->getAuthorizedContextObject(ASSOC_TYPE_REPRESENTATION);
		return array_merge(
			parent::getRequestArgs(),
			array(
				'assocType' => ASSOC_TYPE_NOTE,
				'assocId' => $this->_noteId,
				'queryId' => $query->getId(),
				'noteId' => $this->_noteId,
				'representationId' => $representation?$representation->getId():null,
			)
		);
	}

	/**
	 * @copydoc FilesGridDataProvider::getAddFileAction()
	 */
	function getAddFileAction($request) {
		$submission = $this->getSubmission();
		$query = $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);
		import('lib.pkp.controllers.api.file.linkAction.AddFileLinkAction');
		return new AddFileLinkAction(
			$request, $submission->getId(), $this->getStageId(),
			$this->getUploaderRoles(), null, $this->getFileStage(),
			ASSOC_TYPE_NOTE, $this->_noteId
		);
	}
}

?>
