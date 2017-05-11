<?php

/**
 * @file controllers/grid/admin/systemInfo/SystemInfoGridCategoryRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SystemInfoGridCategoryRow
 * @ingroup controllers_grid_admin_systemInfo
 *
 * @brief System Info grid category row definition
 */

import('lib.pkp.classes.controllers.grid.GridCategoryRow');

class SystemInfoGridCategoryRow extends GridCategoryRow {

	var $_configSection;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Overridden methods from GridCategoryRow
	//
	/**
	 * @see GridCategoryRow::initialize()
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		// Do the default initialization
		parent::initialize($request);
		$this->_configSection = $this->getData();
	}

	/**
	 * Use a label if the actions in the grid are disabled.
	 * return string
	 */
	function getCategoryLabel() {
		return $this->_configSection;
	}
}
?>
