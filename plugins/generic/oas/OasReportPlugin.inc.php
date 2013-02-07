<?php

/**
 * @file plugins/generic/oas/OasReportPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OasReportPlugin
 * @ingroup plugins_generic_oas
 *
 * @brief OA-S report plugin (and metrics provider)
 */


import('classes.plugins.ReportPlugin');

class OasReportPlugin extends ReportPlugin {

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
		return 'OasReportPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.oas.report.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.reports.oas.report.description');
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
		assert(false); // TODO: implement
	}

	/**
	 * @see ReportPlugin::getMetricTypes()
	 */
	function getMetricTypes() {
		assert(false); // TODO: implement
	}

	/**
	 * @see ReportPlugin::getMetricDisplayType()
	 */
	function getMetricDisplayType($metricType) {
		assert(false); // TODO: implement
	}

	/**
	 * @see ReportPlugin::getMetricFullName()
	 */
	function getMetricFullName($metricType) {
		assert(false); // TODO: implement
	}
}

?>
