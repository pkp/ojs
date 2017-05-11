<?php

/**
 * @file controllers/grid/settings/library/LibraryFileAdminGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilesGridDataProvider
 * @ingroup controllers_grid_settings_library
 *
 * @brief The data provider for the admin library files grid.
 */


import('lib.pkp.classes.controllers.grid.CategoryGridDataProvider');

class LibraryFileAdminGridDataProvider extends CategoryGridDataProvider {

	/** the context for this library **/
	var $_context;

	/** whether or not this grid is editable **/
	var $_canEdit;

	/**
	 * Constructor
	 */
	function __construct($canEdit) {
		$this->_canEdit = $canEdit;
		parent::__construct();
	}


	//
	// Getters and Setters
	//

	/**
	 * @copydoc GridDataProvider::getAuthorizationPolicy()
	 */
	function getAuthorizationPolicy($request, $args, $roleAssignments) {
		$this->_context = $request->getContext();
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		return new ContextAccessPolicy($request, $roleAssignments);
	}

	/**
	 * @copydoc GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		return array('canEdit' => $this->canEdit());
	}

	/**
	 * get the current context
	 * @return $context Context
	 */
	function &getContext() {
		return $this->_context;
	}


	/**
	 * get whether or not this grid is editable (has actions).
	 * @return boolean $canEdit
	 */
	function canEdit() {
		return $this->_canEdit;
	}


	/**
	 * @copydoc CategoryGridHandler::loadCategoryData()
	 */
	function loadCategoryData($request, &$fileType, $filter = null) {

		// Elements to be displayed in the grid
		$libraryFileDao = DAORegistry::getDAO('LibraryFileDAO');
		$context = $this->getContext();
		$libraryFiles = $libraryFileDao->getByContextId($context->getId(), $fileType);

		return $libraryFiles->toAssociativeArray();
	}
}

?>
