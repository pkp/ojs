<?php

/**
 * @file controllers/listbuilder/admin/categories/CategoryListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoryListbuilderHandler
 * @ingroup controllers_listbuilder_admin_categories
 *
 * @brief Listbuilder for managing journal categories.
 */

import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');

class CategoryListbuilderHandler extends ListbuilderHandler {

	/**
	 * Constructor
	 */
	function CategoryListbuilderHandler() {
		parent::ListbuilderHandler();
		$this->addRoleAssignment(
			ROLE_ID_SITE_ADMIN,
			array('fetch', 'fetchRow', 'save', 'fetchOptions')
		);
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc ListbuilderHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_ADMIN);

		// Basic configuration
		$this->setTitle('admin.categories');
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('categories');
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_TEXT);

		// Title column
		$titleColumn = new MultilingualListbuilderGridColumn($this, 'name', 'common.name', null, null, null, null, array('tabIndex' => 1));
		import('controllers.listbuilder.admin.categories.CategoryListbuilderGridCellProvider');
		$titleColumn->setCellProvider(new CategoryListbuilderGridCellProvider());
		$this->addColumn($titleColumn);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request) {
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		return $categoryDao->getIterator();
	}

	/**
	 * @copydoc GridHandler::getRowDataElement
	 */
	function getRowDataElement($request, &$rowId) {
		// fallback on the parent if a rowId is found
		if ( !empty($rowId) ) {
			return parent::getRowDataElement($request, $rowId);
		}

		// Otherwise return from the $newRowId
		$rowData = $this->getNewRowId($request);
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$category = $categoryDao->getEntryDao()->newDataObject();
		$category->setName($rowData['name'], null); // Localized
		return $category;
	}
}
?>
