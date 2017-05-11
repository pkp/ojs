<?php

/**
 * @file controllers/grid/settings/roles/UserGroupGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserGroupGridCellProvider
 * @ingroup controllers_grid_settings_roles
 *
 * @brief Cell provider for columns in a user group grid.
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class UserGroupGridCellProvider extends GridCellProvider {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$userGroup = $row->getData(); /* @var $userGroup UserGroup */
		$columnId = $column->getId();
		$workflowStages = Application::getApplicationStages();
		$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$assignedStages = $userGroupDao->getAssignedStagesByUserGroupId($userGroup->getContextId(), $userGroup->getId());

		switch ($columnId) {
			case 'name':
				return array('label' => $userGroup->getLocalizedName());
			case 'abbrev':
				return array('label' => $userGroup->getLocalizedAbbrev());
			case in_array($columnId, $workflowStages):
				// Set the state of the select element that will
				// be used to assign the stage to the user group.
				$selectDisabled = false;
				if (in_array($columnId, $roleDao->getForbiddenStages($userGroup->getRoleId()))) {
					// This stage should not be assigned to the user group.
					$selectDisabled = true;
				}

				return array('selected' => in_array($columnId, array_keys($assignedStages)),
					'disabled' => $selectDisabled);
			default:
				break;
		}

		return parent::getTemplateVarsFromRowColumn($row, $column);
	}

	/**
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		$workflowStages = Application::getApplicationStages();
		$columnId = $column->getId();

		if (in_array($columnId, $workflowStages)) {
			$userGroup = $row->getData(); /* @var $userGroup UserGroup */
			$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
			$assignedStages = $userGroupDao->getAssignedStagesByUserGroupId($userGroup->getContextId(), $userGroup->getId());

			$router = $request->getRouter();
			$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */

			if (!in_array($columnId, $roleDao->getForbiddenStages($userGroup->getRoleId()))) {
				if (in_array($columnId, array_keys($assignedStages))) {
					$operation = 'unassignStage';
					$actionTitleKey = 'grid.userGroup.unassignStage';
				} else {
					$operation = 'assignStage';
					$actionTitleKey = 'grid.userGroup.assignStage';
				}
				$actionArgs = array_merge(array('stageId' => $columnId),
					$row->getRequestArgs());

				$actionUrl = $router->url($request, null, null, $operation, null, $actionArgs);
				import('lib.pkp.classes.linkAction.request.AjaxAction');
				$actionRequest = new AjaxAction($actionUrl);

				$linkAction = new LinkAction(
					$operation,
					$actionRequest,
					__($actionTitleKey),
					null
				);

				return array($linkAction);
			}
		}

		return parent::getCellActions($request, $row, $column, $position);
	}
}

?>
