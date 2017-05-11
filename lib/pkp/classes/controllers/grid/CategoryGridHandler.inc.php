<?php

/**
 * @file classes/controllers/grid/CategoryGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoryGridHandler
 * @ingroup controllers_grid
 *
 * @brief Class defining basic operations for handling HTML grids with categories.
 */

// import grid classes
import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.grid.GridCategoryRow');

// empty category constant
define('GRID_CATEGORY_NONE', 'NONE');

class CategoryGridHandler extends GridHandler {

	/** @var string empty category row locale key */
	var $_emptyCategoryRowText = 'grid.noItems';

	/** @var array The category grid's data source. */
	var $_categoryData;

	/** @var string The category id that this grid is currently rendering. */
	var $_currentCategoryId = null;


	/**
	 * Constructor.
	 */
	function __construct($dataProvider = null) {
		parent::__construct($dataProvider);

		import('lib.pkp.classes.controllers.grid.NullGridCellProvider');
		$this->addColumn(new GridColumn('indent', null, null, null,
			new NullGridCellProvider(), array('indent' => true, 'width' => 2)));
	}


	//
	// Getters and setters.
	//
	/**
	 * Get the empty rows text for a category.
	 * @return string
	 */
	function getEmptyCategoryRowText() {
		return $this->_emptyCategoryRowText;
	}

	/**
	 * Set the empty rows text for a category.
	 * @param string $translationKey
	 */
	function setEmptyCategoryRowText($translationKey) {
		$this->_emptyCategoryRowText = $translationKey;
	}

	/**
	 * Get the category id that this grid is currently rendering.
	 * @param int
	 */
	function getCurrentCategoryId() {
		return $this->_currentCategoryId;
	}

	/**
	 * Override to return the data element sequence value
	 * inside the passed category, if needed.
	 * @param $categoryId int The data element category id.
	 * @param $gridDataElement mixed The element to return the
	 * sequence.
	 * @return int
	 */
	function getDataElementInCategorySequence($categoryId, &$gridDataElement) {
		assert(false);
	}

	/**
	 * Override to set the data element new sequence inside
	 * the passed category, if needed.
	 * @param $categoryId int The data element category id.
	 * @param $gridDataElement mixed The element to set the
	 * new sequence.
	 * @param $newSequence int The new sequence value.
	 */
	function setDataElementInCategorySequence($categoryId, &$gridDataElement, $newSequence) {
		assert(false);
	}

	/**
	 * Override to define whether the data element inside the passed
	 * category is selected or not.
	 * @param $categoryId int
	 * @param $gridDataElement mixed
	 */
	function isDataElementInCategorySelected($categoryId, &$gridDataElement) {
		assert(false);
	}

	/**
	 * Get the grid category data.
	 * @param $request PKPRequest
	 * @param $categoryElement mixed The category element.
	 * @return array
	 */
	function &getGridCategoryDataElements($request, $categoryElement) {
		$filter = $this->getFilterSelectionData($request);

		// Get the category element id.
		$categories = $this->getGridDataElements($request);
		$categoryElementId = array_search($categoryElement, $categories);
		assert($categoryElementId !== false);

		// Try to load data if it has not yet been loaded.
		if (!is_array($this->_categoryData) || !array_key_exists($categoryElementId, $this->_categoryData)) {
			$data = $this->loadCategoryData($request, $categoryElement, $filter);

			if (is_null($data)) {
				// Initialize data to an empty array.
				$data = array();
			}

			$this->setGridCategoryDataElements($request, $categoryElementId, $data);
		}

		return $this->_categoryData[$categoryElementId];
	}

	/**
	 * Check whether the passed category has grid rows.
	 * @param $categoryElement mixed The category data element
	 * that will be checked.
	 * @param $request PKPRequest
	 * @return boolean
	 */
	function hasGridDataElementsInCategory($categoryElement, $request) {
		$data =& $this->getGridCategoryDataElements($request, $categoryElement);
		assert (is_array($data));
		return (boolean) count($data);
	}

	/**
	 * Get the number of elements inside the passed category element.
	 * @param $categoryElement mixed
	 * @param $request PKPRequest
	 * @return int 
	 */
	function getCategoryItemsCount($categoryElement, $request) {
		$data = $this->getGridCategoryDataElements($request, $categoryElement);
		assert(is_array($data));
		return count($data);
	}

