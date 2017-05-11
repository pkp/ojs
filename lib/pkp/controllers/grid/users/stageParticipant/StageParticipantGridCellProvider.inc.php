<?php

/**
 * @file controllers/grid/users/stageParticipant/StageParticipantGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataObjectGridCellProvider
 * @ingroup controllers_grid_users_submissionContributor
 *
 * @brief Cell provider to retrieve the user's name from the stage assignment
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class StageParticipantGridCellProvider extends DataObjectGridCellProvider {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		switch ($column->getId()) {
			case 'participants':
				$stageAssignment = $row->getData();
				$userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
				$user = $userDao->getById($stageAssignment->getUserId());
				return array('label' => $user->getFullName());
			default:
				assert(false);
		}
	}
}

?>
