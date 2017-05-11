<?php

/**
 * @file classes/controllers/grid/feature/selectableItems/SelectableItemsFeature.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SelectableItemsFeature
 * @ingroup controllers_grid_feature_selectableItems
 *
 * @brief Implements grid widgets selectable items functionality.
 *
 */

import('lib.pkp.classes.controllers.grid.feature.GridFeature');

class SelectableItemsFeature extends GridFeature {


	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct('selectableItems');
	}


	//
	// Hooks implementation.
	//
	/**
	 * @see GridFeature::gridInitialize()
	 */
	function gridInitialize($args) {
		$grid = $args['grid'];

		// Add checkbox column to the grid.
		import('lib.pkp.classes.controllers.grid.feature.selectableItems.ItemSelectionGridColumn');
		$grid->addColumn(new ItemSelectionGridColumn($grid->getSelectName()));
	}

	/**
	 * @see GridFeature::getInitializedRowInstance()
	 */
	function getInitializedRowInstance($args) {
		$grid = $args['grid'];
		$row = $args['row'];

		if (is_a($grid, 'CategoryGridHandler')) {
			$categoryId = $grid->getCurrentCategoryId();
			$row->addFlag('selected', $grid->isDataElementInCategorySelected($categoryId, $row->getData()));
		} else {
			$row->addFlag('selected', $grid->isDataElementSelected($row->getData()));
		}
	}
}

?>
