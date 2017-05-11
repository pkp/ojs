<?php

/**
 * @file controllers/grid/files/final/FinalDraftFilesGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FinalDraftFilesGridHandler
 * @ingroup controllers_grid_files_final
 *
 * @brief Handle the final draft files grid (displays files sent to copyediting from the review stage)
 */

import('lib.pkp.controllers.grid.files.fileList.FileListGridHandler');

class FinalDraftFilesGridHandler extends FileListGridHandler {
	/**
	 * Constructor
	 *  FILE_GRID_* capabilities set.
	 */
	function __construct() {
		import('lib.pkp.controllers.grid.files.final.FinalDraftFilesGridDataProvider');
		parent::__construct(
			new FinalDraftFilesGridDataProvider(),
			null,
			FILE_GRID_DELETE|FILE_GRID_EDIT|FILE_GRID_MANAGE|FILE_GRID_VIEW_NOTES
		);
		$this->addRoleAssignment(
			array(
				ROLE_ID_SUB_EDITOR,
				ROLE_ID_MANAGER,
				ROLE_ID_ASSISTANT
			),
			array(
				'fetchGrid', 'fetchRow', 'selectFiles'
			)
		);

		$this->setTitle('submission.finalDraft');
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
		import('lib.pkp.controllers.grid.files.final.form.ManageFinalDraftFilesForm');
		$manageFinalDraftFilesForm = new ManageFinalDraftFilesForm($this->getSubmission()->getId());
		$manageFinalDraftFilesForm->initData($args, $request);
		return new JSONMessage(true, $manageFinalDraftFilesForm->fetch($request));
	}
}

?>
