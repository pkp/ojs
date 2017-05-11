<?php

/**
 * @file controllers/listbuilder/users/UserListbuilderGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserListbuilderGridCellProvider
 * @ingroup controllers_grid
 *
 * @brief Base class for a cell provider that can retrieve labels from arrays
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class UserListbuilderGridCellProvider extends GridCellProvider {
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
	 * This implementation assumes a simple data element array that
	 * has column ids as keys.
	 * @see GridCellProvider::getTemplateVarsFromRowColumn()
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$user =& $row->getData();
		$columnId = $column->getId();
		// Allow for either Users or Authors (both have a getFullName method).
		assert((is_a($user, 'User') || is_a($user, 'Author')) && !empty($columnId));

		return array('labelKey' => $user->getId(), 'label' => $user->getFullName() . ' <' . $user->getEmail() . '>');
	}
}

?>
