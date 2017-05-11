<?php

/**
 * @file classes/controllers/grid/DataObjectGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataObjectGridCellProvider
 * @ingroup controllers_grid
 *
 * @brief Base class for a cell provider that can retrieve simple labels
 *  from DataObjects. If you need more complex cell content then you may
 *  be better off using a ColumnBasedGridCellProvider.
 *
 * @see ColumnBasedGridCellProvider
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class DataObjectGridCellProvider extends GridCellProvider {
	/** @var string the locale to be retrieved. */
	var $_locale = null;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Setters and Getters
	//
	/**
	 * Set the locale
	 * @param $locale string
	 */
	function setLocale($locale) {
		$this->_locale = $locale;
	}

	/**
	 * Get the locale
	 * @return string
	 */
	function getLocale() {
		return $this->_locale;
	}


	//
	// Template methods from GridCellProvider
	//
	/**
	 * This implementation assumes an element that is a
	 * DataObject. It will retrieve an element in the
	 * configured locale.
	 * @see GridCellProvider::getTemplateVarsFromRowColumn()
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element = $row->getData();
		$columnId = $column->getId();
		assert(is_a($element, 'DataObject') && !empty($columnId));

		$data = $element->getData($columnId);
		// For localized fields, $data will be an array; otherwise,
		// it will be a value suitable for conversion to string.
		// If it's localized, fetch the value in the current locale.
		if (is_array($data)) $data=$data[$this->getLocale()];

		return array('label' => $data);
	}
}

?>
