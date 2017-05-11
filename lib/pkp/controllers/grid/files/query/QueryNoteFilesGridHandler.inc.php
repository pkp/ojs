<?php

/**
 * @file controllers/grid/files/query/QueryNoteFilesGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryNoteFilesGridHandler
 * @ingroup controllers_grid_files_query
 *
 * @brief Handle query files that are associated with a query
 * The participants of a query have access to the files in this grid.
 */

import('lib.pkp.controllers.grid.files.fileList.FileListGridHandler');

class QueryNoteFilesGridHandler extends FileListGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		// import app-specific grid data provider for access policies.
		$request = Application::getRequest();
		$stageId = $request->getUservar('stageId'); // authorized in authorize() method.
		import('lib.pkp.controllers.grid.files.query.QueryNoteFilesGridDataProvider');
		parent::__construct(
			new QueryNoteFilesGridDataProvider($request->getUserVar('noteId')),
			$stageId,
			FILE_GRID_ADD|FILE_GRID_DELETE|FILE_GRID_VIEW_NOTES|FILE_GRID_EDIT
		);

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR),
			array('fetchGrid', 'fetchRow', 'selectFiles')
		);

		// Set grid title.
		$this->setTitle('submission.queries.attachedFiles');
	}

	/**
	 * @copydoc SubmissionFilesGridHandler::authorize()
	 */
	function authorize($request, $args, $roleAssignments) {
		$stageId = $request->getUserVar('stageId'); // This is being validated in WorkflowStageAccessPolicy
		$this->_stageId = (int)$stageId;

		// Get the stage access policy
		import('lib.pkp.classes.security.authorization.QueryAccessPolicy');
		$queryAccessPolicy = new QueryAccessPolicy($request, $args, $roleAssignments, $stageId);
		$this->addPolicy($queryAccessPolicy);
		$result = parent::authorize($request, $args, $roleAssignments);

		if (0!=count(array_intersect(
			$this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES),
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT)
		))) {
			$this->getCapabilities()->setCanManage(true);
		}

		return $result;
	}


	//
	// Public handler methods
	//
	/**
	 * Show the form to allow the user to select files from previous stages
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function selectFiles($args, $request) {
		$submission = $this->getSubmission();
		$query = $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);

		import('lib.pkp.controllers.grid.files.query.form.ManageQueryNoteFilesForm');
		$manageQueryNoteFilesForm = new ManageQueryNoteFilesForm($submission->getId(), $query->getId(), $request->getUserVar('noteId'), $this->getRequestArgs());
		$manageQueryNoteFilesForm->initData($args, $request);
		return new JSONMessage(true, $manageQueryNoteFilesForm->fetch($request));
	}
}

?>
