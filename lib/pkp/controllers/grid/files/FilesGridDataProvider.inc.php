<?php

/**
 * @file controllers/grid/files/FilesGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilesGridDataProvider
 * @ingroup controllers_grid_files
 *
 * @brief Basic files grid data provider.
 */


import('lib.pkp.classes.controllers.grid.GridDataProvider');

class FilesGridDataProvider extends GridDataProvider {

	/* @var integer */
	var $_uploaderRoles;

	/* @var array */
	var $_uploaderGroupIds = null;

	/** @var boolean */
	var $_viewableOnly = false;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Getters and Setters
	//
	/**
	 * Set the uploader roles.
	 * @param $roleAssignments array The grid's
	 *  role assignment from which the uploader roles
	 *  will be extracted.
	 */
	function setUploaderRoles($roleAssignments) {
		$this->_uploaderRoles = array_keys($roleAssignments);
	}

	/**
	 * Get the uploader roles.
	 * @return array
	 */
	function getUploaderRoles() {
		assert(is_array($this->_uploaderRoles) && !empty($this->_uploaderRoles));
		return $this->_uploaderRoles;
	}

	/**
	 * Set the uploader group IDs.
	 * @param $groupIds array The group IDs to consider
	 *  when presenting the file upload modal.
	 */
	function setUploaderGroupIds($uploaderGroupIds) {
		$this->_uploaderGroupIds = $uploaderGroupIds;
	}

	/**
	 * Get the uploader group IDs.
	 * @return array
	 */
	function getUploaderGroupIds() {
		assert(!isset($this->_uploaderGroupIds) || is_array($this->_uploaderGroupIds));
		return $this->_uploaderGroupIds;
	}

	/**
	 * Load only viewable files flag.
	 * @param $viewableOnly boolean
	 */
	function setViewableOnly($viewableOnly) {
		$this->_viewableOnly = $viewableOnly;
	}


	//
	// Public helper methods
	//
	/**
	 * Configures and returns the action to add a file.
	 *
	 * NB: Must be overridden by subclasses (if implemented).
	 *
	 * @param $request Request
	 *
	 * @return AddFileLinkAction
	 */
	function getAddFileAction($request) {
		assert(false);
	}

	/**
	 * Configures and returns the select files action.
	 *
	 * NB: Must be overridden by subclasses (if implemented).
	 *
	 * @param $request Request
	 *
	 * @return SelectFilesLinkAction
	 */
	function getSelectAction($request) {
		assert(false);
	}


	//
	// Protected helper methods
	//
	/**
	 * Get the authorized submission.
	 * @return Submission
	 */
	protected function getSubmission() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}
}

?>
