<?php

/**
 * @file plugins/generic/usageStats/UsageStatsReportPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsReportPlugin
 * @ingroup plugins_generic_usageStats
 *
 * @brief OJS default statistics report plugin (and metrics provider)
 */


import('classes.plugins.ReportPlugin');

define('OJS_METRIC_TYPE_COUNTER', 'ojs::counter');

class UsageStatsReportPlugin extends ReportPlugin {

	/**
	 * @see PKPPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * @see PKPPlugin::getName()
	 */
	function getName() {
		return 'UsageStatsReportPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.reports.usageStats.report.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.reports.usageStats.report.description');
	}

	/**
	 * @see ReportPlugin::display()
	 */
	function display(&$args) {
		parent::display($args);
		$journal =& Request::getJournal();

		$reportArgs = array(
			'metricType' => OJS_METRIC_TYPE_COUNTER,
			'columns' => array(
				STATISTICS_DIMENSION_ASSOC_ID, STATISTICS_DIMENSION_ASSOC_TYPE, STATISTICS_DIMENSION_CONTEXT_ID,
				STATISTICS_DIMENSION_ISSUE_ID, STATISTICS_DIMENSION_MONTH, STATISTICS_DIMENSION_COUNTRY),
			'filters' => serialize(array(STATISTICS_DIMENSION_CONTEXT_ID => $journal->getId())),
			'orderBy' => serialize(array(STATISTICS_DIMENSION_MONTH => STATISTICS_ORDER_ASC))
		);
		Request::redirect(null, null, 'generateReport', null, $reportArgs);
	}

	/**
	 * @see ReportPlugin::getMetrics()
	 */
	function getMetrics($metricType = null, $columns = null, $filters = null, $orderBy = null, $range = null) {
		// Validate the metric type.
		if (!(is_scalar($metricType) || count($metricType) === 1)) return null;
		if (is_array($metricType)) $metricType = array_pop($metricType);
		if ($metricType !== OJS_METRIC_TYPE_COUNTER) return null;

		// This plug-in uses the MetricsDAO to store metrics. So we simply
		// delegate there.
		$metricsDao =& DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		return $metricsDao->getMetrics($metricType, $columns, $filters, $orderBy, $range);
	}

	/**
	 * @see ReportPlugin::getMetricTypes()
	 */
	function getMetricTypes() {
		return array(OJS_METRIC_TYPE_COUNTER);
	}

	/**
	 * @see ReportPlugin::getMetricDisplayType()
	 */
	function getMetricDisplayType($metricType) {
		if ($metricType !== OJS_METRIC_TYPE_COUNTER) return null;
		return __('plugins.reports.usageStats.metricType');
	}

	/**
	 * @see ReportPlugin::getMetricFullName()
	 */
	function getMetricFullName($metricType) {
		if ($metricType !== OAS_METRIC_TYPE_COUNTER) return null;
		return __('plugins.reports.usageStats.metricType.full');
	}

	/**
	 * @see ReportPlugin::getColumns()
	 */
	function getColumns($metricType) {
		if ($metricType !== OJS_METRIC_TYPE_COUNTER) return array();
		return array(
			STATISTICS_DIMENSION_ASSOC_ID,
			STATISTICS_DIMENSION_ASSOC_TYPE,
			STATISTICS_DIMENSION_SUBMISSION_ID,
			STATISTICS_DIMENSION_ISSUE_ID,
			STATISTICS_DIMENSION_CONTEXT_ID,
			STATISTICS_DIMENSION_CITY,
			STATISTICS_DIMENSION_REGION,
			STATISTICS_DIMENSION_COUNTRY,
			STATISTICS_DIMENSION_DAY,
			STATISTICS_DIMENSION_MONTH,
			STATISTICS_DIMENSION_FILE_TYPE,
			STATISTICS_METRIC
		);
	}

	/**
	 * @see ReportPlugin::getObjectTypes()
	 */
	function getObjectTypes($metricType) {
		if ($metricType !== OJS_METRIC_TYPE_COUNTER) return array();
		return array(
			ASSOC_TYPE_JOURNAL,
			ASSOC_TYPE_ISSUE,
			ASSOC_TYPE_ISSUE_GALLEY,
			ASSOC_TYPE_ARTICLE,
			ASSOC_TYPE_GALLEY
		);
	}
}

?>
