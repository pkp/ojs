<?php

/**
 * @file classes/controllers/grid/GridBodyElement.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GridBodyElement
 * @ingroup controllers_grid
 *
 * @brief Base class for grid body elements.
 */

class GridBodyElement {
	/**
	 * @var string identifier of the element instance - must be unique
	 *  among all instances within a grid.
	 */
	var $_id;

	/**
	 * @var array flags that can be set by the handler to trigger layout
	 *  options in the element or in cells inside of it.
	 */
	var $_flags;

	/** @var GridCellProvider a cell provider for cells inside this element */
	var $_cellProvider;

	/**
	 * Constructor
	 */
	function __construct($id = '', $cellProvider = null, $flags = array()) {
		$this->_id = $id;
		$this->_cellProvider = $cellProvider;
		$this->_flags = $flags;
	}

	//
	// Setters/Getters
	//
	/**
	 * Get the element id
	 * @return string
	 */
	function getId() {
		return $this->_id;
	}

	/**
	 * Set the element id
	 * @param $id string
	 */
	function setId($id) {
		$this->_id = $id;
	}

	/**
	 * Get all layout flags
	 * @return array
	 */
	function getFlags() {
		return $this->_flags;
	}

	/**
	 * Get a single layout flag
	 * @param $flag string
	 * @return mixed
	 */
	function getFlag($flag) {
		assert(isset($this->_flags[$flag]));
		return $this->_flags[$flag];
	}

	/**
	 * Check whether a layout flag is set to true.
	 * @param $flag string
	 * @return boolean
	 */
	function hasFlag($flag) {
		if (!isset($this->_flags[$flag])) return false;
		return (boolean)$this->_flags[$flag];
	}

	/**
	 * Add a layout flag
	 * @param $flag string
	 * @param $value mixed optional
	 */
	function addFlag($flag, $value = true) {
		$this->_flags[$flag] = $value;
	}

	/**
	 * Get the cell provider
	 * @return GridCellProvider
	 */
	function &getCellProvider() {
		return $this->_cellProvider;
	}

	/**
	 * Set the cell provider
	 * @param $cellProvider GridCellProvider
	 */
	function setCellProvider(&$cellProvider) {
		$this->_cellProvider =& $cellProvider;
	}
}

?>
