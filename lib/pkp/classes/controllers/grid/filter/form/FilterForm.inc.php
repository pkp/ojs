<?php

/**
 * @file classes/controllers/grid/filter/form/FilterForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterForm
 * @ingroup classes_controllers_grid_filter_form
 *
 * @brief Form for adding/editing a filter.
 * New filter instances are based on filter templates.
 */

import('lib.pkp.classes.form.Form');

class FilterForm extends Form {
	/** @var Filter the filter being edited */
	var $_filter;

	/** @var string a translation key for the filter form title */
	var $_title;

	/** @var string a translation key for the filter form description */
	var $_description;

	/** @var string the filter group to be configured in this form */
	var $_filterGroupSymbolic;

	/**
	 * Constructor.
	 * @param $filter Filter
	 * @param $filterGroupSymbolic string
	 * @param $title string
	 * @param $description string
	 */
	function __construct(&$filter, $title, $description, $filterGroupSymbolic) {
		parent::__construct('controllers/grid/filter/form/filterForm.tpl');

		// Initialize internal state.
		$this->_filter =& $filter;
		$this->_title = $title;
		$this->_description = $description;
		$this->_filterGroupSymbolic = $filterGroupSymbolic;

		// Transport filter/template id.
		$this->readUserVars(array('filterId', 'filterTemplateId'));

		// Validation check common to all requests.
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

		// Validation check for template selection.
		if (!is_null($filter) && !is_numeric($filter->getId())) {
			$this->addCheck(new FormValidator($this, 'filterTemplateId', 'required', 'manager.setup.filter.grid.filterTemplateRequired'));
		}

		// Add filter specific meta-data and checks.
		if (is_a($filter, 'Filter')) {
			$this->setData('filterSettings', $filter->getSettings());
			foreach($filter->getSettings() as $filterSetting) {
				// Add check corresponding to filter setting.
				$settingCheck =& $filterSetting->getCheck($this);
				if (!is_null($settingCheck)) $this->addCheck($settingCheck);
			}
		}
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the filter
	 * @return Filter
	 */
	function &getFilter() {
		return $this->_filter;
	}

	/**
	 * Get the filter form title
	 * @return string
	 */
	function getTitle() {
		return $this->_title;
	}

	/**
	 * Get the filter form description
	 * @return string
	 */
	function getDescription() {
		return $this->_description;
	}

	/**
	 * Get the filter group symbol
	 * @return mixed
	 */
	function getFilterGroupSymbolic() {
		return $this->_filterGroupSymbolic;
	}

	//
	// Template methods from Form
	//
	/**
	 * Initialize form data.
	 * @param $alreadyInstantiatedFilters array
	 */
	function initData(&$alreadyInstantiatedFilters) {
		// Transport filter/template id.
		$this->readUserVars(array('filterId', 'filterTemplateId'));

		$filter =& $this->getFilter();
		if (is_a($filter, 'Filter')) {
			// A transformation has already been chosen
			// so identify the settings and edit them.

			// Add filter default settings as form data.
			foreach($filter->getSettings() as $filterSetting) {
				// Add filter setting data
				$settingName = $filterSetting->getName();
				$this->setData($settingName, $filter->getData($settingName));
			}
		} else {
			// The user did not yet choose a template
			// to base the transformation on.

			// Retrieve all compatible filter templates
			// from the database.
			$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
			$filterTemplateObjects =& $filterDao->getObjectsByGroup($this->getFilterGroupSymbolic(), 0, true);
			$filterTemplates = array();

			// Make a blacklist of filters that cannot be
			// instantiated again because they already
			// have been instantiated and cannot be parameterized.
			$filterClassBlacklist = array();
			foreach($alreadyInstantiatedFilters as $alreadyInstantiatedFilter) {
				if (!$alreadyInstantiatedFilter->hasSettings()) {
					$filterClassBlacklist[] = $alreadyInstantiatedFilter->getClassName();
				}
			}

			foreach($filterTemplateObjects as $filterTemplateObject) {
				// Check whether the filter is on the blacklist.
				if (in_array($filterTemplateObject->getClassName(), $filterClassBlacklist)) continue;

				// The filter can still be added.
				$filterTemplates[$filterTemplateObject->getId()] = $filterTemplateObject->getDisplayName();
			}
			$this->setData('filterTemplates', $filterTemplates);

			// There are no more filter templates to
			// be chosen from.
			if (empty($filterTemplates)) $this->setData('noMoreTemplates', true);
		}
	}

	/**
	 * Initialize form data from user submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('filterId', 'filterTemplateId'));
		// A value of -1 for the filter template means "nothing selected"
		if ($this->getData('filterTemplate') == '-1') $this->setData('filterTemplate', '');

		$filter =& $this->getFilter();
		if(is_a($filter, 'Filter')) {
			foreach($filter->getSettings() as $filterSetting) {
				$userVars[] = $filterSetting->getName();
			}
			$this->readUserVars($userVars);
		}
	}

	/**
	 * @see Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);

		// The form description depends on the current state
		// of the selection process: do we select a filter template
		// or configure the settings of a selected template?
		$filter =& $this->getFilter();
		if (is_a($filter, 'Filter')) {
			$displayName = $filter->getDisplayName();
			$templateMgr->assign('filterDisplayName', $displayName);
			if (count($filter->getSettings())) {
				// We need a filter specific translation key so that we
				// can explain the filter's configuration options.
				// We use the display name to generate such a key as this
				// is probably easiest for translators to understand.
				// This also has the advantage that we can explain
				// composite filters individually.
				// FIXME: When we start to translate display names then
				// please make sure that you use the en-US key for this
				// processing. Alternatively we might want to introduce
				// an alphanumeric "filter key" to the filters table.
				$filterKey = PKPString::regexp_replace('/[^a-zA-Z0-9]/', '', $displayName);
				$filterKey = strtolower(substr($filterKey, 0, 1)).substr($filterKey, 1);
				$formDescriptionKey = $this->getDescription().'.'.$filterKey;
			} else {
				$formDescriptionKey = $this->getDescription().'Confirm';
			}
		} else {
			$templateMgr->assign('filterDisplayName', '');
			$formDescriptionKey = $this->getDescription().'Template';
		}

		$templateMgr->assign('formTitle', $this->getTitle());
		$templateMgr->assign('formDescription', $formDescriptionKey);

		return parent::fetch($request);
	}

	/**
	 * Save filter
	 * @param $request PKPRequest
	 */
	function execute($request) {
		$filter = $this->getFilter();
		assert(is_a($filter, 'Filter'));

		// Configure the filter
		foreach($filter->getSettings() as $filterSetting) {
			$settingName = $filterSetting->getName();
			$filter->setData($settingName, $this->getData($settingName));
		}

		// Persist the filter
		$filterDao = DAORegistry::getDAO('FilterDAO');
		if (is_numeric($filter->getId())) {
			$filterDao->updateObject($filter);
		} else {
			$router = $request->getRouter();
			$context = $router->getContext($request);
			$contextId = (is_null($context)?CONTEXT_ID_NONE:$context->getId());
			$filterDao->insertObject($filter, $contextId);
		}
		return true;
	}
}

?>
