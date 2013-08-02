<?php

/**
 * @file controllers/listbuilder/settings/categories/CategoriesListbuilderHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoriesListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for assigning categories to series.
 */

import('lib.pkp.controllers.listbuilder.settings.SetupListbuilderHandler');

class CategoriesListbuilderHandler extends SetupListbuilderHandler {
	/**
	 * Constructor
	 */
	function CategoriesListbuilderHandler() {
		parent::SetupListbuilderHandler();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array('fetch', 'fetchRow', 'fetchOptions')
		);
	}

	/**
	 * Load the list from an external source into the grid structure
	 */
	function loadData() {
		$journal = $this->getContext();
		$categoryIds = (array) $journal->getSetting('categories');
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$entryDao = $categoryDao->getEntryDAO();
		$categories = $entryDao->getByControlledVocabId($categoryDao->build()->getId());
		$categories = $categories->toAssociativeArray();
		return array_intersect_key($categories, $categoryIds);
	}

	/**
	 * Get possible items to populate autosuggest list with
	 */
	function getOptions() {
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		return array($categoryDao->getCategories());
	}

	/**
	 * @copydoc GridHandler::getRowDataElement()
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
		$categoryId = $newRowId['name'];
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$entryDao = $categoryDao->getEntryDAO();
		return $entryDao->getById($categoryId, $categoryDao->build()->getId());
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
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_MANAGER);

		// Basic configuration
		$this->setTitle('manager.setup.categories');
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_SELECT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('categories');

		// Name column
		$nameColumn = new ListbuilderGridColumn($this, 'name', 'common.name');

		import('controllers.listbuilder.settings.categories.CategoriesListbuilderGridCellProvider');
		$nameColumn->setCellProvider(new CategoriesListbuilderGridCellProvider());
		$this->addColumn($nameColumn);
	}
}

?>
