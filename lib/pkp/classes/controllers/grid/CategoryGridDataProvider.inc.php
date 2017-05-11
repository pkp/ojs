<?php

/**
 * @file classes/controllers/grid/CategoryGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoryGridDataProvider
 * @ingroup classes_controllers_grid
 *
 * @brief Provide access to category grid data. Can optionally use a grid data
 * provider object that already provides access to data that the grid needs.
 */

// Import base class.
import('lib.pkp.classes.controllers.grid.GridDataProvider');

class CategoryGridDataProvider extends GridDataProvider {

	/* @var GridDataProvider A grid data provider that can be
	 * used by this category grid data provider to provide access
	 * to common data.
	 */
	var $_dataProvider;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Getters and setters.
	//
	/**
	 * Get a grid data provider object.
	 * @return GridDataProvider
	 */
	function getDataProvider() {
		return $this->_dataProvider;
	}

	/**
	 * Set a grid data provider object.
	 * @param $dataProvider GridDataProvider
	 */
	function setDataProvider($dataProvider) {
		if (is_a($dataProvider, 'CategoryGridDataProvider')) {
			assert(false);
			$dataProvider = null;
		}

		$this->_dataProvider = $dataProvider;
	}


	//
	// Overriden methods from GridDataProvider
	//
	/**
	 * @see GridDataProvider::setAuthorizedContext()
	 */
	function setAuthorizedContext(&$authorizedContext) {
		// We need to pass the authorized context object to
		// the grid data provider object, if any.
		$dataProvider = $this->getDataProvider();
		if ($dataProvider) {
			$dataProvider->setAuthorizedContext($authorizedContext);
		}

		parent::setAuthorizedContext($authorizedContext);
	}


	//
	// Template methods to be implemented by subclasses
	//
	/**
	 * Retrieve the category data to load into the grid.
	 * @param $request PKPRequest
	 * @param $categoryDataElement mixed
	 * @param $filter mixed array or null
	 * @return array
	 */
	function loadCategoryData($request, $categoryDataElement, $filter = null) {
		assert(false);
	}
}

?>