	/**
	 * Set the grid category data.
	 * @param $categoryElementId string The category element id.
	 * @param $data mixed an array or ItemIterator with category elements data.
	 */
	function setGridCategoryDataElements($request, $categoryElementId, $data) {
		// Make sure we have an array to store all categories elements data.
		if (!is_array($this->_categoryData)) {
			$this->_categoryData = array();
		}

		// FIXME: We go to arrays for all types of iterators because
		// iterators cannot be re-used, see #6498.
		if (is_array($data)) {
			$this->_categoryData[$categoryElementId] = $data;
		} elseif(is_a($data, 'DAOResultFactory')) {
			$this->_categoryData[$categoryElementId] = $data->toAssociativeArray();
		} elseif(is_a($data, 'ItemIterator')) {
			$this->_categoryData[$categoryElementId] = $data->toArray();
		} else {
			assert(false);
		}
	}


	//
	// Public handler methods
	//
	/**
	 * Render a category with all the rows inside of it.
	 * @param $args array
	 * @param $request Request
	 * @return string the serialized row JSON message or a flag
	 *  that indicates that the row has not been found.
	 */
	function fetchCategory(&$args, $request) {
		// Instantiate the requested row (includes a
		// validity check on the row id).
		$row = $this->getRequestedCategoryRow($request, $args);

		$json = new JSONMessage(true);
		if (is_null($row)) {
			// Inform the client that the category does no longer exist.
			$json->setAdditionalAttributes(array('elementNotFound' => (int)$args['rowId']));
		} else {
			// Render the requested category
			$this->setFirstDataColumn();
			$json->setContent($this->_renderCategoryInternally($request, $row));
		}
		return $json;
	}


