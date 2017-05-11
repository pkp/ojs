<?php

/**
 * @file classes/controllers/grid/GridCategoryRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GridCategoryRow
 * @ingroup controllers_grid
 *
 * @brief Class defining basic operations for handling the category row in a grid
 *
 */
import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.controllers.grid.GridCategoryRowCellProvider');

class GridCategoryRow extends GridRow {
	/** @var string empty row locale key */
	var $_emptyCategoryRowText = 'grid.noItems';

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();

		// Set a default cell provider that will get the cell template
		// variables from the category grid row.
		$this->setCellProvider(new GridCategoryRowCellProvider());
	}


	//
	// Getters/Setters
	//
	/**
	 * Get the no items locale key
	 */
	function getEmptyCategoryRowText() {
		return $this->_emptyCategoryRowText;
	}

	/**
	 * Set the no items locale key
	 */
	function setEmptyCategoryRowText($emptyCategoryRowText) {
		$this->_emptyCategoryRowText = $emptyCategoryRowText;
	}

	/**
	 * Category rows only have one cell and one label.  This is it.
	 * @param string $categoryName
	 * return string
	 */
	function getCategoryLabel() {
		return '';
	}
}

?>
