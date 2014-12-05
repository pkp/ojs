<?php

/**
 * @file controllers/grid/admin/categories/AdminCategoriesGridHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminCategoriesGridHandler
 * @ingroup controllers_grid_admin_categories
 *
 * @brief Handle category administration grid requests.
 */

import('lib.pkp.controllers.grid.settings.SetupGridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

import('controllers.grid.admin.categories.AdminCategoriesGridRow');

class AdminCategoriesGridHandler extends SetupGridHandler {

	/**
	 * Constructor
	 */
	function AdminCategoriesGridHandler() {
		parent::SetupGridHandler();
		$this->addRoleAssignment(array(ROLE_ID_MANAGER),
				array('fetchGrid', 'fetchRow', 'addItem', 'editItem', 'updateItem', 'deleteItem'));
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		return parent::authorize($request, $args, $roleAssignments, false);
	}

	//
	// Overridden template methods
	//
	/**
	 * @see SetupGridHandler::initialize()
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_ADMIN);

		// Basic grid configuration
		$this->setId('adminCategories');
		$this->setTitle('admin.categories');
		$this->setInstructions('admin.categories.description');


		// Add grid-level actions
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$router = $request->getRouter();
		$this->addAction(
			new LinkAction(
				'addItem',
				new AjaxModal(
					$router->url($request, null, null, 'addItem', null, array('gridId' => $this->getId())),
					__('grid.action.addItem'),
					'modal_add_item',
					true),
				__('grid.action.addItem'),
				'add_item')
		);

		// Columns
		$this->addColumn(
			new GridColumn(
				'name',
				'common.name'
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::getRowInstance()
	 */
	function getRowInstance() {
		return new AdminCategoriesGridRow();
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter) {
		// Elements to be displayed in the grid
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$categories = array();
		foreach ($categoryDao->getCategories() as $id => $name) {
			$categories[$id]['name'] = $name;
		}
		return $categories;
	}


	//
	// Public grid actions.
	//
	/**
	 * An action to add a new category
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addItem($args, $request) {
		// Calling editItem with an empty row id will add
		// a new category.
		return $this->editItem($args, $request);
	}

	/**
	 * An action to edit a category
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editItem($args, $request) {
		import('controllers.grid.admin.categories.form.AdminCategoryForm');
		$categoryId = isset($args['rowId']) ? $args['rowId'] : null;
		$categoryForm = new AdminCategoryForm($categoryId);

		$categoryForm->initData($args, $request);

		return new JSONMessage(true, $categoryForm->fetch($request));
	}

	/**
	 * Update a category
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateItem($args, $request) {
		// -> categoryId must be present and valid
		// -> htmlId must be present and valid

		import('controllers.grid.admin.categories.form.AdminCategoryForm');
		$categoryId = isset($args['rowId']) ? $args['rowId'] : null;
		$categoryForm = new AdminCategoryForm($categoryId);
		$categoryForm->readInputData();

		if ($categoryForm->validate()) {
			$categoryForm->execute($args, $request);
			return DAO::getDataChangedEvent($categoryForm->categoryId);
		}
		return new JSONMessage(false);
	}

	/**
	 * Delete a category
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function deleteItem($args, $request) {
		$rowId = $request->getUserVar('rowId');

		// Get all categories
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$entryDao = $categoryDao->getEntryDAO();
		foreach ($categoryDao->getCategories() as $id => $name) {
			if ($id == $rowId) {
				$entryDao->deleteObjectById($id);
				break;
			}
		}

		$categoryDao->rebuildCache();

		return DAO::getDataChangedEvent($rowId);
	}
}

?>
