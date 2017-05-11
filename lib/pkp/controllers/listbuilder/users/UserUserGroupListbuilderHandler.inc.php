<?php

/**
 * @file controllers/listbuilder/users/UserUserGroupListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserUserGroupListbuilderHandler
 * @ingroup controllers_listbuilder_users
 *
 * @brief Class assign/remove mappings of user user groups
 */

import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');

class UserUserGroupListbuilderHandler extends ListbuilderHandler {
	/** @var integer the user id for which to map user groups */
	var $_userId;

	/** @var Context */
	var $_context;


	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array('fetch', 'fetchRow', 'fetchOptions', 'save')
		);
	}


	//
	// Setters and Getters
	//
	/**
	 * Set the user id
	 * @param $userId integer
	 */
	function setUserId($userId) {
		$this->_userId = $userId;
	}


	/**
	 * Get the user id
	 * @return integer
	 */
	function getUserId() {
		return $this->_userId;
	}


	/**
	 * Set the context
	 * @param $context Context
	 */
	function setContext(&$context) {
		$this->_context =& $context;
	}


	/**
	 * Get the context
	 * @return Context
	 */
	function &getContext() {
		return $this->_context;
	}


	//
	// Overridden parent class functions
	//
	/**
	 * @copydoc GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		return array(
			'userId' => $this->getUserId()
		);
	}

	/**
	 * @copydoc ListbuilderHandler::getAddItemLinkAction()
	 */
	function getAddItemLinkAction($actionRequest) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_GRID);
		return new LinkAction(
			'addItem',
			$actionRequest,
			__('grid.user.addRole'),
			'add_item'
		);
	}


	/**
	 * @copydoc ListbuilderHandler::getOptions
	 * @param $includeDesignations boolean
	 */
	function getOptions($includeDesignations = false) {
		// Initialize the object to return
		$items = array(
			array(), // Names
			array() // Designations
		);

		// Fetch the user groups
		$context = $this->getContext();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups = $userGroupDao->getByContextId($context->getId());
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$roleNames = $roleDao->getRoleNames(true);

		// Assemble the array to return
		while ($userGroup = $userGroups->next()) {
			$userGroupId = $userGroup->getId();
			$roleId = $userGroup->getRoleId();
			$roleName = __($roleNames[$roleId]);

			$items[0][$roleId][$userGroupId] = $userGroup->getLocalizedName();
			if ($includeDesignations) {
				$items[1][$userGroupId] = $userGroup->getLocalizedAbbrev();
			}

			// Add the optgroup label.
			$items[0][LISTBUILDER_OPTGROUP_LABEL][$roleId] = $roleName;
		}

		return $items;
	}


	/**
	 * Initialize the grid with the currently selected set of user groups.
	 */
	protected function loadData() {
		$context = $this->getContext();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		return $userGroupDao->getByUserId($this->getUserId(), $context->getId());
	}


	//
	// Implement template methods from PKPHandler
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
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		// FIXME Validate user ID?

		// Load user-related translations.
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_USER);

		$this->setUserId((int) $request->getUserVar('userId'));

		$this->setContext($request->getContext());
		parent::initialize($request);

		// Basic configuration
		$this->setTitle($request->getUserVar('title'));
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_SELECT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('roles');

		import('lib.pkp.controllers.listbuilder.users.UserGroupListbuilderGridCellProvider');
		$cellProvider = new UserGroupListbuilderGridCellProvider();

		// Name column
		$nameColumn = new ListbuilderGridColumn(
			$this,
			'name',
			'common.name',
			null,
			null,
			$cellProvider,
			array('width' => 75, 'alignment' => COLUMN_ALIGNMENT_LEFT)
		);
		$this->addColumn($nameColumn);

		// Designation column
		$designationColumn = new ListbuilderGridColumn(
			$this,
			'designation',
			'common.designation',
			null,
			'controllers/listbuilder/listbuilderNonEditGridCell.tpl',
			$cellProvider,
			array('width' => 25, 'alignment' => COLUMN_ALIGNMENT_LEFT)
		);
		$this->addColumn($designationColumn);
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
		$userGroupId = $newRowId['name'];
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$context = $this->getContext();
		return $userGroupDao->getById($userGroupId, $context->getId());
	}
}

?>
