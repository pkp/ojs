<?php

/**
 * @file controllers/grid/settings/roles/UserGroupGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserGroupGridHandler
 * @ingroup controllers_grid_settings
 *
 * @brief Handle operations for user group management operations.
 */

// Import the base GridHandler.
import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');
import('lib.pkp.classes.workflow.WorkflowStageDAO');

// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');

class UserGroupGridHandler extends GridHandler {

	/** @var integer Context id. */
	private $_contextId;

	/** @var UserGroup User group object handled by some grid operations. */
	private $_userGroup;


	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array(
				'fetchGrid',
				'fetchCategory',
				'fetchRow',
				'addUserGroup',
				'editUserGroup',
				'updateUserGroup',
				'removeUserGroup',
				'assignStage',
				'unassignStage'
			)
		);
	}

	//
	// Overridden methods from PKPHandler.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

		$operation = $request->getRequestedOp();
		$workflowStageRequiredOps = array('assignStage', 'unassignStage');
		if (in_array($operation, $workflowStageRequiredOps)) {
			import('lib.pkp.classes.security.authorization.internal.WorkflowStageRequiredPolicy');
			$this->addPolicy(new WorkflowStageRequiredPolicy($request->getUserVar('stageId')));
		}

		$userGroupRequiredOps = array_merge($workflowStageRequiredOps, array('editUserGroup', 'updateUserGroup', 'removeUserGroup'));
		if (in_array($operation, $userGroupRequiredOps)) {
			// Validate the user group object.
			$userGroupId = $request->getUserVar('userGroupId');
			$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
			$userGroup = $userGroupDao->getById($userGroupId);

			if (!$userGroup) {
				fatalError('Invalid user group id!');
			} else {
				$this->_userGroup = $userGroup;
			}
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		$context = $request->getContext();
		$this->_contextId = $context->getId();

		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_PKP_SUBMISSION
		);

		// Basic grid configuration.
		$this->setTitle('grid.roles.currentRoles');

		// Add grid-level actions.
		$router = $request->getRouter();
		$this->addAction(
			new LinkAction(
				'addUserGroup',
				new AjaxModal(
					$router->url($request, null, null, 'addUserGroup'),
					__('grid.roles.add'),
					'modal_add_role'
				),
				__('grid.roles.add'),
				'add_role'
			)
		);

		import('lib.pkp.controllers.grid.settings.roles.UserGroupGridCellProvider');
		$cellProvider = new UserGroupGridCellProvider();

		$workflowStagesLocales = WorkflowStageDAO::getWorkflowStageTranslationKeys();

		// Set array containing the columns info with the same cell provider.
		$columnsInfo = array(
			1 => array('id' => 'name', 'title' => 'settings.roles.roleName', 'template' => null),
			2 => array('id' => 'abbrev', 'title' => 'settings.roles.roleAbbrev', 'template' => null)
		);

		foreach ($workflowStagesLocales as $stageId => $stageTitleKey) {
			$columnsInfo[] = array('id' => $stageId, 'title' => $stageTitleKey, 'template' => 'controllers/grid/common/cell/selectStatusCell.tpl');
		}

		// Add array columns to the grid.
		foreach($columnsInfo as $columnInfo) {
			$this->addColumn(
				new GridColumn(
					$columnInfo['id'], $columnInfo['title'], null,
					$columnInfo['template'], $cellProvider
				)
			);
		}
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$contextId = $this->_getContextId();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */

		$roleIdFilter = null;
		$stageIdFilter = null;

		if (!is_array($filter)) {
			$filter = array();
		}

		if (isset($filter['selectedRoleId'])) {
			$roleIdFilter = $filter['selectedRoleId'];
		}

		if (isset($filter['selectedStageId'])) {
			$stageIdFilter = $filter['selectedStageId'];
		}

		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());

		if ($stageIdFilter && $stageIdFilter != 0) {
			return $userGroupDao->getUserGroupsByStage($contextId, $stageIdFilter, $roleIdFilter, $rangeInfo);
		} else if ($roleIdFilter && $roleIdFilter != 0) {
			return $userGroupDao->getByRoleId($contextId, $roleIdFilter, false, $rangeInfo);
		} else {
			return $userGroupDao->getByContextId($contextId, $rangeInfo);
		}
	}

	/**
	 * @copydoc GridHandler::getRowInstance()
	 * @return UserGroupGridRow
	 */
	protected function getRowInstance() {
		import('lib.pkp.controllers.grid.settings.roles.UserGroupGridRow');
		return new UserGroupGridRow();
	}

	/**
	* @see GridHandler::renderFilter()
	*/
	function renderFilter($request) {
		// Get filter data.
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$roleOptions = array(0 => 'grid.user.allPermissionLevels') + $roleDao->getRoleNames(true);

		// Reader roles are not important for stage assignments.
		if (array_key_exists(ROLE_ID_READER, $roleOptions)) {
			unset($roleOptions[ROLE_ID_READER]);
		}

		$filterData = array('roleOptions' => $roleOptions);

		$workflowStages = array(0 => 'grid.userGroup.allStages') + WorkflowStageDAO::getWorkflowStageTranslationKeys();
		$filterData['stageOptions'] = $workflowStages;

		return parent::renderFilter($request, $filterData);
	}

	/**
	 * @see GridHandler::getFilterSelectionData()
	 * @return array Filter selection data.
	 */
	function getFilterSelectionData($request) {
		$selectedRoleId = $request->getUserVar('selectedRoleId');
		$selectedStageId = $request->getUserVar('selectedStageId');

		// Cast or set to grid filter default value (all roles).
		$selectedRoleId = (is_null($selectedRoleId) ? 0 : (int)$selectedRoleId);
		$selectedStageId = (is_null($selectedStageId) ? 0 : (int)$selectedStageId);

		return array ('selectedRoleId' => $selectedRoleId, 'selectedStageId' => $selectedStageId);
	}

	/**
	 * @see GridHandler::getFilterForm()
	 * @return string Filter template.
	 */
	protected function getFilterForm() {
		return 'controllers/grid/settings/roles/userGroupsGridFilter.tpl';
	}

	/**
	 * @see GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new PagingFeature());
	}


	//
	// Handler operations.
	//
	/**
	 * Handle the add user group operation.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addUserGroup($args, $request) {
		return $this->editUserGroup($args, $request);
	}

	/**
	 * Handle the edit user group operation.
	 * @param $args array
	 * @return JSONMessage JSON object
	 */
	function editUserGroup($args, $request) {
		$userGroupForm = $this->_getUserGroupForm($request);

		$userGroupForm->initData();

		return new JSONMessage(true, $userGroupForm->fetch($request));
	}

	/**
	 * Update user group data on database and grid.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateUserGroup($args, $request) {
		$userGroupForm = $this->_getUserGroupForm($request);

		$userGroupForm->readInputData();
		if($userGroupForm->validate()) {
			$userGroupForm->execute($request);
			return DAO::getDataChangedEvent();
		} else {
			return new JSONMessage(true, $userGroupForm->fetch($request));
		}
	}

	/**
	 * Remove user group.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function removeUserGroup($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$user = $request->getUser();
		$userGroup = $this->_userGroup;
		$contextId = $this->_getContextId();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$notificationMgr = new NotificationManager();

		$usersAssignedToUserGroupCount = $userGroupDao->getContextUsersCount($contextId, $userGroup->getId());
		if ($usersAssignedToUserGroupCount == 0) {
			if ($userGroupDao->isDefault($userGroup->getId())) {
				// Can't delete default user groups.
				$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_WARNING,
					array('contents' => __('grid.userGroup.cantRemoveDefaultUserGroup',
						array('userGroupName' => $userGroup->getLocalizedName()	)
				)));
			} else {
				// We can delete, no user assigned yet.
				$userGroupDao->deleteObject($userGroup);
				$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS,
					array('contents' => __('grid.userGroup.removed',
						array('userGroupName' => $userGroup->getLocalizedName()	)
				)));
			}
		} else {
			// Can't delete while an user
			// is still assigned to that user group.
			$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_WARNING,
				array('contents' => __('grid.userGroup.cantRemoveUserGroup',
					array('userGroupName' => $userGroup->getLocalizedName()	, 'usersCount' => $usersAssignedToUserGroupCount)
			)));

		}

		return DAO::getDataChangedEvent($userGroup->getId());
	}

	/**
	 * Assign stage to user group.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function assignStage($args, $request) {
		return $this->_toggleAssignment($args, $request);
	}

	/**
	* Unassign stage to user group.
	* @param $args array
	* @param $request PKPRequest
	*/
	function unassignStage($args, $request) {
		return $this->_toggleAssignment($args, $request);
	}

	//
	// Private helper methods.
	//

	/**
	 * Toggle user group stage assignment.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	private function _toggleAssignment($args, $request) {
		$userGroup = $this->_userGroup;
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$contextId = $this->_getContextId();
		$operation = $request->getRequestedOp();

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */

		switch($operation) {
			case 'assignStage':
				$userGroupDao->assignGroupToStage($contextId, $userGroup->getId(), $stageId);
				$messageKey = 'grid.userGroup.assignedStage';
				break;
			case 'unassignStage':
				$userGroupDao->removeGroupFromStage($contextId, $userGroup->getId(), $stageId);
				$messageKey = 'grid.userGroup.unassignedStage';
				break;
		}

		$notificationMgr = new NotificationManager();
		$user = $request->getUser();

		$stageLocaleKeys = WorkflowStageDAO::getWorkflowStageTranslationKeys();

		$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS,
			array('contents' => __($messageKey,
				array('userGroupName' => $userGroup->getLocalizedName(), 'stageName' => __($stageLocaleKeys[$stageId]))
		)));

		return DAO::getDataChangedEvent($userGroup->getId());
	}

	/**
	 * Get a UserGroupForm instance.
	 * @param $request Request
	 * @return UserGroupForm
	 */
	private function _getUserGroupForm($request) {
		// Get the user group Id.
		$userGroupId = (int) $request->getUserVar('userGroupId');

		// Instantiate the files form.
		import('lib.pkp.controllers.grid.settings.roles.form.UserGroupForm');
		$contextId = $this->_getContextId();
		return new UserGroupForm($contextId, $userGroupId);
	}

	/**
	 * Get context id.
	 * @return int
	 */
	private function _getContextId() {
		return $this->_contextId;
	}
}

?>
