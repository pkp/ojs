<?php

/**
 * @file controllers/listbuilder/settings/SubEditorsListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubEditorsListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for adding a section/series editor
 */

import('lib.pkp.controllers.listbuilder.settings.SetupListbuilderHandler');

class SubEditorsListbuilderHandler extends SetupListbuilderHandler {
	/** @var The section/series ID for this listbuilder */
	var $_sectionId;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array('fetch', 'fetchRow', 'fetchOptions')
		);
	}

	/**
	 * Set the section/series ID
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		$this->_sectionId = $sectionId;
	}

	/**
	 * Get the section/series ID
	 * @return int
	 */
	function getSectionId() {
		return $this->_sectionId;
	}

	/**
	 * Load the list from an external source into the grid structure
	 * @param $request PKPRequest
	 * @return array List of sub-editors by section ID
	 */
	protected function loadData($request) {
		$context = $this->getContext();
		$sectionId = $this->getSectionId();

		$subEditorsDao = DAORegistry::getDAO('SubEditorsDAO');
		return $subEditorsDao->getBySectionId($sectionId, $context->getId());
	}

	/**
	 * Get possible items to populate pulldown with
	 * @return array Listbuilder-formatted array of pulldown options
	 */
	function getOptions() {
		$context = $this->getContext();
		$subEditorsDao = DAORegistry::getDAO('SubEditorsDAO');

		if ($this->getSectionId()) {
			$unassignedSubEditors = $subEditorsDao->getEditorsNotInSection($context->getId(), $this->getSectionId());
		} else {
			$roleDao = DAORegistry::getDAO('RoleDAO');
			$editors = $roleDao->getUsersByRoleId(ROLE_ID_SUB_EDITOR, $context->getId());
			$unassignedSubEditors = $editors->toArray();
		}
		$itemList = array(0 => array());
		foreach ($unassignedSubEditors as $subEditor) {
			$itemList[0][$subEditor->getId()] = $subEditor->getFullName();
		}

		return $itemList;
	}

	/**
	 * @copydoc GridHandler::getRowDataElement
	 */
	protected function getRowDataElement($request, &$rowId) {
		// fallback on the parent if a rowId is found
		if ( !empty($rowId) ) {
			return parent::getRowDataElement($request, $rowId);
		}

		// Otherwise return from the $newRowId
		$newRowId = $this->getNewRowId($request);
		$subEditorId = $newRowId['name'];
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getById($subEditorId);
	}

	/**
	 * Preserve the section/series ID for internal listbuilder requests.
	 * @see GridHandler::getRequestArgs
	 */
	function getRequestArgs() {
		$args = parent::getRequestArgs();
		$args['sectionId'] = $this->getSectionId();
		return $args;
	}


	//
	// Overridden template methods
	//
	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize($request) {
		parent::initialize($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);

		// Basic configuration
		$this->setTitle('user.role.subEditors');
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_SELECT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('subEditors');

		$this->setSectionId($request->getUserVar('sectionId'));

		// Name column
		$nameColumn = new ListbuilderGridColumn($this, 'name', 'common.name');

		// We can reuse the User cell provider because getFullName
		import('lib.pkp.controllers.listbuilder.users.UserListbuilderGridCellProvider');
		$nameColumn->setCellProvider(new UserListbuilderGridCellProvider());
		$this->addColumn($nameColumn);
	}
}

?>
