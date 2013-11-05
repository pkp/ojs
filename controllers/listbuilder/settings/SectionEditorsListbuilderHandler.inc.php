<?php

/**
 * @file controllers/listbuilder/settings/SectionEditorsListbuilderHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionEditorsListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for adding a section editor
 */

import('lib.pkp.controllers.listbuilder.settings.SetupListbuilderHandler');

class SectionEditorsListbuilderHandler extends SetupListbuilderHandler {
	/** @var The group ID for this listbuilder */
	var $sectionId;

	/**
	 * Constructor
	 */
	function SectionEditorsListbuilderHandler() {
		parent::SetupListbuilderHandler();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array('fetch', 'fetchRow', 'fetchOptions')
		);
	}

	/**
	 * Set the section ID
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		$this->sectionId = $sectionId;
	}

	/**
	 * Get the section ID
	 * @return int
	 */
	function getSectionId() {
		return $this->sectionId;
	}

	/**
	 * Load the list from an external source into the grid structure
	 * @param $request PKPRequest
	 */
	function loadData($request) {
		$press = $this->getContext();
		$sectionId = $this->getSectionId();

		$sectionEditorsDao = DAORegistry::getDAO('SectionEditorsDAO');
		$assignedSectionEditors = $sectionEditorsDao->getEditorsBySectionId($sectionId, $press->getId());
		$returner = array();
		foreach ($assignedSectionEditors as $sectionEditorData) {
			$sectionEditor = $sectionEditorData['user'];
			$returner[$sectionEditor->getId()] = $sectionEditor;
		}
		return $returner;
	}

	/**
	 * Get possible items to populate autosuggest list with
	 */
	function getOptions() {
		$press = $this->getContext();
		$sectionEditorsDao = DAORegistry::getDAO('SectionEditorsDAO');

		if ($this->getSectionId()) {
			$unassignedSectionEditors = $sectionEditorsDao->getEditorsNotInSection($press->getId(), $this->getSectionId());
		} else {
			$roleDao = DAORegistry::getDAO('RoleDAO');
			$editors = $roleDao->getUsersByRoleId(ROLE_ID_SUB_EDITOR, $press->getId());
			$unassignedSectionEditors = $editors->toArray();
		}
		$itemList = array(0 => array());
		foreach ($unassignedSectionEditors as $sectionEditor) {
			$itemList[0][$sectionEditor->getId()] = $sectionEditor->getFullName();
		}

		return $itemList;
	}

	/**
	 * @see GridHandler::getRowDataElement
	 * Get the data element that corresponds to the current request
	 * Allow for a blank $rowId for when creating a not-yet-persisted row
	 */
	function getRowDataElement($request, $rowId) {
		// fallback on the parent if a rowId is found
		if ( !empty($rowId) ) {
			return parent::getRowDataElement($request, $rowId);
		}

		// Otherwise return from the $newRowId
		$newRowId = $this->getNewRowId($request);
		$sectionEditorId = $newRowId['name'];
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getById($sectionEditorId);
	}

	/**
	 * Preserve the section ID for internal listbuilder requests.
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
		$this->setTitle('user.role.sectionEditors');
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_SELECT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('sectionEditors');

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
