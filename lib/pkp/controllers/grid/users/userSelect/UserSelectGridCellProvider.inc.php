<?php

/**
 * @file controllers/grid/users/userSelect/UserSelectGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserSelectGridCellProvider
 * @ingroup controllers_grid_users_userSelect
 *
 * @brief Base class for a cell provider that retrieves data for selecting a user
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class UserSelectGridCellProvider extends DataObjectGridCellProvider {
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
		$element = $row->getData();
		assert(is_a($element, 'User'));
		switch ($column->getId()) {
			case 'select': // Displays the radio option
				return array('rowId' => $row->getId());

			case 'name': // User's name
				return array('label' => $element->getFullName());
		}
		assert(false);
	}
}

?>
