<?php

/**
 * @file controllers/listbuilder/settings/categories/CategoriesListbuilderGridCellProvider.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoriesListbuilderGridCellProvider
 * @ingroup controllers_grid
 *
 * @brief Base class for a cell provider that can retrieve labels from arrays
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class CategoriesListbuilderGridCellProvider extends GridCellProvider {
	/**
	 * Constructor
	 */
	function CategoriesListbuilderGridCellProvider() {
		parent::GridCellProvider();
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * This implementation assumes a simple data element array that
	 * has column ids as keys.
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$category = $row->getData();
		$columnId = $column->getId();
		assert(!empty($columnId));

		return array('labelKey' => $category->getId(), 'label' => $category->getLocalizedName());
	}
}

?>
