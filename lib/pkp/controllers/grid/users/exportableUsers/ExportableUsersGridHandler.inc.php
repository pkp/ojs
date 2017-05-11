<?php

/**
 * @file controllers/grid/users/exportableUsers/ExportableUsersGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExportableUsersGridHandler
 * @ingroup controllers_grid_users_exportableUsers
 *
 * @brief Handle exportable user grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');
import('lib.pkp.classes.linkAction.request.RedirectConfirmationModal');

class ExportableUsersGridHandler extends GridHandler {

	var $_pluginName;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(
			ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchRow')
		);
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request);

		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_APP_MANAGER
		);

		// Basic grid configuration.
		$this->setTitle('grid.user.currentUsers');

		// Grid actions.
		$router = $request->getRouter();
		$pluginName = $request->getUserVar('pluginName');
		assert(!empty($pluginName));
		$this->_pluginName = $pluginName;

		$dispatcher = $request->getDispatcher();
		$url = $dispatcher->url($request, ROUTE_PAGE, null, 'management', 'importexport', array('plugin', $pluginName, 'exportAllUsers'));

		$this->addAction(
			new LinkAction(
				'exportAllUsers',
				new RedirectConfirmationModal(
					__('grid.users.confirmExportAllUsers'),
					null,
					$url
				),
				__('grid.action.exportAllUsers'),
				'export_users'
			)
		);

		//
		// Grid columns.
		//

		// First Name.
		$cellProvider = new DataObjectGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'firstName',
				'user.firstName',
				null,
				null,
				$cellProvider
			)
		);

		// Last Name.
		$cellProvider = new DataObjectGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'lastName',
				'user.lastName',
				null,
				null,
				$cellProvider
			)
		);

		// User name.
		$cellProvider = new DataObjectGridCellProvider();
		$this->addColumn(
				new GridColumn(
					'username',
					'user.username',
					null,
					null,
					$cellProvider
				)
		);

		// Email.
		$cellProvider = new DataObjectGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'email',
				'user.email',
				null,
				null,
				$cellProvider
			)
		);
	}


	//
	// Implement methods from GridHandler.
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.selectableItems.SelectableItemsFeature');
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new SelectableItemsFeature(), new PagingFeature());
	}

	/**
	 * @copydoc GridHandler::getSelectName()
	 */
	function getSelectName() {
		return 'selectedUsers';
	}

	//
	// Implemented methods from GridHandler.
	//
	/**
	 * @copydoc GridHandler::isDataElementSelected()
	 */
	function isDataElementSelected($gridDataElement) {
		return false; // Nothing is selected by default
	}

	/**
	 * @copydoc GridHandler::loadData()
	 * @param $request PKPRequest
	 * @return array Grid data.
	 */
	protected function loadData($request, $filter) {
		// Get the context.
		$context = $request->getContext();

		// Get all users for this context that match search criteria.
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());

		return $users = $userGroupDao->getUsersById(
			$filter['userGroup'],
			$context->getId(),
			$filter['searchField'],
			$filter['search']?$filter['search']:null,
			$filter['searchMatch'],
			$rangeInfo
		);
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request) {
		$context = $request->getContext();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups = $userGroupDao->getByContextId($context->getId());
		$userGroupOptions = array('' => __('grid.user.allRoles'));
		while ($userGroup = $userGroups->next()) {
			$userGroupOptions[$userGroup->getId()] = $userGroup->getLocalizedName();
		}

		// Import PKPUserDAO to define the USER_FIELD_* constants.
		import('lib.pkp.classes.user.PKPUserDAO');
		$fieldOptions = array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		);

		$matchOptions = array(
			'contains' => 'form.contains',
			'is' => 'form.is'
		);

		$filterData = array(
			'userGroupOptions' => $userGroupOptions,
			'fieldOptions' => $fieldOptions,
			'matchOptions' => $matchOptions
		);

		return parent::renderFilter($request, $filterData);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 * @return array Filter selection data.
	 */
	function getFilterSelectionData($request) {
		// Get the search terms.
		$userGroup = $request->getUserVar('userGroup') ? (int)$request->getUserVar('userGroup') : null;
		$searchField = $request->getUserVar('searchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');

		return $filterSelectionData = array(
			'userGroup' => $userGroup,
			'searchField' => $searchField,
			'searchMatch' => $searchMatch,
			'search' => $search ? $search : ''
		);
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 * @return string Filter template.
	 */
	protected function getFilterForm() {
		return 'controllers/grid/users/exportableUsers/userGridFilter.tpl';
	}

	/**
	 * @see GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		return array_merge(parent::getRequestArgs(), array('pluginName' => $this->_getPluginName()));
	}

	/**
	 * Fetch the name of the plugin for this grid's calling context.
	 * @return string
	 */
	function _getPluginName() {
		return $this->_pluginName;
	}
}

?>
