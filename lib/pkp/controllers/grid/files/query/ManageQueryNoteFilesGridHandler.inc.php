<?php

/**
 * @file controllers/grid/files/query/ManageQueryNoteFilesGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageQueryNoteFilesGridHandler
 * @ingroup controllers_grid_files_query
 *
 * @brief Handle the query file selection grid
 */

import('lib.pkp.controllers.grid.files.SelectableSubmissionFileListCategoryGridHandler');

class ManageQueryNoteFilesGridHandler extends SelectableSubmissionFileListCategoryGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		import('lib.pkp.controllers.grid.files.query.QueryNoteFilesCategoryGridDataProvider');
		$request = Application::getRequest();
		$stageId = $request->getUservar('stageId'); // authorized by data provider.
		parent::__construct(
			new QueryNoteFilesCategoryGridDataProvider(),
			$stageId,
			FILE_GRID_DELETE|FILE_GRID_VIEW_NOTES|FILE_GRID_EDIT
		);

		$this->addRoleAssignment(
			array(
				ROLE_ID_SUB_EDITOR,
				ROLE_ID_MANAGER,
				ROLE_ID_ASSISTANT
			),
			array(
				'fetchGrid', 'fetchCategory', 'fetchRow',
				'addFile',
				'downloadFile',
				'deleteFile',
				'updateQueryNoteFiles'
			)
		);

		// Set the grid title.
		$this->setTitle('submission.queryNoteFiles');
	}


	//
	// Override methods from SelectableSubmissionFileListCategoryGridHandler
	//
	/**
	 * @copydoc GridHandler::isDataElementInCategorySelected()
	 */
	function isDataElementInCategorySelected($categoryDataId, &$gridDataElement) {
		$submissionFile = $gridDataElement['submissionFile'];

		// Check for special cases when the file needs to be unselected.
		$dataProvider = $this->getDataProvider();
		if ($dataProvider->getFileStage() != $submissionFile->getFileStage()) return false;

		// Passed the checks above. If it's part of the current query, mark selected.
		$query = $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);
		$headNote = $query->getHeadNote();
		return ($submissionFile->getAssocType() == ASSOC_TYPE_NOTE && $submissionFile->getAssocId() == $headNote->getId());
	}

	//
	// Public handler methods
	//
	/**
	 * Save 'manage query files' form
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateQueryNoteFiles($args, $request) {
		$submission = $this->getSubmission();
		$query = $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);

		import('lib.pkp.controllers.grid.files.query.form.ManageQueryNoteFilesForm');
		$manageQueryNoteFilesForm = new ManageQueryNoteFilesForm($submission->getId(), $query->getId(), $request->getUserVar('noteId'));
		$manageQueryNoteFilesForm->readInputData();

		if ($manageQueryNoteFilesForm->validate()) {
			$manageQueryNoteFilesForm->execute(
				$args, $request,
				$this->getGridCategoryDataElements($request, $this->getStageId())
			);

			// Let the calling grid reload itself
			return DAO::getDataChangedEvent();
		} else {
			return new JSONMessage(false);
		}
	}
}

?>
