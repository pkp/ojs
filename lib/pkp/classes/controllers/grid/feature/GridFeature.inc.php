<?php

/**
 * @file classes/controllers/grid/feature/GridFeature.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GridFeature
 * @ingroup controllers_grid_feature
 *
 * @brief Base grid feature class. A feature is a type of plugin specific
 * to the grid widgets. It provides several hooks to allow injection of
 * additional grid functionality. This class implements template methods
 * to be extendeded by subclasses.
 *
 */

class GridFeature {

	/** @var string */
	var $_id;

	/** @var array */
	var $_options;

	/**
	 * Constructor.
	 * @param $id string Feature id.
	 */
	function __construct($id) {
		$this->setId($id);
	}


	//
	// Getters and setters.
	//
	/**
	 * Get feature id.
	 * @return string
	 */
	function getId() {
		return $this->_id;
	}

	/**
	 * Set feature id.
	 * @param $id string
	 */
	function setId($id) {
		$this->_id = $id;
	}

	/**
	 * Get feature js class options.
	 * @return string
	 */
	function getOptions() {
		return $this->_options;
	}

	/**
	 * Add feature js class options.
	 * @param $options array $optionId => $optionValue
	 */
	function addOptions($options) {
		assert(is_array($options));
		$this->_options = array_merge((array) $this->getOptions(), $options);
	}


	//
	// Protected methods to be used or extended by subclasses.
	//
	/**
	 * Set feature js class options. Extend this method to
	 * define more feature js class options.
	 * @param $request PKPRequest
	 * @param $grid GridHandler
	 */
	function setOptions($request, $grid) {
		$renderedElements = $this->fetchUIElements($request, $grid);
		if ($renderedElements) {
			foreach ($renderedElements as $id => $markup) {
				$this->addOptions(array($id => $markup));
			}
		}
	}

	/**
	 * Fetch any user interface elements that
	 * this feature needs to add its functionality
	 * into the grid. Use this only for ui elements
	 * that grid will not fetch itself.
	 * @param $request PKPRequest
	 * @param $grid GridHandler The grid that this
	 * feature is attached to.
	 * @return array It is expected that the array
	 * returns data in this format:
	 * $elementId => $elementMarkup
	 */
	function fetchUIElements($request, $grid) {
		return array();
	}

	/**
	 * Return the java script feature class.
	 * @return string|null
	 */
	function getJSClass() {
		return null;
	}


	//
	// Public hooks to be implemented in subclasses.
	//
	/**
	 * Hook called every time grid request args are
	 * required. Note that if the specific grid implementation
	 * extends the getRequestArgs method, this hook will only
	 * be called if the extending method call its parent.
	 * @param $args array
	 * 'grid' => GridHandler
	 * 'requestArgs' => array
	 */
	function getRequestArgs($args) {
		return null;
	}

	/**
	 * Hook called every time the grid range info is
	 * retrieved.
	 * @param $args array
	 * 'request' => PKPRequest
	 * 'grid' => GridHandler
	 * 'rangeInfo' => DBResultRange
	 */
	function getGridRangeInfo($args) {
		return null;
	}

	/**
	* Hook called when grid data is retrieved.
	* @param $args array
	* 'request' => PKPRequest
	* 'grid' => GridHandler
	* 'gridData' => mixed (array or ItemIterator)
	* 'filter' => array
	*/
	function getGridDataElements($args) {
		return null;
	}

	/**
	 * Hook called before grid data is setted.
	 * @param $args array
	 * 'grid' => GridHandler
	 * 'data' => mixed (array or ItemIterator)
	 */
	function setGridDataElements($args) {
		return null;
	}

	/**
	 * Hook called every time grid initialize a row object.
	 * @param $args array
	 * 'grid' => GridHandler,
	 * 'row' => GridRow
	 */
	function getInitializedRowInstance($args) {
		return null;
	}

	/**
	 * Hook called on grid category row initialization.
	 * @param $args array 'request' => PKPRequest
	 * 'grid' => CategoryGridHandler
	 * 'categoryId' => int
	 * 'row' => GridCategoryRow
	 */
	function getInitializedCategoryRowInstance($args) {
		return null;
	}

	/**
	 * Hook called on grid's initialization.
	 * @param $args array Contains the grid handler referenced object
	 * in 'grid' array index.
	 */
	function gridInitialize($args) {
		return null;
	}

	/**
	 * Hook called on grid's data loading.
	 * @param $args array
	 * 'request' => PKPRequest,
	 * 'grid' => GridHandler,
	 * 'gridData' => array
	 */
	function loadData($args) {
		return null;
	}

	/**
	 * Hook called on grid fetching.
	 * @param $args array 'grid' => GridHandler
	 */
	function fetchGrid($args) {
		$grid =& $args['grid'];
		$request =& $args['request'];

		$this->setOptions($request, $grid);
	}

	/**
	 * Hook called after a group of rows is fetched.
	 * @param $args array
	 * 'request' => PKPRequest
	 * 'grid' => GridHandler
	 * 'jsonMessage' => JSONMessage
	 */
	function fetchRows($args) {
		return null;
	}

	/**
	 * Hook called after a row is fetched.
	 * @param $args array
	 * 'request' => PKPRequest
	 * 'grid' => GridHandler
	 * 'row' => mixed GridRow or null
	 * 'jsonMessage' => JSONMessage
	 */
	function fetchRow($args) {
		return null;
	}

	/**
	 * Hook called when save grid items sequence
	 * is requested.
	 * @param $args array 'request' => PKPRequest,
	 * 'grid' => GridHandler
	 */
	function saveSequence($args) {
		return null;
	}
}

?>
