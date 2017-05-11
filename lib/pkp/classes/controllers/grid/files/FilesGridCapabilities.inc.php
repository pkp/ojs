<?php

/**
 * @file classes/controllers/grid/files/FilesGridCapabilities.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilesGridCapabilities
 * @ingroup classes_controllers_grid_files
 *
 * @brief Defines files grid capabilities. Should be used by grid handlers
 * that handle submission files to store which capabilities the grid has.
 */

// Define the grid capabilities.
define('FILE_GRID_ADD',			0x00000001);
define('FILE_GRID_DOWNLOAD_ALL',	0x00000002);
define('FILE_GRID_DELETE',		0x00000004);
define('FILE_GRID_VIEW_NOTES',		0x00000008);
define('FILE_GRID_MANAGE',		0x00000010);
define('FILE_GRID_EDIT',		0x00000020);

class FilesGridCapabilities {

	/** @var boolean */
	var $_canAdd;

	/** @var boolean */
	var $_canViewNotes;

	/** @var boolean */
	var $_canDownloadAll;

	/** @var boolean */
	var $_canDelete;

	/** @var boolean */
	var $_canManage;

	/** @var boolean */
	var $_canEdit;

	/**
	 * Constructor
	 * @param $capabilities integer A bit map with zero or more
	 *  FILE_GRID_* capabilities set.
	 */
	function __construct($capabilities = 0) {
		$this->setCanAdd($capabilities & FILE_GRID_ADD);
		$this->setCanDownloadAll($capabilities & FILE_GRID_DOWNLOAD_ALL);
		$this->setCanDelete($capabilities & FILE_GRID_DELETE);
		$this->setCanViewNotes($capabilities & FILE_GRID_VIEW_NOTES);
		$this->setCanManage($capabilities & FILE_GRID_MANAGE);
		$this->setCanEdit($capabilities & FILE_GRID_EDIT);
	}


	//
	// Getters and Setters
	//
	/**
	 * Does this grid allow the addition of files or revisions?
	 * @return boolean
	 */
	function canAdd() {
		return $this->_canAdd;
	}

	/**
	 * Set whether or not the grid allows the addition of files or revisions.
	 * @param $canAdd boolean
	 */
	function setCanAdd($canAdd) {
		$this->_canAdd = (boolean) $canAdd;
	}

	/**
	 * Does this grid allow viewing of notes?
	 * @return boolean
	 */
	function canViewNotes() {
		return $this->_canViewNotes;
	}

	/**
	 * Set whether this grid allows viewing of notes or not.
	 * @return boolean
	 */
	function setCanViewNotes($canViewNotes) {
		$this->_canViewNotes = $canViewNotes;
	}

	/**
	 * Can the user download all files as an archive?
	 * @return boolean
	 */
	function canDownloadAll() {
		$tarBinary = Config::getVar('cli', 'tar');
		return $this->_canDownloadAll && !empty($tarBinary) && file_exists($tarBinary);
	}

	/**
	 * Set whether user can download all files as an archive or not.
	 * @return boolean
	 */
	function setCanDownloadAll($canDownloadAll) {
		$this->_canDownloadAll = $canDownloadAll;
	}

	/**
	 * Can the user delete files from this grid?
	 * @return boolean
	 */
	function canDelete() {
		return $this->_canDelete;
	}

	/**
	 * Set whether or not the user can delete files from this grid.
	 * @param $canDelete boolean
	 */
	function setCanDelete($canDelete) {
		$this->_canDelete = (boolean) $canDelete;
	}

	/**
	 * Whether the grid allows file management (select existing files to add to grid)
	 * @return boolean
	 */
	function canManage() {
		return $this->_canManage;
	}

	/**
	 * Set whether the grid allows file management (select existing files to add to grid)
	 * @return boolean
	 */
	function setCanManage($canManage) {
		$this->_canManage = $canManage;
	}

	/**
	 * Whether the grid allows file metadata editing
	 * @return boolean
	 */
	function canEdit() {
		return $this->_canEdit;
	}

	/**
	 * Set whether the grid allows file metadata editing
	 * @return boolean
	 */
	function setCanEdit($canEdit) {
		$this->_canEdit = $canEdit;
	}

	/**
	 * Get the download all link action.
	 * @param $request PKPRequest
	 * @param $files array The files to be downloaded.
	 * @param $linkParams array The link action request
	 * parameters.
	 * @return LinkAction
	 */
	function getDownloadAllAction($request, $files, $linkParams) {
		if (sizeof($files) > 0) {
			import('lib.pkp.controllers.grid.files.fileList.linkAction.DownloadAllLinkAction');
			return new DownloadAllLinkAction($request, $linkParams, $files);
		} else {
			return null;
		}
	}
}

?>
