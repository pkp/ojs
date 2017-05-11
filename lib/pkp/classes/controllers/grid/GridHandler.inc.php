<?php

/**
 * @file classes/controllers/grid/GridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GridHandler
 * @ingroup classes_controllers_grid
 *
 * @brief This class defines basic operations for handling HTML grids. Grids
 *  are used to implement a standardized listing of elements, as would commonly
 *  be laid out in an HTML table, permitting rows, columns, row actions (such
 *  as "delete" and "edit" actions, which operate on a single row), and grid
 *  actions (such as "new element", which operates on the grid as a whole), and
 *  other functionality to be implemented consistently.
 *
 * An implemented grid consists of several classes, with a subclass of
 * GridHandler as the centerpiece. Each row is described by an instance of a
 * GridRow, which is generally extended for the row in question; each column
 * is described by an instance of GridColumn (for which several generic columns
 * are implemented). Often grids will make use of a specific subclass of
 * DataProvider in order to prepare data for display in the grid.
 *
 * Actions (be they row or grid actions) are implemented by LinkAction
 * instances.
 *
 * There are several subclasses of GridHandler that provide generalized grids
 * of particular forms, such as CategoryGridHandler and ListbuilderHandler.
 *
 * The JavaScript front-end is described at <http://pkp.sfu.ca/wiki/index.php?title=JavaScript_widget_controllers#Grids>.
 *
 * For a concrete example of a grid handler (and related classes), see
 * AnnouncementTypeGridHandler.
 */

// Import the base Handler.
import('lib.pkp.classes.handler.PKPHandler');

// Import action class.
import('lib.pkp.classes.linkAction.LinkAction');

// Import grid classes.
import('lib.pkp.classes.controllers.grid.GridColumn');
import('lib.pkp.classes.controllers.grid.GridRow');

// Import JSON class for use with all AJAX requests.
import('lib.pkp.classes.core.JSONMessage');

// Grid specific action positions.
define('GRID_ACTION_POSITION_DEFAULT', 'default');
define('GRID_ACTION_POSITION_ABOVE', 'above');
define('GRID_ACTION_POSITION_LASTCOL', 'lastcol');
define('GRID_ACTION_POSITION_BELOW', 'below');

class GridHandler extends PKPHandler {

	/** @var string grid title locale key */
	var $_title = '';

	/** @var string empty row locale key */
	var $_emptyRowText = 'grid.noItems';

	/** @var string Grid foot note locale key */
	var $_footNote = '';

	/** @var GridDataProvider */
	var $_dataProvider;

	/**
	 * @var array Grid actions. The first key represents
	 *  the position of the action in the grid, the second key
	 *  represents the action id.
	 */
	var $_actions = array(GRID_ACTION_POSITION_DEFAULT => array());

	/** @var array The GridColumns of this grid. */
	var $_columns = array();

	/** @var Array The grid's data source. */
	var $_data;

	/** @var ItemIterator The item iterator to be used for paging. */
	var $_itemIterator;

	/** @var string The grid template. */
	var $_template;

	/** @var array The urls that will be used in JS handler. */
	var $_urls;

	/** @var array The grid features. */
	var $_features;