	//
	// Extended methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		if (!is_null($request->getUserVar('rowCategoryId'))) {
			$this->_currentCategoryId = (string) $request->getUserVar('rowCategoryId');
		}
	}

	/**
	 * @see GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		$args = parent::getRequestArgs();

		// If grid is rendering grid rows inside category,
		// add current category id value so rows will also know
		// their parent category.
		if (!is_null($this->_currentCategoryId)) {
			if ($this->getCategoryRowIdParameterName()) {
				$args[$this->getCategoryRowIdParameterName()] = $this->_currentCategoryId;
			}
		}

		return $args;
	}


	/**
	 * @see GridHandler::getJSHandler()
	 */
	public function getJSHandler() {
		return '$.pkp.controllers.grid.CategoryGridHandler';
	}

	/**
	 * @see GridHandler::setUrls()
	 */
	function setUrls($request) {
		$router = $request->getRouter();
		$url = array('fetchCategoryUrl' => $router->url($request, null, null, 'fetchCategory', null, $this->getRequestArgs()));
		parent::setUrls($request, $url);
	}

	/**
	 * @see GridHandler::getRowsSequence()
	 */
	protected function getRowsSequence($request) {
		return array_keys($this->getGridCategoryDataElements($request, $this->getCurrentCategoryId()));
	}

	/**
	 * @see GridHandler::doSpecificFetchGridActions($args, $request)
	 */
	protected function doSpecificFetchGridActions($args, $request, &$templateMgr) {
		// Render the body elements (category groupings + rows inside a <tbody>)
		$gridBodyParts = $this->_renderCategoriesInternally($request);
		$templateMgr->assign('gridBodyParts', $gridBodyParts);
	}

	/**
	 * @copydoc GridHandler::getRowDataElement()
	 */
	protected function getRowDataElement($request, &$rowId) {
		$rowData = parent::getRowDataElement($request, $rowId);
		$rowCategoryId = $request->getUserVar('rowCategoryId');

		if (is_null($rowData) && !is_null($rowCategoryId)) {
			// Try to get row data inside category.
			$categoryRowData = parent::getRowDataElement($request, $rowCategoryId);
			if (!is_null($categoryRowData)) {
				$categoryElements = $this->getGridCategoryDataElements($request, $categoryRowData);

				assert(is_array($categoryElements));
				if (!isset($categoryElements[$rowId])) return null;

				// Let grid (and also rows) knowing the current category id.
				// This value will be published by the getRequestArgs method.
				$this->_currentCategoryId = $rowCategoryId;

				return $categoryElements[$rowId];
			}
		} else {
			return $rowData;
		}
	}

	/**
	 * @see GridHandler::setFirstDataColumn()
	 */
	protected function setFirstDataColumn() {
		$columns =& $this->getColumns();
		reset($columns);
		// Category grids will always have indent column firstly,
		// so we need to consider the first column the second one.
		$secondColumn = next($columns); /* @var $secondColumn GridColumn */
		$secondColumn->addFlag('firstColumn', true);
	}

	/**
	 * @see GridHandler::renderRowInternally()
	 */
	protected function renderRowInternally($request, $row) {
		if ($this->getCategoryRowIdParameterName()) {
			$param = $this->getRequestArg($this->getCategoryRowIdParameterName());
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign('categoryId', $param);
		}

		return parent::renderRowInternally($request, $row);
	}

	/**
	 * Tries to identify the data element in the grids
	 * data source that corresponds to the requested row id.
	 * Raises a fatal error if such an element cannot be
	 * found.
	 * @param $request PKPRequest
	 * @param $args array
	 * @return GridRow the requested grid row, already
	 *  configured with id and data or null if the row
	 *  could not been found.
	 */
	protected function getRequestedCategoryRow($request, $args) {
		if (isset($args['rowId'])) {
			// A row ID was specified. Fetch it
			$elementId = $args['rowId'];

			// Retrieve row data for the requested row id
			// (we can use the default getRowData element, works for category grids as well).
			$dataElement = $this->getRowDataElement($request, $elementId);
			if (is_null($dataElement)) {
				// If the row doesn't exist then
				// return null. It may be that the
				// row has been deleted in the meantime
				// and the client does not yet know about this.
				$nullVar = null;
				return $nullVar;
			}
		}

		// Instantiate a new row
		return $this->_getInitializedCategoryRowInstance($request, $elementId, $dataElement);
	}


	//
	// Protected methods to be overridden/used by subclasses
	//
	/**
	 * Get a new instance of a category grid row. May be
	 * overridden by subclasses if they want to
	 * provide a custom row definition.
	 * @return CategoryGridRow
	 */
	protected function getCategoryRowInstance() {
		//provide a sensible default category row definition
		return new GridCategoryRow();
	}

	/**
	 * Get the category row id parameter name.
	 * @return string
	 */
	protected function getCategoryRowIdParameterName() {
		// Must be implemented by subclasses.
		return null;
	}

	/**
	 * Implement this method to load category data into the grid.
	 * @param $request PKPRequest
	 * @param $categoryDataElement mixed
	 * @param $filter mixed
	 * @return array
	 */
	protected function loadCategoryData($request, &$categoryDataElement, $filter = null) {
		$gridData = array();
		$dataProvider = $this->getDataProvider();
		if (is_a($dataProvider, 'CategoryGridDataProvider')) {
			// Populate the grid with data from the
			// data provider.
			$gridData = $dataProvider->loadCategoryData($request, $categoryDataElement, $filter);
		}
		return $gridData;
	}


	//
	// Private helper methods
	//
	/**
	 * Instantiate a new row.
	 * @param $request Request
	 * @param $elementId string
	 * @param $element mixed
	 * @param $isModified boolean optional
	 * @return GridRow
	 */
	private function _getInitializedCategoryRowInstance($request, $elementId, $element) {
		// Instantiate a new row
		$row = $this->getCategoryRowInstance();
		$row->setGridId($this->getId());
		$row->setId($elementId);
		$row->setData($element);
		$row->setRequestArgs($this->getRequestArgs());

		// Initialize the row before we render it
		$row->initialize($request);
		$this->callFeaturesHook('getInitializedCategoryRowInstance',
			array('request' => $request,
				'grid' => $this,
				'categoryId' => $this->_currentCategoryId,
				'row' => $row));
		return $row;
	}

	/**
	 * Render all the categories internally
	 * @param $request PKPRequest
	 */
	private function _renderCategoriesInternally($request) {
		// Iterate through the rows and render them according
		// to the row definition.
		$renderedCategories = array();

		$elements = $this->getGridDataElements($request);
		foreach($elements as $key => $element) {

			// Instantiate a new row
			$categoryRow = $this->_getInitializedCategoryRowInstance($request, $key, $element);

			// Render the row
			$renderedCategories[] = $this->_renderCategoryInternally($request, $categoryRow);
		}

		return $renderedCategories;
	}

	/**
	 * Render a category row and its data.
	 * @param $request PKPRequest
	 * @param $categoryRow GridCategoryRow
	 * @return String HTML for all the rows (including category)
	 */
	private function _renderCategoryInternally($request, $categoryRow) {
		// Prepare the template to render the category.
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('grid', $this);
		$columns = $this->getColumns();
		$templateMgr->assign('columns', $columns);

		$categoryDataElement = $categoryRow->getData();
		$rowData = $this->getGridCategoryDataElements($request, $categoryDataElement);

		// Render the data rows
		$templateMgr->assign('categoryRow', $categoryRow);

		// Let grid (and also rows) knowing the current category id.
		// This value will be published by the getRequestArgs method.
		$this->_currentCategoryId = $categoryRow->getId();

		$renderedRows = $this->renderRowsInternally($request, $rowData);
		$templateMgr->assign('rows', $renderedRows);

		$renderedCategoryRow = $this->renderRowInternally($request, $categoryRow);

		// Finished working with this category, erase the current id value.
		$this->_currentCategoryId = null;

		$templateMgr->assign('renderedCategoryRow', $renderedCategoryRow);
		return $templateMgr->fetch('controllers/grid/gridBodyPartWithCategory.tpl');
	}
}

?>
