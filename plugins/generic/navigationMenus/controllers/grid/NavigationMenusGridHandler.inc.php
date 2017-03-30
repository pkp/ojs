<?php

/**
 * @file controllers/grid/NavigationMenusGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NavigationMenusGridHandler
 * @ingroup controllers_grid_navigationMenus
 *
 * @brief Handle NavigationMenus grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.navigationMenus.controllers.grid.NavigationMenusGridRow');
import('plugins.generic.navigationMenus.controllers.grid.NavigationMenusGridCellProvider');

class NavigationMenusGridHandler extends GridHandler {
	/** @var NavigationMenusPlugin The NavigationMenus plugin */
	static $plugin;

	/**
	 * Set the static pages plugin.
	 * @param $plugin NavigationMenusPlugin
	 */
	static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('index', 'fetchGrid', 'fetchRow', 'addNavigationMenu', 'editNavigationMenu', 'updateNavigationMenu', 'delete')
		);
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc Gridhandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);
		$context = $request->getContext();

		// Set the grid details.
		$this->setTitle('plugins.generic.navigationMenus.navigationMenus');
		$this->setEmptyRowText('plugins.generic.navigationMenus.noneCreated');

		// Get the pages and add the data to the grid
		$navigationMenusDao = DAORegistry::getDAO('NavigationMenusDAO');
		$this->setGridDataElements($navigationMenusDao->getByContextId($context->getId()));

		// Add grid-level actions
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'addNavigationMenu',
				new AjaxModal(
					$router->url($request, null, null, 'addNavigationMenu'),
					__('plugins.generic.navigationMenus.addNavigationMenu'),
					'modal_add_item'
				),
				__('plugins.generic.navigationMenus.addNavigationMenu'),
				'add_item'
			)
		);

		// Columns
		$cellProvider = new NavigationMenusGridCellProvider();
		$this->addColumn(new GridColumn(
			'title',
			'plugins.generic.navigationMenus.pageTitle',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));
		$this->addColumn(new GridColumn(
			'path',
			'plugins.generic.navigationMenus.path',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
		return array(new OrderGridItemsFeature());
	}

	/**
	 * @copydoc Gridhandler::getRowInstance()
	 */
	function getRowInstance() {
		return new NavigationMenusGridRow();
	}

	//
	// Public Grid Actions
	//
	/**
	 * Display the grid's containing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$context = $request->getContext();
		import('lib.pkp.classes.form.Form');
		$form = new Form(self::$plugin->getTemplatePath() . 'navigationMenus.tpl');
		$json = new JSONMessage(true, $form->fetch($request));
		return $json->getString();
	}

	/**
	 * An action to add a new NavigationMenus
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest Request object
	 */
	function addNavigationMenu($args, $request) {
		// Calling editNavigationMenu with an empty ID will add
		// a new NavigationMenu.
		return $this->editNavigationMenu($args, $request);
	}

	/**
	 * An action to edit a NavigationMenu
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest Request object
	 * @return string Serialized JSON object
	 */
	function editNavigationMenu($args, $request) {
		$navigationMenuId = $request->getUserVar('navigationMenusId');
		$context = $request->getContext();
		$this->setupTemplate($request);

		// Create and present the edit form
		import('plugins.generic.navigationMenus.controllers.grid.form.NavigationMenusForm');
		$navigationMenusPlugin = self::$plugin;
		$navigationMenusForm = new NavigationMenusForm(self::$plugin, $context->getId(), $navigationMenusId);
		$navigationMenusForm->initData();
		$json = new JSONMessage(true, $navigationMenusForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Update a NavigationMenu
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateNavigationMenu($args, $request) {
		$navigationMenuId = $request->getUserVar('navigationMenusId');
		$context = $request->getContext();
		$this->setupTemplate($request);

		// Create and populate the form
		import('plugins.generic.navigationMenus.controllers.grid.form.NavigationMenusForm');
		$navigationMenusPlugin = self::$plugin;
		$navigationMenusForm = new NavigationMenusForm(self::$plugin, $context->getId(), $navigationMenusId);
		$navigationMenusForm->readInputData();

		// Check the results
		if ($navigationMenusForm->validate()) {
			// Save the results
			$navigationMenusForm->execute();
			return DAO::getDataChangedEvent();
		} else {
			// Present any errors
			$json = new JSONMessage(true, $navigationMenusForm->fetch($request));
			return $json->getString();
		}
	}

	/**
	 * Delete a NavigationMenu
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function delete($args, $request) {
		$navigationMenuId = $request->getUserVar('navigationMenusId');
		$context = $request->getContext();

		// Delete the static page
		$navigationMenusDao = DAORegistry::getDAO('NavigationMenusDAO');
		$navigationMenu = $navigationMenusDao->getById($navigationMenuId, $context->getId());
		$navigationMenusDao->deleteObject($navigationMenu);

		return DAO::getDataChangedEvent();
	}
}

?>