	/**
	 * Constructor.
	 * @param $dataProvider GridDataProvider An optional data provider
	 *  for the grid. If no data provider is given then the grid
	 *  assumes that child classes will override default method
	 *  implementations.
	 */
	function __construct($dataProvider = null) {
		$this->_dataProvider = $dataProvider;
		parent::__construct();
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the data provider.
	 * @return FilesGridDataProvider
	 */
	function getDataProvider() {
		return $this->_dataProvider;
	}

	/**
	 * Get the grid request parameters. These
	 * are the parameters that uniquely identify the
	 * data within a grid.
	 *
	 * NB: You should make sure to authorize and/or
	 * validate parameters before you publish them
	 * through this interface. Callers will assume that
	 * data accessed through this method will not have
	 * to be sanitized.
	 *
	 * The default implementation tries to retrieve
	 * request parameters from a data provider if there
	 * is one.
	 *
	 * @return array
	 */
	function getRequestArgs() {
		$dataProvider = $this->getDataProvider();
		$requestArgs = array();
		if (is_a($dataProvider, 'GridDataProvider')) {
			$requestArgs = $dataProvider->getRequestArgs();
		}

		$this->callFeaturesHook('getRequestArgs', array('grid' => &$this, 'requestArgs' => &$requestArgs));

		return $requestArgs;
	}

	/**
	 * Get a single grid request parameter.
	 * @see getRequestArgs()
	 *
	 * @param $key string The name of the parameter to retrieve.
	 * @return mixed
	 */
	function getRequestArg($key) {
		$requestArgs = $this->getRequestArgs();
		assert(isset($requestArgs[$key]));
		return $requestArgs[$key];
	}

	/**
	 * Get the grid title.
	 * @return string locale key
	 */
	function getTitle() {
		return $this->_title;
	}

	/**
	 * Set the grid title.
	 * @param $title string locale key
	 */
	function setTitle($title) {
		$this->_title = $title;
	}

	/**
	 * Get the no items locale key
	 */
	function getEmptyRowText() {
		return $this->_emptyRowText;
	}

	/**
	 * Set the no items locale key
	 */
	function setEmptyRowText($emptyRowText) {
		$this->_emptyRowText = $emptyRowText;
	}

	/**
	 * Get the grid foot note.
	 * @return string locale key
	 */
	function getFootNote() {
		return $this->_footNote;
	}

	/**
	 * Set the grid foot note.
	 * @param $footNote string locale key
	 */
	function setFootNote($footNote) {
		$this->_footNote = $footNote;
	}

	/**
	 * Get all actions for a given position within the grid.
	 * @param $position string The position of the actions.
	 * @return array The LinkActions for the given position.
	 */
	function getActions($position = GRID_ACTION_POSITION_ABOVE) {
		if(!isset($this->_actions[$position])) return array();
		return $this->_actions[$position];
	}

	/**
	 * Add an action.
	 * @param $position string The position of the action.
	 * @param $action Mixed a single action.
	 */
	function addAction($action, $position = GRID_ACTION_POSITION_ABOVE) {
		if (!isset($this->_actions[$position])) $this->_actions[$position] = array();
		$this->_actions[$position][$action->getId()] = $action;
	}

	/**
	 * Get all columns.
	 * @return array An array of GridColumn instances.
	 */
	function &getColumns() {
		return $this->_columns;
	}

	/**
	 * Retrieve a single column by id.
	 * @param $columnId
	 * @return GridColumn
	 */
	function getColumn($columnId) {
		assert(isset($this->_columns[$columnId]));
		return $this->_columns[$columnId];
	}

	/**
	 * Get columns by flag.
	 * @param $flag string
	 * @return array
	 */
	function &getColumnsByFlag($flag) {
		$columns = array();
		foreach ($this->getColumns() as $column) {
			if ($column->hasFlag($flag)) {
				$columns[$column->getId()] = $column;
			}
		}

		return $columns;
	}

	/**
	 * Get columns number. If a flag is passed, the columns
	 * using it will not be counted.
	 * @param $flag optional string
	 * @return int
	 */
	function getColumnsCount($flag = null) {
		$count = 0;
		foreach ($this->getColumns() as $column) {
			if (!$column->hasFlag($flag)) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Checks whether a column exists.
	 * @param $columnId
	 * @return boolean
	 */
	function hasColumn($columnId) {
		return isset($this->_columns[$columnId]);
	}

	/**
	 * Add a column.
	 * @param $column mixed A single GridColumn instance.
	 */
	function addColumn($column) {
		assert(is_a($column, 'GridColumn'));
		$this->_columns[$column->getId()] = $column;
	}

	/**
	 * Get the grid data.
	 * @param $request PKPRequest
	 * @return array
	 */
	function &getGridDataElements($request) {
		$filter = $this->getFilterSelectionData($request);

		// Try to load data if it has not yet been loaded.
		if (is_null($this->_data)) {
			$data = $this->loadData($request, $filter);

			if (is_null($data)) {
				// Initialize data to an empty array.
				$data = array();
			}

			$this->setGridDataElements($data);
		}

		$this->callFeaturesHook('getGridDataElements', array('request' => &$request, 'grid' => &$this, 'gridData' => &$data, 'filter' => &$filter));

		return $this->_data;
	}

	/**
	 * Check whether the grid has rows.
	 * @return boolean
	 */
	function hasGridDataElements($request) {
		$data =& $this->getGridDataElements($request);
		assert (is_array($data));
		return (boolean) count($data);
	}

	/**
	 * Set the grid data.
	 * @param $data mixed an array or ItemIterator with element data
	 */
	function setGridDataElements($data) {
		$this->callFeaturesHook('setGridDataElements', array('grid' => &$this, 'data' => &$data));

		// FIXME: We go to arrays for all types of iterators because
		// iterators cannot be re-used, see #6498.
		if (is_array($data)) {
			$this->_data = $data;
		} elseif(is_a($data, 'DAOResultFactory')) {
			$this->_data = $data->toAssociativeArray();
		} elseif(is_a($data, 'ItemIterator')) {
			$this->_data = $data->toArray();
		} else {
			assert(false);
		}
	}

	/**
	 * Get the grid template.
	 * @return string
	 */
	function getTemplate() {
		if (is_null($this->_template)) {
			$this->setTemplate('controllers/grid/grid.tpl');
		}

		return $this->_template;
	}

	/**
	 * Set the grid template.
	 * @param $template string
	 */
	function setTemplate($template) {
		$this->_template = $template;
	}

	/**
	 * Return all grid urls that will be used
	 * in JS handler.
	 * @return array
	 */
	function getUrls() {
		return $this->_urls;
	}

	/**
	 * Define the urls that will be used
	 * in JS handler.
	 * @param $request Request
	 * @param $extraUrls array Optional extra urls.
	 */
	function setUrls($request, $extraUrls = array()) {
		$router = $request->getRouter();
		$urls = array(
			'fetchGridUrl' => $router->url($request, null, null, 'fetchGrid', null, $this->getRequestArgs()),
			'fetchRowsUrl' => $router->url($request, null, null, 'fetchRows', null, $this->getRequestArgs()),
			'fetchRowUrl' => $router->url($request, null, null, 'fetchRow', null, $this->getRequestArgs())
		);
		$this->_urls = array_merge($urls, $extraUrls);
	}

	/**
	 * Override this method to return true if you want
	 * to use the grid within another component (e.g. to
	 * remove the title or change the layout accordingly).
	 *
	 * @return boolean
	 */
	function getIsSubcomponent() {
		return false;
	}

	/**
	 * Get all grid attached features.
	 * @return array
	 */
	function getFeatures() {
		return $this->_features;
	}

	/**
	 * Get the item iterator that represents this grid data.
	 * Should only be used for retriving paging data.
	 * See #6498.
	 * @return ItemIterator
	 */
	function getItemIterator() {
		return $this->_itemIterator;
	}

	/**
	 * Get "publish data changed" event list.
	 * @return array
	 */
	function getPublishChangeEvents() {
		return array();
	}

	// FIXME: Since we've moved to PHP5, maybe those methods
	// should be moved into interfaces like OrderableItems
	// and SelectableItems. Then each grid can implement
	// them in a clear way. It will also simplify this base
	// class hiding optional interfaces.

	//
	// Orderable items.
	//
	/**
	 * Override to return the data element sequence value.
	 * @param $gridDataElement mixed
	 * @return int
	 */
	function getDataElementSequence($row) {
		return 0; // Ordering is ambiguous or irrelevant.
	}

	/**
	 * Override to set the data element new sequence.
	 * @param $request PKPRequest
	 * @param $rowId int
	 * @param $gridDataElement mixed
	 * @param $newSequence int
	 */
	function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence) {
		assert(false);
	}

	//
	// Selectable items.
	//
	/**
	 * Returns the current selection state
	 * of the grid data element.
	 * @param $gridDataElement mixed
	 * @return boolean
	 */
	function isDataElementSelected($gridDataElement) {
		assert(false);
	}

	/**
	 * Get the select parameter name to store
	 * the selected files.
	 * @return string
	 */
	function getSelectName() {
		assert(false);
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
	function getRequestedRow($request, $args) {
		$isModified = isset($args['modify']);
		if (isset($args['rowId']) && !$isModified) {
			// A row ID was specified. Fetch it
			$elementId = $args['rowId'];

			// Retrieve row data for the requested row id
			$dataElement = $this->getRowDataElement($request, $elementId);
			if (is_null($dataElement)) {
				// If the row doesn't exist then
				// return null. It may be that the
				// row has been deleted in the meantime
				// and the client does not yet know about this.
				$nullVar = null;
				return $nullVar;
			}
		} elseif ( $isModified ) {
			$elementId = null;
			// The row is modified. The client may be asking
			// for a formatted new entry, to be saved later, or
			// for a representation of a modified row.
			$dataElement = $this->getRowDataElement($request, $elementId);
			if ( isset($args['rowId']) ) {
				// the rowId holds the elementId being modified
				$elementId = $args['rowId'];
			}
		}

		// Instantiate a new row
		return $this->_getInitializedRowInstance($request, $elementId, $dataElement, $isModified);
	}

	/**
	 * Render the passed row and return its markup.
	 * @param $request PKPRequest
	 * @param $row GridRow
	 * @return string
	 */
	function renderRow($request, $row) {
		$this->setFirstDataColumn();
		return $this->renderRowInternally($request, $row);
	}

	/**
	 * Get grid range info.
	 * @param $request PKPRequest
	 * @param $rangeName string The grid id.
	 * @param $contextData mixed
	 * @return DBResultRange
	 */
	function getGridRangeInfo($request, $rangeName, $contextData = null) {
		$rangeInfo = parent::getRangeInfo($request, $rangeName, $contextData);

		$this->callFeaturesHook('getGridRangeInfo', array('request' => &$request, 'grid' => &$this, 'rangeInfo' => $rangeInfo));

		return $rangeInfo;
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$dataProvider = $this->getDataProvider();
		$hasDataProvider = is_a($dataProvider, 'GridDataProvider');
		if ($hasDataProvider) {
			$this->addPolicy($dataProvider->getAuthorizationPolicy($request, $args, $roleAssignments));
		}

		$success = parent::authorize($request, $args, $roleAssignments);

		if ($hasDataProvider && $success === true) {
			$dataProvider->setAuthorizedContext($this->getAuthorizedContext());
		}

		return $success;
	}

	/**
	 * @see PKPHandler::initialize()
	 * @param $request PKPRequest
	 * @param $args array optional
	 */
	function initialize($request, $args = null) {
		parent::initialize($request);

		// Load grid-specific translations
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_GRID, LOCALE_COMPONENT_APP_COMMON);

		if ($this->getFilterForm() && $this->isFilterFormCollapsible()) {
			import('lib.pkp.classes.linkAction.request.NullAction');
			$this->addAction(
				new LinkAction(
					'search',
					new NullAction(),
					__('common.search'),
					'search_extras_expand'
				)
			);
		}

		// Give a chance to grid add features before calling hooks.
		// Because we must control when features are added to a grid,
		// this is the only place that should use the _addFeature() method.
		$this->_addFeatures($this->initFeatures($request, $args));
		$this->callFeaturesHook('gridInitialize', array('grid' => &$this));
	}


	//
	// Public handler methods
	//
	/**
	 * Render the entire grid controller and send
	 * it to the client.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function fetchGrid($args, $request) {
		$this->setUrls($request);

		// Prepare the template to render the grid.
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('grid', $this);
		$templateMgr->assign('request', $request);

		// Add rendered filter
		$renderedFilter = $this->renderFilter($request);
		$templateMgr->assign('gridFilterForm', $renderedFilter);

		// Add columns.
		$this->setFirstDataColumn();
		$columns = $this->getColumns();
		$templateMgr->assign('columns', $columns);

		$this->_fixColumnWidths();

		// Do specific actions to fetch this grid.
		$this->doSpecificFetchGridActions($args, $request, $templateMgr);

		// Assign additional params for the fetchRow and fetchGrid URLs to use.
		$templateMgr->assign('gridRequestArgs', $this->getRequestArgs());

		$this->callFeaturesHook('fetchGrid', array('grid' => &$this, 'request' => &$request));

		// Assign features.
		$templateMgr->assign('features', $this->getFeatures());

		// Let the view render the grid.
		return new JSONMessage(true, $templateMgr->fetch($this->getTemplate()));
	}

	/**
	 * Fetch all grid rows from loaded data.
	 * @param $args Array
	 * @param $request Request
	 * @return JSONMessage JSON object.
	 */
	function fetchRows($args, $request) {
		// Render the rows.
		$this->setFirstDataColumn();
		$elements = $this->getGridDataElements($request);
		$renderedRows = $this->renderRowsInternally($request, $elements);

		$json = new JSONMessage();
		$json->setStatus(false);

		if ($renderedRows) {
			$renderedRowsString = null;
			foreach ($renderedRows as $rowString) {
				$renderedRowsString .= $rowString;
			}
			$json->setStatus(true);
			$json->setContent($renderedRowsString);
		}

		$this->callFeaturesHook('fetchRows', array('request' => &$request, 'grid' => &$this, 'jsonMessage' => &$json));

		return $json;
	}

	/**
	 * Render a row and send it to the client. If the row no
	 * longer exists then inform the client.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object.
	 */
	function fetchRow(&$args, $request) {
		// Instantiate the requested row (includes a
		// validity check on the row id).
		$row = $this->getRequestedRow($request, $args);

		$json = new JSONMessage(true);
		if (is_null($row)) {
			// Inform the client that the row does no longer exist.
			$json->setAdditionalAttributes(array('elementNotFound' => $args['rowId']));
		} else {
			// Render the requested row
			$renderedRow = $this->renderRow($request, $row);
			$json->setContent($renderedRow);

			// Add the sequence map so grid can place the row at the correct position.
			$sequenceMap = $this->getRowsSequence($request);
			$json->setAdditionalAttributes(array('sequenceMap' => $sequenceMap));
		}

		$this->callFeaturesHook('fetchRow', array('request' => &$request, 'grid' => &$this, 'row' => &$row, 'jsonMessage' => &$json));

		// Render and return the JSON message.
		return $json;
	}

	/**
	 * Render a cell and send it to the client
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function fetchCell(&$args, $request) {
		// Check the requested column
		if(!isset($args['columnId'])) fatalError('Missing column id!');
		if(!$this->hasColumn($args['columnId'])) fatalError('Invalid column id!');
		$this->setFirstDataColumn();
		$column = $this->getColumn($args['columnId']);

		// Instantiate the requested row
		$row = $this->getRequestedRow($request, $args);
		if (is_null($row)) fatalError('Row not found!');

		// Render the cell
		return new JSONMessage(true, $this->_renderCellInternally($request, $row, $column));
	}

	/**
	 * Hook oportunity for grid features to request a save items sequence
	 * operation. If no grid feature that implements the saveSequence
	 * hook is attached to this grid, this operation will only return
	 * the data changed event json message.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function saveSequence($args, $request) {
		$this->callFeaturesHook('saveSequence', array('request' => &$request, 'grid' => &$this));

		return DAO::getDataChangedEvent();
	}

	/**
	 * Get the js handler for this component.
	 * @return string
	 */
	public function getJSHandler() {
		return '$.pkp.controllers.grid.GridHandler';
	}

	//
	// Protected methods to be overridden/used by subclasses
	//
	/**
	 * Return the sequence map of the current loaded grid items.
	 * This is not the sequence value of the data represented by the
	 * row, it's just the mapping of the rows sequence, in the order
	 * that they are loaded. To handle grid items ordering, see
	 * OrderItemsFeature class.
	 * @param $request PKPRequest
	 * @return array
	 */
	protected function getRowsSequence($request) {
		return array_keys($this->getGridDataElements($request));
	}


	/**
	 * Get a new instance of a grid row. May be
	 * overridden by subclasses if they want to
	 * provide a custom row definition.
	 * @return GridRow
	 */
	protected function getRowInstance() {
		//provide a sensible default row definition
		return new GridRow();
	}

	/**
	 * Create a data element from a request. This is used to format
	 * new rows prior to their insertion or existing rows that have
	 * been edited but not saved.
	 * @param $request PKPRequest
	 * @param $elementId int Reference to be filled with element
	 *  ID (if one is to be used)
	 * @return object
	 */
	protected function &getDataElementFromRequest($request, &$elementId) {
		fatalError('Grid does not support data element creation!');
	}

	/**
	 * Retrieve a single data element from the grid's data
	 * source corresponding to the given row id. If none is
	 * found then return null.
	 * @param $rowId string The row ID; reference permits modification.
	 * @return mixed
	 */
	protected function getRowDataElement($request, &$rowId) {
		$elements =& $this->getGridDataElements($request);

		assert(is_array($elements));
		if (!isset($elements[$rowId])) return null;

		return $elements[$rowId];
	}

	/**
	 * Implement this method to load data into the grid.
	 * @param $request Request
	 * @param $filter array An associative array with filter data as returned by
	 *  getFilterSelectionData(). If no filter has been selected by the user
	 *  then the array will be empty.
	 * @return grid data
	 */
	protected function loadData($request, $filter) {
		$gridData = null;
		$dataProvider = $this->getDataProvider();
		if (is_a($dataProvider, 'GridDataProvider')) {
			// Populate the grid with data from the
			// data provider.
			$gridData = $dataProvider->loadData($filter);
		}

		$this->callFeaturesHook('loadData', array('request' => &$request, 'grid' => &$this, 'gridData' => &$gridData));

		return $gridData;
	}

	/**
	 * Returns a Form object or the path name of a filter template.
	 * @return Form|string
	 */
	protected function getFilterForm() {
		return null;
	}

	/**
	 * Determine whether a filter form should be collapsible.
	 * @return boolean
	 */
	protected function isFilterFormCollapsible() {
		return true;
	}

	/**
	 * Method that extracts the user's filter selection from the request either
	 * by instantiating the filter's Form object or by reading the request directly
	 * (if using a simple filter template only).
	 * @param $request PKPRequest
	 * @return array
	 */
	protected function getFilterSelectionData($request) {
		return null;
	}

	/**
	 * Render the filter (a template or a Form).
	 * @param $request PKPRequest
	 * @param $filterData Array Data to be used by the filter template.
	 * @return string
	 */
	protected function renderFilter($request, $filterData = array()) {
		$form = $this->getFilterForm();
		switch(true) {
			case $form === null: // No filter form.
				return '';
			case is_a($form, 'Form'): // Form object subclass
				// Only read form data if the clientSubmit flag has been checked
				$clientSubmit = (boolean) $request->getUserVar('clientSubmit');
				if($clientSubmit) {
					$form->readInputData();
					$form->validate();
				}

				$form->initData($filterData, $request);
				return $form->fetch($request);
			case is_string($form): // HTML mark-up
				$templateMgr = TemplateManager::getManager($request);

				// Assign data to the filter.
				$templateMgr->assign('filterData', $filterData);

				// Assign current selected filter data.
				$filterSelectionData = $this->getFilterSelectionData($request);
				$templateMgr->assign('filterSelectionData', $filterSelectionData);

				return $templateMgr->fetch($form);
				break;
		}
		assert(false);
	}

	/**
	 * Returns a common 'no matches' result when subclasses find no results for
	 * AJAX autocomplete requests.
	 * @return JSONMessage JSON object
	 */
	protected function noAutocompleteResults() {
		$returner = array();
		$returner[] = array('label' => __('common.noMatches'), 'value' => '');

		return new JSONMessage(true, $returner);
	}

	/**
	 * Override this method if your subclass needs to perform
	 * different actions than the ones implemented here.
	 * This method is called by GridHandler::fetchGrid()
	 * @param $args array
	 * @param $request Request
	 */
	protected function doSpecificFetchGridActions($args, $request, $templateMgr) {
		// Render the body elements.
		$gridBodyParts = $this->renderGridBodyPartsInternally($request);
		$templateMgr->assign('gridBodyParts', $gridBodyParts);
	}

	/**
	 * Define the first column that will contain
	 * grid data.
	 *
	 * Override this method to define a different column
	 * than the first one.
	 */
	protected function setFirstDataColumn() {
		$columns =& $this->getColumns();
		$firstColumn = reset($columns);
		$firstColumn->addFlag('firstColumn', true);
	}

	/**
	 * Override to init grid features.
	 * This method is called by GridHandler::initialize()
	 * method that use the returned array with the initialized
	 * features to add them to grid.
	 * @param $request Request
	 * @param $args array
	 * @return array Array with initialized grid features objects.
	 */
	protected function initFeatures($request, $args) {
		return array();
	}

	/**
	 * Call the passed hook in all attached features.
	 * @param $hookName string
	 * @param $args array Arguments provided by this handler.
	 */
	protected function callFeaturesHook($hookName, $args) {
		$features = $this->getFeatures();
		if (is_array($features)) {
			foreach ($features as &$feature) {
				if (is_callable(array($feature, $hookName))) {
					$feature->$hookName($args);
				} else {
					assert(false);
				}
			}
		}
	}

	/**
	 * Cycle through the data and get generate the row HTML.
	 * @param $request PKPRequest
	 * @param $elements array The grid data elements to be rendered.
	 * @return array of HTML Strings for Grid Rows.
	 */
	protected function renderRowsInternally($request, &$elements) {
		// Iterate through the rows and render them according
		// to the row definition.
		$renderedRows = array();
		foreach ($elements as $elementId => $element) {
			// Instantiate a new row.
			$row = $this->_getInitializedRowInstance($request, $elementId, $element);

			// Render the row
			$renderedRows[] = $this->renderRowInternally($request, $row);
		}

		return $renderedRows;
	}

	/**
	 * Method that renders a single row.
	 *
	 * NB: You must have initialized the row
	 * before you call this method.
	 *
	 * @param $request PKPRequest
	 * @param $row GridRow
	 * @return string the row HTML
	 */
	protected function renderRowInternally($request, $row) {
		// Iterate through the columns and render the
		// cells for the given row.
		$renderedCells = array();
		$columns = $this->getColumns();
		foreach ($columns as $column) {
			assert(is_a($column, 'GridColumn'));
			$renderedCells[] = $this->_renderCellInternally($request, $row, $column);
		}

		// Pass control to the view to render the row
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'grid' => $this,
			'columns' => $columns,
			'cells' => $renderedCells,
			'row' => $row,
		));
		return $templateMgr->fetch($row->getTemplate());
	}

