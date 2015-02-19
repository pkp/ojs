<?php

/**
 * @file controllers/statistics/form/ReportGeneratorForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportGeneratorForm
 * @ingroup controllers_statistics_form
 * @see Form
 *
 * @brief Form to generate custom statistics reports.
 */

import('lib.pkp.classes.form.Form');

define('TIME_FILTER_OPTION_CURRENT_DAY', 0);
define('TIME_FILTER_OPTION_CURRENT_MONTH', 1);
define('TIME_FILTER_OPTION_RANGE_DAY', 2);
define('TIME_FILTER_OPTION_RANGE_MONTH', 3);

class ReportGeneratorForm extends Form {

	/* @var $_columns array */
	var $_columns;

	/* @var $_objects array */
	var $_objects;

	/* @var $_fileTypes array */
	var $_fileTypes;

	/* @var $_metricType string */
	var $_metricType;

	/* @var $_defaultReportTemplates array */
	var $_defaultReportTemplates;

	/* @var $_reportTemplateIndex int */
	var $_reportTemplateIndex;

	/**
	 * Constructor.
	 * @param $columns array Report column names.
	 * @param $objects array Object types.
	 * @param $fileTypes array File types.
	 * @param $metricType string The default report metric type.
	 * @param $defaultReportTemplates array Default report templates that
	 * defines columns and filters selections. The key for each array
	 * item is expected to be a localized key that describes the
	 * report Template.
	 * @param $reportTemplateIndex int (optional) Current report template index
	 * from the passed default report templates array.
	 */
	function ReportGeneratorForm($columns, $objects, $fileTypes, $metricType, $defaultReportTemplates, $reportTemplateIndex = null) {
		parent::Form('controllers/statistics/form/reportGeneratorForm.tpl');

		$this->_columns = $columns;
		$this->_objects = $objects;
		$this->_fileTypes = $fileTypes;
		$this->_metricType = $metricType;
		$this->_defaultReportTemplates = $defaultReportTemplates;
		$this->_reportTemplateIndex = $reportTemplateIndex;

		$this->addCheck(new FormValidatorArray($this, 'columns', 'required', 'manager.statistics.reports.form.columnsRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize the form from the current settings.
	 */
	function fetch(&$request) {
		$router =& $request->getRouter();
		$context =& $router->getContext($request);
		$columns = $this->_columns;

		$availableMetricTypeStrings = StatisticsHelper::getAllMetricTypeStrings();
		if (count($availableMetricTypeStrings) > 1) {
			$this->setData('metricTypeOptions', $availableMetricTypeStrings);
		}

		$reportTemplateOptions = array();
		$reportTemplates = $this->_defaultReportTemplates;
		foreach($reportTemplates as $reportTemplate) {
			$reportTemplateOptions[] = __($reportTemplate['nameLocaleKey']);
		}

		if (!empty($reportTemplateOptions)) $this->setData('reportTemplateOptions', $reportTemplateOptions);

		$reportTemplateIndex = (int) $this->_reportTemplateIndex;
		if (!is_null($reportTemplateIndex) && isset($reportTemplates[$reportTemplateIndex])) {
			$reportTemplate = $reportTemplates[$reportTemplateIndex];
			$reportColumns = $reportTemplate['columns'];
			if (!is_array($reportColumns)) continue;

			$this->setData('columns', $reportColumns);
			$this->setData('reportTemplate', $reportTemplateIndex);
			if (isset($reportTemplate['aggregationColumns'])) {
				$aggreationColumns = $reportTemplate['aggregationColumns'];
				if (!is_array($aggreationColumns)) continue;

				$aggreationOptions = $selectedAggregationOptions = array();
				foreach ($aggreationColumns as $column) {
					$columnName = StatisticsHelper::getColumnNames($column);
					if (!$columnName) continue;
					$aggreationOptions[$column] = $columnName;
				}
				$this->setData('aggregationOptions', $aggreationOptions);
				$this->setData('selectedAggregationOptions', array_intersect($aggreationColumns, $reportColumns));
			}

			if (isset($reportTemplate['filter']) && is_array($reportTemplate['filter'])) {
				foreach ($reportTemplate['filter'] as $dimension => $filter) {
					switch ($dimension) {
						case STATISTICS_DIMENSION_ASSOC_TYPE:
							$this->setData('objectTypes', $filter);
							break;
					}
				}
			}
		}

		$timeFilterSelectedOption = $request->getUserVar('timeFilterOption');
		if (is_null($timeFilterSelectedOption)) {
			$timeFilterSelectedOption = TIME_FILTER_OPTION_CURRENT_MONTH;
		}
		switch ($timeFilterSelectedOption) {
			case TIME_FILTER_OPTION_CURRENT_DAY:
				$this->setData('today', true);
				break;
			case TIME_FILTER_OPTION_CURRENT_MONTH:
			default:
				$this->setData('currentMonth', true);
				break;
			case TIME_FILTER_OPTION_RANGE_DAY:
				$this->setData('byDay', true);
				break;
			case TIME_FILTER_OPTION_RANGE_MONTH:
				$this->setData('byMonth', true);
				break;
		}

		$startTime = $request->getUserDateVar('dateStart');
		$endTime = $request->getUserDateVar('dateEnd');
		if (!$startTime) $startTime = time();
		if (!$endTime) $endTime = time();

		$this->setData('dateStart', $startTime);
		$this->setData('dateEnd', $endTime);

		if (isset($columns[STATISTICS_DIMENSION_ISSUE_ID])) {
			$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issueFactory =& $issueDao->getIssues($context->getId());
			$issueIdAndTitles = array();
			while ($issue =& $issueFactory->next()) {
				/* @var $issue Issue */
				$issueIdAndTitles[$issue->getId()] = $issue->getIssueIdentification();
			}
			$this->setData('issuesOptions', $issueIdAndTitles);
			$this->setData('showArticleInput', isset($columns[STATISTICS_DIMENSION_SUBMISSION_ID]));

		}

		if (isset($columns[STATISTICS_DIMENSION_COUNTRY])) {
			$geoLocationTool =& StatisticsHelper::getGeoLocationTool();
			if ($geoLocationTool) {
				$countryCodes = $geoLocationTool->getAllCountryCodes();
				$countryCodes = array_combine($countryCodes, $countryCodes);
				$this->setData('countriesOptions', $countryCodes);
			}

			$this->setData('showRegionInput', isset($columns[STATISTICS_DIMENSION_REGION]));
			$this->setData('showCityInput', isset($columns[STATISTICS_DIMENSION_CITY]));
		}

		$this->setData('showMonthInputs', isset($columns[STATISTICS_DIMENSION_MONTH]));
		$this->setData('showDayInputs', isset($columns[STATISTICS_DIMENSION_DAY]));

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
		$this->setData('objectTypesOptions', $this->_objects);
		if ($this->_fileTypes) {
			$this->setData('fileTypesOptions', $this->_fileTypes);
		}
		$this->setData('fileAssocTypes', array(ASSOC_TYPE_GALLEY, ASSOC_TYPE_ISSUE_GALLEY));
		$this->setData('orderColumnsOptions', $orderColumns);
		$this->setData('orderDirectionsOptions', array(
			STATISTICS_ORDER_ASC => __('manager.statistics.reports.orderDir.asc'),
			STATISTICS_ORDER_DESC => __('manager.statistics.reports.orderDir.desc')));

		$columnsOptions = $this->_columns;
		// Reports will always include this column.
		unset($columnsOptions[STATISTICS_METRIC]);
		$this->setData('columnsOptions', $columnsOptions);

		return parent::fetch($request);
	}

	/**
	 * Assign user-submitted data to form.
	 */
	function readInputData() {
		$this->readUserVars(array('columns', 'objectTypes', 'fileTypes', 'objectIds', 'issues',
			'articles', 'timeFilterOption', 'countries', 'regions', 'cityNames',
			'orderByColumn', 'orderByDirection'));
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

		// Get the time filter data, if any.
		$startTime = $request->getUserDateVar('dateStart', 1, 1, 1, 23, 59, 59);
		$endTime = $request->getUserDateVar('dateEnd', 1, 1, 1, 23, 59, 59);
		if ($startTime && $endTime) {
			$startYear = date('Y', $startTime);
			$endYear = date('Y', $endTime);
			$startMonth = date('m', $startTime);
			$endMonth = date('m', $endTime);
			$startDay = date('d', $startTime);
			$endDay = date('d', $endTime);
		}

		$timeFilterOption = $this->getData('timeFilterOption');
		switch($timeFilterOption) {
			case TIME_FILTER_OPTION_CURRENT_DAY:
				$filter[STATISTICS_DIMENSION_MONTH] = STATISTICS_CURRENT_DAY;
				break;
			case TIME_FILTER_OPTION_CURRENT_MONTH:
				$filter[STATISTICS_DIMENSION_MONTH] = STATISTICS_CURRENT_MONTH;
				break;
			case TIME_FILTER_OPTION_RANGE_DAY:
			case TIME_FILTER_OPTION_RANGE_MONTH:
				if ($timeFilterOption == TIME_FILTER_OPTION_RANGE_DAY) {
					$startDate = $startYear . $startMonth . $startDay;
					$endDate = $endYear . $endMonth . $endDay;
				} else {
					$startDate = $startYear . $startMonth;
					$endDate = $endYear . $endMonth;
				}

				if ($startTime == $endTime) {
					// The start and end date are the same, there is no range defined
					// only one specific date. Use the start time.
					$filter[STATISTICS_DIMENSION_MONTH] = $startDate;
				} else {
					$filter[STATISTICS_DIMENSION_MONTH]['from'] = $startDate;
					$filter[STATISTICS_DIMENSION_MONTH]['to'] = $endDate;
				}
				break;
			default:
				break;
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

		return StatisticsHelper::getReportUrl($request, $this->_metricType, $columns, $filter, $orderBy);
	}
}

?>
