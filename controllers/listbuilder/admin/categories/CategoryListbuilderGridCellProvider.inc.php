<?php

/**
 * @file controllers/listbuilder/admin/categories/CategoryListbuilderGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoryListbuilderGridCellProvider
 * @ingroup controllers_listbuilder_admin_categories
 *
 * @brief Provide labels for categories listbuilder.
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class CategoryListbuilderGridCellProvider extends GridCellProvider {
	/**
	 * Constructor
	 */
	function CategoryListbuilderGridCellProvider() {
		parent::GridCellProvider();
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$category = $row->getData(); /* @var $category Category */
		$columnId = $column->getId();
		assert(is_a($category, 'ControlledVocabEntry'));
		switch ($columnId) {
			case 'name':
				return array('labelKey' => $category->getId(), 'label' => $category->getName(null));
		}
	}
}

?>
