<?php
/**
 * @file controllers/grid/files/fileList/SelectableFileListGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SelectableFileListGridHandler
 * @ingroup controllers_grid_files_fileList
 *
 * @brief Base grid for selectable file lists. The grid use the SelectableItemFeature
 * to show a check box for each row so that the user can make a selection
 * among grid entries.
 */

import('lib.pkp.controllers.grid.files.fileList.FileListGridHandler');

class SelectableFileListGridHandler extends FileListGridHandler {

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
	// Overriden methods from GridHandler.
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.selectableItems.SelectableItemsFeature');
		return array(new SelectableItemsFeature());
	}


	//
	// Implemented methods from GridHandler.
	//
	/**
	 * @copydoc GridHandler::isDataElementSelected()
	 */
	function isDataElementSelected($gridDataElement) {
		$file = $gridDataElement['submissionFile'];
		return $file->getViewable();
	}

	/**
	 * @copydoc GridHandler::getSelectName()
	 */
	function getSelectName() {
		return 'selectedFiles';
	}
}

?>
