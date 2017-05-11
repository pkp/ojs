<?php

/**
 * @file controllers/grid/files/SubmissionFilesGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFilesGridRow
 * @ingroup controllers_grid_files
 *
 * @brief Handle submission file grid row requests.
 */

// Import grid base classes.
import('lib.pkp.classes.controllers.grid.GridRow');

class SubmissionFilesGridRow extends GridRow {
	/** @var FilesGridCapabilities */
	var $_capabilities;

	/** @var int */
	var $_stageId;

	/**
	 * Constructor
	 * @param $capabilities FilesGridCapabilities
	 * @param $stageId int Stage ID (optional)
	 */
	function __construct($capabilities = null, $stageId = null) {
		$this->_capabilities = $capabilities;
		$this->_stageId = $stageId;
		parent::__construct();
	}


	//
	// Getters and Setters
	//
	/**
	 * Can the user delete files from this grid?
	 * @return boolean
	 */
	function canDelete() {
		return $this->_capabilities->canDelete();
	}

	/**
	 * Can the user view file notes on this grid?
	 * @return boolean
	 */
	function canViewNotes() {
		return $this->_capabilities->canViewNotes();
	}

	/**
	 * Can the user manage files in this grid?
	 * @return boolean
	 */
	function canEdit() {
		return $this->_capabilities->canEdit();
	}

	/**
	 * Get the stage id, if any.
	 * @return int Stage ID
	 */
	function getStageId() {
		return $this->_stageId;
	}

	//
	// Overridden template methods from GridRow
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $template = 'controllers/grid/gridRow.tpl') {
		parent::initialize($request, $template);

		// Retrieve the submission file.
		$submissionFileData =& $this->getData();
		assert(isset($submissionFileData['submissionFile']));
		$submissionFile =& $submissionFileData['submissionFile']; /* @var $submissionFile SubmissionFile */
		assert(is_a($submissionFile, 'SubmissionFile'));

		// File grid row actions:
		// 1) Information center action.
		if ($this->canViewNotes()) {
			import('lib.pkp.controllers.informationCenter.linkAction.FileInfoCenterLinkAction');
			$this->addAction(new FileInfoCenterLinkAction($request, $submissionFile, $this->getStageId()));
		}

		// 2) Edit metadata action.
		if ($this->canEdit()) {
			import('lib.pkp.controllers.api.file.linkAction.EditFileLinkAction');
			$this->addAction(new EditFileLinkAction($request, $submissionFile, $this->getStageId()));
		}

		// 3) Delete file action.
		if ($this->canDelete()) {
			import('lib.pkp.controllers.api.file.linkAction.DeleteFileLinkAction');
			$this->addAction(new DeleteFileLinkAction($request, $submissionFile, $this->getStageId()));
		}
	}
}

?>
