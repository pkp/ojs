<?php

/**
 * @file controllers/grid/files/FileNameGridColumn.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileNameGridColumn
 * @ingroup controllers_grid_files
 *
 * @brief Implements a file name column.
 */

import('lib.pkp.classes.controllers.grid.GridColumn');

class FileNameGridColumn extends GridColumn {
	/** @var boolean */
	var $_includeNotes;

	/** @var int */
	var $_stageId;

	/** @var boolean */
	var $_removeHistoryTab;

	/**
	 * Constructor
	 * @param $includeNotes boolean
	 * @param $stageId int (optional)
	 * @param $removeHistoryTab boolean (optional) Open the information center
	 * without the history tab.
	 */
	function __construct($includeNotes = true, $stageId = null, $removeHistoryTab = false) {
		$this->_includeNotes = $includeNotes;
		$this->_stageId = $stageId;
		$this->_removeHistoryTab = $removeHistoryTab;

		import('lib.pkp.classes.controllers.grid.ColumnBasedGridCellProvider');
		$cellProvider = new ColumnBasedGridCellProvider();

		parent::__construct('name', 'common.name', null, null, $cellProvider,
			array('width' => 70, 'alignment' => COLUMN_ALIGNMENT_LEFT, 'anyhtml' => true));
	}


	//
	// Public methods
	//
	/**
	 * Method expected by ColumnBasedGridCellProvider
	 * to render a cell in this column.
	 *
	 * @copydoc ColumnBasedGridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRow($row) {
		$submissionFileData = $row->getData();
		$submissionFile = $submissionFileData['submissionFile'];
		assert(is_a($submissionFile, 'SubmissionFile'));
		$id = $submissionFile->getFileId() . '-' . $submissionFile->getRevision();
		$fileExtension = strtolower($submissionFile->getExtension());
		return array('label' => '<span class="file_extension ' . $fileExtension . '">' . $id . '</span>');
	}


	//
	// Override methods from GridColumn
	//
	/**
	 * @copydoc GridColumn::getCellActions()
	 */
	function getCellActions($request, $row, $position = GRID_ACTION_POSITION_DEFAULT) {
		$cellActions = parent::getCellActions($request, $row, $position);

		// Retrieve the submission file.
		$submissionFileData =& $row->getData();
		assert(isset($submissionFileData['submissionFile']));
		$submissionFile = $submissionFileData['submissionFile']; /* @var $submissionFile SubmissionFile */

		// Create the cell action to download a file.
		import('lib.pkp.controllers.api.file.linkAction.DownloadFileLinkAction');
		$cellActions[] = new DownloadFileLinkAction($request, $submissionFile, $this->_getStageId());

		return $cellActions;
	}

	//
	// Private methods
	//
	/**
	 * Determine whether or not submission note status should be included.
	 */
	function _getIncludeNotes() {
		return $this->_includeNotes;
	}

	/**
	 * Get stage id, if any.
	 * @return mixed int or null
	 */
	function _getStageId() {
		return $this->_stageId;
	}
}

?>
