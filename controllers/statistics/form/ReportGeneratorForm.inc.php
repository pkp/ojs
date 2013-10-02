<?php

/**
 * @file controllers/statistics/form/ReportGeneratorForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportGeneratorForm
 * @ingroup controllers_statistics_form
 * @see Form
 *
 * @brief Form to generate custom statistics reports.
 */

import('lib.pkp.classes.form.Form');

class ReportGeneratorForm extends Form {

	/* @var $_columns array */
	var $_columns;

	/* @var $_objects array */
	var $_objects;

	/* @var $_fileTypes array */
	var $_fileTypes;

	/* @var $_metricType string */
	var $_metricType;

	/**
	 * Constructor.
	 * @param $columns array Report column names.
	 * @param $objects array Object types.
	 * @param $fileTypes array File types.
	 * @param $metricType string The default report metric type.
	 */
	function ReportGeneratorForm($columns, $objects, $fileTypes, $metricType) {
		parent::Form('controllers/statistics/form/reportGeneratorForm.tpl');

		$this->_columns = $columns;
		$this->_objects = $objects;
		$this->_fileTypes = $fileTypes;
		$this->_metricType = $metricType;

		$this->addCheck(new FormValidatorArray($this, 'columns', 'required', 'manager.statistics.reports.form.columnsRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize the form from the current settings.
	 */
	function fetch(&$request) {
		$router =& $request->getRouter();
		$context =& $router->getContext($request);
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issueFactory =& $issueDao->getIssues($context->getId());
		$issueIdAndTitles = array();
		while ($issue =& $issueFactory->next()) { /* @var $issue Issue */
			$issueIdAndTitles[$issue->getId()] = $issue->getIssueIdentification();
		}

		$plugin =& PluginRegistry::getPlugin('generic', 'usagestatsplugin'); /* @var $plugin UsageStatsPlugin */
		if (is_a($plugin, 'UsageStatsPlugin')) {
			$geoLocationTool =& $plugin->getGeoLocationTool();
			if ($geoLocationTool) {
				$countryCodes = $geoLocationTool->getAllCountryCodes();
				$countryCodes = array_combine($countryCodes, $countryCodes);
				$this->setData('countriesOptions', $countryCodes);
			}
		}

		$orderColumns = $this->_columns;
		$nonOrderableColumns = array(STATISTICS_DIMENSION_ASSOC_TYPE,
			STATISTICS_DIMENSION_SUBMISSION_ID,
			STATISTICS_DIMENSION_ISSUE_ID,
			STATISTICS_DIMENSION_CONTEXT_ID,
			STATISTICS_DIMENSION_REGION,
			STATISTICS_DIMENSION_FILE_TYPE,
			STATISTICS_DIMENSION_METRIC_TYPE
		);
		foreach($nonOrderableColumns as $column) {
			unset($orderColumns[$column]);
		}

		$this->setData('metricType', $this->_metricType);
		$this->setData('issuesOptions', $issueIdAndTitles);
		$this->setData('objectTypesOptions', $this->_objects);
		$this->setData('fileTypesOptions', $this->_fileTypes);
		$this->setData('fileAssocTypes', array(ASSOC_TYPE_GALLEY, ASSOC_TYPE_ISSUE_GALLEY));
		$this->setData('orderColumnsOptions', $orderColumns);
		$this->setData('orderDirectionsOptions', array(
			STATISTICS_ORDER_ASC => __('manager.statistics.reports.orderDir.asc'),
			STATISTICS_ORDER_DESC => __('manager.statistics.reports.orderDir.desc')));
		$this->setData('columnsOptions', $this->_columns);

		return parent::fetch($request);
	}

	/**
	 * Assign user-submitted data to form.
	 */
	function readInputData() {
		$this->readUserVars(array('columns', 'objectTypes', 'fileTypes', 'objectIds', 'issues',
			'articles', 'month', 'monthFrom', 'monthTo', 'currentMonth', 'day', 'dayFrom', 'dayTo',
			'today', 'countries', 'regions', 'cityNames', 'orderByColumn', 'orderByDirection'));
		return parent::readInputData();
	}

	/**
	 * @see Form::execute()
	 */
	function execute(&$request) {
		parent::execute();
		$router =& $request->getRouter(); /* @var $router PageRouter */
		$context =& $router->getContext($request);

		$columns = $this->getData('columns');
		$filter = array();
		if ($this->getData('objectTypes')) {
			$filter[STATISTICS_DIMENSION_ASSOC_TYPE] = $this->getData('objectTypes');
		}

		if ($this->getData('objectIds') && count($filter[STATISTICS_DIMENSION_ASSOC_TYPE] == 1)) {
			$objectIds = explode(',', $this->getData('objectIds'));
			$filter[STATISTICS_DIMENSION_ASSOC_ID] = $objectIds;
		}

		if ($this->getData('fileTypes')) {
			$filter[STATISTICS_DIMENSION_FILE_TYPE] = $this->getData('fileTypes');
		}

		$filter[STATISTICS_DIMENSION_CONTEXT_ID] = $context->getId();

		if ($this->getData('issues')) {
			$filter[STATISTICS_DIMENSION_ISSUE_ID] = $this->getData('issues');
		}

		if ($this->getData('articles')) {
			$filter[STATISTICS_DIMENSION_SUBMISSION_ID] = $this->getData('articles');
		}

		if ($this->getData('currentMonth')) {
			$filter[STATISTICS_DIMENSION_MONTH] = STATISTICS_CURRENT_MONTH;
		} else {
			if ($this->getData('month')) {
				$filter[STATISTICS_DIMENSION_MONTH] = $this->getData('month');
			}

			if ($this->getData('monthFrom')) {
				// Erase any possible exact month definition.
				$filter[STATISTICS_DIMENSION_MONTH] = array();
				$filter[STATISTICS_DIMENSION_MONTH]['from'] = $this->getData('monthFrom');

				if ($this->getData('monthTo')) {
					$filter[STATISTICS_DIMENSION_MONTH]['to'] = $this->getData('monthTo');
				} else {
					// Use the current month.
					$filter[STATISTICS_DIMENSION_MONTH]['to'] = date('Ym', time());
				}
			}
		}

		if ($this->getData('today')) {
			$filter[STATISTICS_DIMENSION_DAY] = STATISTICS_CURRENT_DAY;
		} else {
			if ($this->getData('day')) {
				$filter[STATISTICS_DIMENSION_DAY] = $this->getData('day');
			}

			if ($this->getData('dayFrom')) {
				// Erase any possible exact month definition.
				$filter[STATISTICS_DIMENSION_DAY] = array();
				$filter[STATISTICS_DIMENSION_DAY]['from'] = $this->getData('dayFrom');

				if ($this->getData('dayTo')) {
					$filter[STATISTICS_DIMENSION_DAY]['to'] = $this->getData('dayTo');
				} else {
					// Use the current day.
					$filter[STATISTICS_DIMENSION_DAY]['to'] = date('Ymd', time());
				}
			}
		}

		if ($this->getData('countries')) {
			$filter[STATISTICS_DIMENSION_COUNTRY] = $this->getData('countries');
		}

		if ($this->getData('regions')) {
			$filter[STATISTICS_DIMENSION_REGION] = $this->getData('regions');
		}

		if ($this->getData('cityNames')) {
			$cityNames = explode(',', $this->getData('cityNames'));
			$filter[STATISTICS_DIMENSION_CITY] = $cityNames;
		}

		$orderBy = array();
		if ($this->getData('orderByColumn') && $this->getData('orderByDirection')) {
			$orderByColumn = $this->getData('orderByColumn');
			$orderByDirection = $this->getData('orderByDirection');

			$columnIndex = 0;

			foreach ($orderByColumn as $column) {
				if ($column != '0' && !isset($orderBy[$column])) {
					$orderByDir = $orderByDirection[$columnIndex];
					if ($orderByDir == STATISTICS_ORDER_ASC || $orderByDir == STATISTICS_ORDER_DESC) {
						$orderBy[$column] = $orderByDir;
					}
				}

				$columnIndex++;
			}
		}

		$args = array(
			'metricType' => $this->_metricType,
			'columns' => $columns,
			'filters' => serialize($filter)
		);

		if (!empty($orderBy)) {
			$args['orderBy'] = serialize($orderBy);
		}

		$dispatcher =& $request->getDispatcher(); /* @var $dispatcher Dispatcher */
		return $dispatcher->url($request, ROUTE_PAGE, null, 'manager', 'generateReport', null, $args);
	}
}

?>
