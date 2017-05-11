<?php

/**
 * @file controllers/grid/users/userSelect/UserSelectGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserSelectGridHandler
 * @ingroup controllers_grid_users_userSelect
 *
 * @brief Handle user selector grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.controllers.grid.users.userSelect.UserSelectGridCellProvider');

class UserSelectGridHandler extends GridHandler {
	/** @var array (user group ID => user group name) **/
	var $_userGroupOptions;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array('fetchGrid', 'fetchRows')
		);
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = (int)$request->getUserVar('stageId');

		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/*
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_EDITOR,
			LOCALE_COMPONENT_APP_EDITOR
		);

		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups = $userGroupDao->getUserGroupsByStage(
			$request->getContext()->getId(),
			$stageId
		);
		$this->_userGroupOptions = array();
		while ($userGroup = $userGroups->next()) {
			// Exclude reviewers.
			if ($userGroup->getRoleId() == ROLE_ID_REVIEWER) continue;
			$this->_userGroupOptions[$userGroup->getId()] = $userGroup->getLocalizedName();
		}

		$this->setTitle('editor.submission.findAndSelectUser');

		// Columns
		$cellProvider = new UserSelectGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'select',
				'',
				null,
				'controllers/grid/users/userSelect/userSelectRadioButton.tpl',
				$cellProvider,
				array('width' => 5)
			)
		);
		$this->addColumn(
			new GridColumn(
				'name',
				'author.users.contributor.name',
				null,
				null,
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
					'width' => 30
				)
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.InfiniteScrollingFeature');
		import('lib.pkp.classes.controllers.grid.feature.CollapsibleGridFeature');
		return array(new InfiniteScrollingFeature('infiniteScrolling', $this->getItemsNumber()), new CollapsibleGridFeature());
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		list($filterUserGroupId, $name) = $this->getFilterValues($filter);
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());
		$userStageAssignmentDao = DAORegistry::getDAO('UserStageAssignmentDAO'); /* @var $userStageAssignmentDao UserStageAssignmentDAO */
		return $userStageAssignmentDao->filterUsersNotAssignedToStageInUserGroup($submission->getId(), $stageId, $filterUserGroupId, $name, $rangeInfo);
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request, $filterData = array()) {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		$allFilterData = array_merge(
			$filterData,
			array(
				'userGroupOptions' => $this->_userGroupOptions,
				'selectedUserGroupId' => reset(array_keys($this->_userGroupOptions)),
				'gridId' => $this->getId(),
				'submissionId' => $submission->getId(),
				'stageId' => $stageId,
			));
		return parent::renderFilter($request, $allFilterData);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 */
	function getFilterSelectionData($request) {
		$name = (string) $request->getUserVar('name');
		$filterUserGroupId = (int) $request->getUserVar('filterUserGroupId');
		return array(
			'name' => $name,
			'filterUserGroupId' => $filterUserGroupId,
		);
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 */
	protected function getFilterForm() {
		return 'controllers/grid/users/userSelect/searchUserFilter.tpl';
	}

	/**
	 * @copydoc GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		return array(
			'submissionId' => $submission->getId(),
			'stageId' => $stageId,
		);
	}

	/**
	 * Determine whether a filter form should be collapsible.
	 * @return boolean
	 */
	protected function isFilterFormCollapsible() {
		return false;
	}

	/**
	 * Define how many items this grid will start loading.
	 * @return int
	 */
	protected function getItemsNumber() {
		return 20;
	}

	/**
	 * Process filter values, assigning default ones if
	 * none was set.
	 * @return array
	 */
	protected function getFilterValues($filter) {
		if (isset($filter['filterUserGroupId']) && $filter['filterUserGroupId']) {
			$filterUserGroupId = $filter['filterUserGroupId'];
		} else {
			$filterUserGroupId = reset(array_keys($this->_userGroupOptions));
		}
		if (isset($filter['name']) && $filter['name']) {
			$name = $filter['name'];
		} else {
			$name = null;
		}
		return array($filterUserGroupId, $name);
	}

}

?>
