<?php

/**
 * @file controllers/grid/settings/user/UserGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserGridHandler
 * @ingroup controllers_grid_settings_user
 *
 * @brief Handle user grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

import('lib.pkp.controllers.grid.settings.user.UserGridRow');
import('lib.pkp.controllers.grid.settings.user.form.UserDetailsForm');

class UserGridHandler extends GridHandler {
	/** integer user id for the user to remove */
	var $_oldUserId;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(
			ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchRow', 'editUser', 'updateUser', 'updateUserRoles',
				'editDisableUser', 'disableUser', 'removeUser', 'addUser',
				'editEmail', 'sendEmail', 'mergeUsers')
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
		parent::initialize($request, $args);

		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_APP_MANAGER
		);

		$this->_oldUserId  = (int) $request->getUserVar('oldUserId');
		// Basic grid configuration.
		$this->setTitle('grid.user.currentUsers');

		// Grid actions.
		$router = $request->getRouter();

		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'addUser',
				new AjaxModal(
					$router->url($request, null, null, 'addUser', null, null),
					__('grid.user.add'),
					'modal_add_user',
					true
					),
				__('grid.user.add'),
				'add_user')
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
	 * @copydoc GridHandler::getRowInstance()
	 * @return UserGridRow
	 */
	protected function getRowInstance() {
		return new UserGridRow($this->_oldUserId);
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new PagingFeature());
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

		return $userGroupDao->getUsersById(
			$filter['userGroup'],
			$filter['includeNoRole']?null:$context->getId(),
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
		$includeNoRole = $request->getUserVar('includeNoRole') ? (int) $request->getUserVar('includeNoRole') : null;
		$userGroup = $request->getUserVar('userGroup') ? (int)$request->getUserVar('userGroup') : null;
		$searchField = $request->getUserVar('searchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');

		return $filterSelectionData = array(
			'includeNoRole' => $includeNoRole,
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
		return 'controllers/grid/settings/user/userGridFilter.tpl';
	}


	//
	// Public grid actions.
	//
	/**
	 * Add a new user.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addUser($args, $request) {
		// Calling editUser with an empty row id will add a new user.
		return $this->editUser($args, $request);
	}

	/**
	 * Edit an existing user.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editUser($args, $request) {
		// Identify the user Id.
		$userId = $request->getUserVar('rowId');
		if (!$userId) $userId = $request->getUserVar('userId');

		$user = $request->getUser();
		if ($userId !== null && !Validation::canAdminister($userId, $user->getId())) {
			// We don't have administrative rights over this user.
			return new JSONMessage(false, __('grid.user.cannotAdminister'));
		} else {
			// Form handling.
			$userForm = new UserDetailsForm($request, $userId);
			$userForm->initData($args, $request);

			return new JSONMessage(true, $userForm->display($args, $request));
		}
	}

	/**
	 * Update an existing user.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateUser($args, $request) {
		$user = $request->getUser();

		// Identify the user Id.
		$userId = $request->getUserVar('userId');

		if ($userId !== null && !Validation::canAdminister($userId, $user->getId())) {
			// We don't have administrative rights over this user.
			return new JSONMessage(false, __('grid.user.cannotAdminister'));
		}

		// Form handling.
		$userForm = new UserDetailsForm($request, $userId);
		$userForm->readInputData();

		if ($userForm->validate()) {
			$user = $userForm->execute($args, $request);

			// If this is a newly created user, show role management form.
			if (!$userId) {
				import('lib.pkp.controllers.grid.settings.user.form.UserRoleForm');
				$userRoleForm = new UserRoleForm($user->getId(), $user->getFullName());
				$userRoleForm->initData($args, $request);
				return new JSONMessage(true, $userRoleForm->display($args, $request));
			} else {

				// Successful edit of an existing user.
				$notificationManager = new NotificationManager();
				$user = $request->getUser();
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.editedUser')));

				// Prepare the grid row data.
				return DAO::getDataChangedEvent($userId);
			}
		} else {
			return new JSONMessage(false);
		}
	}

	/**
	 * Update a newly created user's roles
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateUserRoles($args, $request) {
		$user = $request->getUser();

		// Identify the user Id.
		$userId = $request->getUserVar('userId');

		if ($userId !== null && !Validation::canAdminister($userId, $user->getId())) {
			// We don't have administrative rights over this user.
			return new JSONMessage(false, __('grid.user.cannotAdminister'));
		}

		// Form handling.
		import('lib.pkp.controllers.grid.settings.user.form.UserRoleForm');
		$userRoleForm = new UserRoleForm($userId, $user->getFullName());
		$userRoleForm->readInputData();

		if ($userRoleForm->validate()) {
			$userRoleForm->execute($args, $request);

			// Successfully managed newly created user's roles.
			return DAO::getDataChangedEvent($userId);
		} else {
			return new JSONMessage(false);
		}
	}

	/**
	 * Edit enable/disable user form
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editDisableUser($args, $request) {
		$user = $request->getUser();

		// Identify the user Id.
		$userId = $request->getUserVar('rowId');
		if (!$userId) $userId = $request->getUserVar('userId');

		// Are we enabling or disabling this user.
		$enable = isset($args['enable']) ? (bool) $args['enable'] : false;

		if ($userId !== null && !Validation::canAdminister($userId, $user->getId())) {
			// We don't have administrative rights over this user.
			return new JSONMessage(false, __('grid.user.cannotAdminister'));
		} else {
			// Form handling
			import('lib.pkp.controllers.grid.settings.user.form.UserDisableForm');
			$userForm = new UserDisableForm($userId, $enable);

			$userForm->initData($args, $request);

			return new JSONMessage(true, $userForm->display($args, $request));
		}
	}

	/**
	 * Enable/Disable an existing user
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function disableUser($args, $request) {
		$user = $request->getUser();

		// Identify the user Id.
		$userId = $request->getUserVar('userId');

		// Are we enabling or disabling this user.
		$enable = (bool) $request->getUserVar('enable');

		if ($userId !== null && !Validation::canAdminister($userId, $user->getId())) {
			// We don't have administrative rights over this user.
			return new JSONMessage(false, __('grid.user.cannotAdminister'));
		}

		// Form handling.
		import('lib.pkp.controllers.grid.settings.user.form.UserDisableForm');
		$userForm = new UserDisableForm($userId, $enable);

		$userForm->readInputData();

		if ($userForm->validate()) {
			$user = $userForm->execute($args, $request);

			// Successful enable/disable of an existing user.
			// Update grid data.
			return DAO::getDataChangedEvent($userId);

		} else {
			return new JSONMessage(false, $userForm->display($args, $request));
		}
	}

	/**
	 * Remove all user group assignments for a context for a given user.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function removeUser($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$context = $request->getContext();
		$user = $request->getUser();

		// Identify the user Id.
		$userId = $request->getUserVar('rowId');

		if ($userId !== null && !Validation::canAdminister($userId, $user->getId())) {
			// We don't have administrative rights over this user.
			return new JSONMessage(false, __('grid.user.cannotAdminister'));
		}

		// Remove user from all user group assignments for this context.
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		// Check if this user has any user group assignments for this context.
		if (!$userGroupDao->userInAnyGroup($userId, $context->getId())) {
			return new JSONMessage(false, __('grid.user.userNoRoles'));
		} else {
			$userGroupDao->deleteAssignmentsByContextId($context->getId(), $userId);
			return DAO::getDataChangedEvent($userId);
		}
	}

	/**
	 * Displays a modal to edit an email message to the user.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editEmail($args, $request) {
		$user = $request->getUser();

		// Identify the user Id.
		$userId = $request->getUserVar('rowId');

		if ($userId !== null && !Validation::canAdminister($userId, $user->getId())) {
			// We don't have administrative rights over this user.
			return new JSONMessage(false, __('grid.user.cannotAdminister'));
		} else {
			// Form handling.
			import('lib.pkp.controllers.grid.settings.user.form.UserEmailForm');
			$userEmailForm = new UserEmailForm($userId);
			$userEmailForm->initData();

			return new JSONMessage(true, $userEmailForm->fetch($args, $request));
		}
	}

	/**
	 * Send the user email and close the modal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function sendEmail($args, $request) {
		$user = $request->getUser();

		// Identify the user Id.
		$userId = $request->getUserVar('userId');

		if ($userId !== null && !Validation::canAdminister($userId, $user->getId())) {
			// We don't have administrative rights over this user.
			return new JSONMessage(false, __('grid.user.cannotAdminister'));
		}
		// Form handling.
		import('lib.pkp.controllers.grid.settings.user.form.UserEmailForm');
		$userEmailForm = new UserEmailForm($userId);
		$userEmailForm->readInputData();

		if ($userEmailForm->validate()) {
			$userEmailForm->execute($args, $request);
			return new JSONMessage(true);
		} else {
			return new JSONMessage(false, $userEmailForm->fetch($args, $request));
		}
	}

	/**
	 * Allow user account merging, including attributed submissions etc.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function mergeUsers($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		// if there is a $newUserId, this is the second time through, so merge the users.
		$newUserId =  (int) $request->getUserVar('newUserId');
		$oldUserId = (int) $request->getUserVar('oldUserId');
		$user = $request->getUser();
		if ($newUserId > 0 && $oldUserId > 0 && Validation::canAdminister($oldUserId, $user->getId())) {
			import('classes.user.UserAction');
			$userAction = new UserAction();
			$userAction->mergeUsers($oldUserId, $newUserId);
			return DAO::getDataChangedEvent();
		} else {
			// The grid shouldn't have presented an action in this
			// case.
			return new JSONMessage(false, __('grid.user.cannotAdminister'));
		}
	}

	/**
	 * @see GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		$requestArgs = (array) parent::getRequestArgs();
		$requestArgs['oldUserId'] = $this->_oldUserId;
		return $requestArgs;
	}
}

?>
