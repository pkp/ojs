<?php
/**
 * @defgroup controllers_grid_files_fileList File List Grid
 */

/**
 * @file controllers/grid/files/fileList/FileListGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileListGridHandler
 * @ingroup controllers_grid_files_fileList
 *
 * @brief Base grid for simple file lists. This grid shows the file type in
 *  addition to the file name.
 */

import('lib.pkp.controllers.grid.files.SubmissionFilesGridHandler');

class FileListGridHandler extends SubmissionFilesGridHandler {

	/**
	 * Constructor
	 * @param $dataProvider GridDataProvider
	 * @param $stageId integer One of the WORKFLOW_STAGE_ID_* constants.
	 * @param $capabilities integer A bit map with zero or more
	 *  FILE_GRID_* capabilities set.
	 */
	function __construct($dataProvider, $stageId, $capabilities = 0) {
		parent::__construct($dataProvider, $stageId, $capabilities);
	}


	//
	// Extended methods from SubmissionFilesGridHandler.
	//
	/**
	 * @copydoc SubmissionFilesGridHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		// Add the "manage files" action if required.
		$capabilities = $this->getCapabilities();
		if($capabilities->canManage()) {
			$dataProvider = $this->getDataProvider();
			$this->addAction($dataProvider->getSelectAction($request));
		}

		// The file list grid layout has an additional file genre column.
		import('lib.pkp.controllers.grid.files.fileList.FileGenreGridColumn');
		$this->addColumn(new FileGenreGridColumn());
	}
}

?>
