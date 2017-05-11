<?php

/**
 * @file classes/controllers/grid/filter/PKPFilterGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPFilterGridHandler
 * @ingroup classes_controllers_grid_filter
 *
 * @brief Manage filter administration and settings.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');

// import filter grid specific classes
import('lib.pkp.classes.controllers.grid.filter.PKPFilterGridRow');
import('lib.pkp.classes.controllers.grid.filter.FilterGridCellProvider');

// import metadata framework classes
import('lib.pkp.classes.metadata.MetadataDescription');


class PKPFilterGridHandler extends GridHandler {
	/** @var object the context (journal, press, conference) for which we manage filters */
	var $_context;

	/** @var string the description text to be displayed in the filter form */
	var $_formDescription;

	/** @var mixed the symbolic name of the filter group to be configured in this grid */
	var $_filterGroupSymbolic;

	/**
	 * Constructor
	 */
	function __construct() {
		// Instantiate the citation DAO which will implicitly
		// define the filter groups for parsers and lookup
		// database connectors.
		DAORegistry::getDAO('CitationDAO');

		parent::__construct();
	}

	//
	// Getters/Setters
	//
	/**
	 * Set the context that filters are being managed for.
	 * This object must implement the getId() and getSettings()
	 * methods.
	 *
	 * @param $context DataObject The context (journal, press,
	 *  conference) for which we manage filters.
	 */
	function setContext(&$context) {
		$this->_context =& $context;
	}

	/**
	 * Get the context that filters are being managed for.
	 *
	 * @return DataObject The context (journal, press,
	 *  conference) for which we manage filters.
	 */
	function &getContext() {
		return $this->_context;
	}

	/**
	 * Set the form description text
	 * @param $formDescription string
	 */
	function setFormDescription($formDescription) {
		$this->_formDescription = $formDescription;
	}

	/**
	 * Get the form description text
	 * @return string
	 */
	function getFormDescription() {
		return $this->_formDescription;
	}

	/**
	 * Set the filter group symbol
	 * @param $filterGroupSymbolic string
	 */
	function setFilterGroupSymbolic($filterGroupSymbolic) {
		$this->_filterGroupSymbolic = $filterGroupSymbolic;
	}

	/**
	 * Get the filter group symbol
	 * @return string
	 */
	function getFilterGroupSymbolic() {
		return $this->_filterGroupSymbolic;
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load manager-specific translations
		// FIXME: the submission translation component can be removed
		// once all filters have been moved to plug-ins (see submission.xml).
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_SUBMISSION);

		// Retrieve the filters to be displayed in the grid
		$router = $request->getRouter();
		$context = $router->getContext($request);
		$contextId = (is_null($context)?CONTEXT_ID_NONE:$context->getId());
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$data = $filterDao->getObjectsByGroup($this->getFilterGroupSymbolic(), $contextId);
		$this->setGridDataElements($data);

		// Grid action
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'addFilter',
				new AjaxModal(
					$router->url($request, null, null, 'addFilter'),
					__('grid.action.addItem'),
					'modal_manage'
				),
				__('grid.action.addItem'),
				'add_filter'
			)
		);

		// Columns
		$cellProvider = new FilterGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'displayName',
				'manager.setup.filter.grid.filterDisplayName',
				false,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'settings',
				'manager.setup.filter.grid.filterSettings',
				false,
				null,
				$cellProvider
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::getRowInstance()
	 */
	protected function getRowInstance() {
		// Return a filter row
		return new PKPFilterGridRow();
	}


	//
	// Public Filter Grid Actions
	//
	/**
	 * An action to manually add a new filter
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addFilter(&$args, $request) {
		// Calling editFilter() to edit a new filter.
		return $this->editFilter($args, $request, true);
	}

	/**
	 * Edit a filter
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editFilter(&$args, $request, $newFilter = false) {
		// Identify the filter to be edited
		if ($newFilter) {
			$filter = null;
		} else {
			$filter =& $this->getFilterFromArgs($request, $args, true);
		}

		// Form handling
		import('lib.pkp.classes.controllers.grid.filter.form.FilterForm');
		$filterForm = new FilterForm($filter, $this->getTitle(), $this->getFormDescription(),
				$this->getFilterGroupSymbolic());

		$filterForm->initData($this->getGridDataElements($request));

		return new JSONMessage(true, $filterForm->fetch($request));
	}

	/**
	 * Update a filter
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateFilter(&$args, $request) {
		if(!$request->isPost()) fatalError('Cannot update filter via GET request!');

		// Identify the citation to be updated
		$filter =& $this->getFilterFromArgs($request, $args, true);

		// Form initialization
		import('lib.pkp.classes.controllers.grid.filter.form.FilterForm');
		$nullVar = null;
		$filterForm = new FilterForm($filter, $this->getTitle(), $this->getFormDescription(),
				$nullVar); // No filter group required here.
		$filterForm->readInputData();

		// Form validation
		if ($filterForm->validate()) {
			// Persist the filter.
			$filterForm->execute($request);

			return DAO::getDataChangedEvent();
		} else {
			// Re-display the filter form with error messages
			// so that the user can fix it.
			return new JSONMessage(false, $filterForm->fetch($request));
		}
	}

	/**
	 * Delete a filter
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteFilter(&$args, $request) {
		// Identify the filter to be deleted
		$filter = $this->getFilterFromArgs($request, $args);

		$filterDao = DAORegistry::getDAO('FilterDAO');
		if ($request->checkCSRF() && $filterDao->deleteObject($filter)) {
			return DAO::getDataChangedEvent();
		} else {
			return new JSONMessage(false, __('manager.setup.filter.grid.errorDeletingFilter'));
		}
	}


	//
	// Protected helper functions
	//
	/**
	 * This will retrieve a filter object from the
	 * grids data source based on the request arguments.
	 * If no filter can be found then this will raise
	 * a fatal error.
	 * @param $args array
	 * @param $mayBeTemplate boolean whether filter templates
	 *  should be considered.
	 * @return Filter
	 */
	function &getFilterFromArgs($request, &$args, $mayBeTemplate = false) {
		if (isset($args['filterId'])) {
			// Identify the filter id and retrieve the
			// corresponding element from the grid's data source.
			$filter =& $this->getRowDataElement($request, $args['filterId']);
			if (!is_a($filter, 'Filter')) fatalError('Invalid filter id!');
		} elseif ($mayBeTemplate && isset($args['filterTemplateId'])) {
			// We need to instantiate a new filter from a
			// filter template.
			$filterTemplateId = $args['filterTemplateId'];
			$filterDao = DAORegistry::getDAO('FilterDAO');
			$filter =& $filterDao->getObjectById($filterTemplateId);
			if (!is_a($filter, 'Filter')) fatalError('Invalid filter template id!');

			// Reset the filter id and template flag so that the
			// filter form correctly handles this filter as a new filter.
			$filter->setId(null);
			$filter->setIsTemplate(false);
		} else {
			fatalError('Missing filter id!');
		}
		return $filter;
	}
}

?>
