<?php

/**
 * @file plugins/generic/usageStats/UsageStatsReportPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsReportPlugin
 * @ingroup plugins_generic_usageStats
 *
 * @brief OJS default statistics report plugin (and metrics provider)
 */


import('classes.plugins.ReportPlugin');

define('OJS_METRIC_TYPE_COUNTER', 'ojs::counter');

// TODO: #8015 Move to their own report plugins when created.
define('OJS_METRIC_TYPE_LEGACY_COUNTER', 'ojs::legacyCounterPlugin');
define('OJS_METRIC_TYPE_TIMED_VIEWS', 'ojs::timedViews');
define('OJS_METRIC_TYPE_LEGACY_DEFAULT', 'ojs::legacyDefault');

class UsageStatsReportPlugin extends ReportPlugin {

	/**
	 * @see Plugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * @see Plugin::getName()
	 */
	function getName() {
		return 'UsageStatsReportPlugin';
	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.reports.usageStats.report.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.reports.usageStats.report.description');
	}

	/**
	 * @see ReportPlugin::display()
	 */
	function display(&$args) {
		return parent::display($args);
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
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
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
}

?>