	/**
	 * Method that renders tbodys to go in the grid main body.
	 * @param Request $request
	 * @return array
	 */
	protected function renderGridBodyPartsInternally($request) {
		// Render the rows.
		$elements = $this->getGridDataElements($request);
		$renderedRows = $this->renderRowsInternally($request, $elements);

		// Render the body part.
		$templateMgr = TemplateManager::getManager($request);
		$gridBodyParts = array();
		if ( count($renderedRows) > 0 ) {
			$templateMgr->assign('grid', $this);
			$templateMgr->assign('rows', $renderedRows);
			$gridBodyParts[] = $templateMgr->fetch('controllers/grid/gridBodyPart.tpl');
		}
		return $gridBodyParts;
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
	private function _getInitializedRowInstance($request, $elementId, &$element, $isModified = false) {
		// Instantiate a new row
		$row = $this->getRowInstance();
		$row->setGridId($this->getId());
		$row->setId($elementId);
		$row->setData($element);
		$row->setRequestArgs($this->getRequestArgs());
		$row->setIsModified($isModified);

		// Initialize the row before we render it
		$row->initialize($request);
		$this->callFeaturesHook('getInitializedRowInstance', array('grid' => &$this, 'row' => &$row));
		return $row;
	}

	/**
	 * Method that renders a cell.
	 *
	 * NB: You must have initialized the row
	 * before you call this method.
	 *
	 * @param $request PKPRequest
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return string the cell HTML
	 */
	private function _renderCellInternally($request, $row, $column) {
		// If there is no object, then we want to return an empty row.
		// override the assigned GridCellProvider and provide the default.
		$element =& $row->getData();
		if ( is_null($element) && $row->getIsModified() ) {
			import('lib.pkp.classes.controllers.grid.GridCellProvider');
			$cellProvider = new GridCellProvider();
			return $cellProvider->render($request, $row, $column);
		}

		// Otherwise, get the cell content.
		// If row defines a cell provider, use it.
		$cellProvider = $row->getCellProvider();
		if (!is_a($cellProvider, 'GridCellProvider')) {
			// Remove reference to the row variable.
			unset($cellProvider);
			// Get cell provider from column.
			$cellProvider = $column->getCellProvider();
		}

		return $cellProvider->render($request, $row, $column);
	}

	/**
	 * Method that grabs all the existing columns and makes sure the column widths add to exactly 100
	 * N.B. We do some extra column fetching because PHP makes copies of arrays with foreach.
	 */
	private function _fixColumnWidths() {
		$columns =& $this->getColumns();
		$width = 0;
		$noSpecifiedWidthCount = 0;
		// Find the total width and how many columns do not specify their width.
		foreach ($columns as $column) {
			if ($column->hasFlag('width')) {
				$width += $column->getFlag('width');
			} else {
				$noSpecifiedWidthCount++;
			}
		}

		// Four cases: we have to add or remove some width, and either we have wiggle room or not.
		// First case, width less than 100 and some unspecified columns to add it to.
		if ($width < 100) {
			if ($noSpecifiedWidthCount > 0) {
				// We need to add width to columns that did not specify it.
				foreach ($columns as $column) {
					if (!$column->hasFlag('width')) {
						$modifyColumn = $this->getColumn($column->getId());
						$modifyColumn->addFlag('width', round((100 - $width)/$noSpecifiedWidthCount));
						unset($modifyColumn);
					}
				}
			}
		}

		// Second case, width higher than 100 and all columns width specified.
		if ($width > 100) {
			if ($noSpecifiedWidthCount == 0) {
				// We need to remove width from all columns equally.
				$columnsToModify = $columns;
				foreach ($columns as $key => $column) {
					// We don't want to change the indent column widht, so avoid it.
					if ($column->getId() == 'indent') {
						unset($columnsToModify[$key]);
					}
				}

				// Calculate the value to remove from all columns.
				$difference = $width - 100;
				$columnsCount = count($columnsToModify);
				$removeValue = round($difference/$columnsCount);
				foreach ($columnsToModify as $column) {
					$modifyColumn = $this->getColumn($column->getId());
					if (end($columnsToModify) === $column) {
						// Handle rounding problems.
						$totalWidth = $width - ($removeValue * $columnsCount);
						if ($totalWidth < 100) {
							$removeValue -= 100 - $totalWidth;
						}
					}

					$modifyColumn->addFlag('width', $modifyColumn->getFlag('width') - $removeValue);
				}
			}
		}
	}

	/**
	 * Add grid features.
	 * @param $features array
	 */
	private function _addFeatures($features) {
		assert(is_array($features));
		foreach ($features as &$feature) {
			assert(is_a($feature, 'GridFeature'));
			$this->_features[$feature->getId()] = $feature;
		}
	}
}
?>
