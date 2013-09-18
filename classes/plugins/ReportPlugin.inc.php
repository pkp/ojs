<?php

/**
 * @file classes/plugins/ReportPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for report plugins
 */

import('lib.pkp.classes.plugins.Plugin');

class ReportPlugin extends Plugin {

	/**
	 * Constructor
	 */
	function ReportPlugin() {
		parent::Plugin();
	}

	/**
	 * Retrieve a range of aggregate, filtered, ordered metric values, i.e.
	 * a statistics report.
	 *
	 * @see <http://pkp.sfu.ca/wiki/index.php/OJSdeStatisticsConcept#Input_and_Output_Formats_.28Aggregation.2C_Filters.2C_Metrics_Data.29>
	 * for a full specification of the input and output format of this method.
	 *
	 * @param $metricType null|string|array metrics selection
	 * @param $columns string|array column (aggregation level) selection
	 * @param $filters array report-level filter selection
	 * @param $orderBy array order criteria
	 * @param $range null|DBResultRange paging specification
	 *
	 * @return null|array The selected data as a simple tabular result set or
	 *  null if metrics are not supported by this plug-in, the specified report
	 *  is invalid or cannot be produced or another error occurred.
	 */
	function getMetrics($metricType = null, $columns = array(), $filters = array(), $orderBy = array(), $range = null) {
		return null;
	}

	/**
	 * Metric types available from this plug-in.
	 *
	 * @return array An array of metric identifiers (strings) supported by
	 *   this plugin.
	 */
	function getMetricTypes() {
		return array();
	}

	/**
	 * Public metric type that will be displayed to end users.
	 * @param $metricType string One of the values returned from getMetricTypes()
	 * @return null|string The metric type or null if the plug-in does not support
	 *  standard metric retrieval or the metric type was not found.
	 */
	function getMetricDisplayType($metricType) {
		return null;
	}

	/**
	 * Full name of the metric type.
	 * @param $metricType string One of the values returned from getMetricTypes()
	 * @return null|string The full name of the metric type or null if the
	 *  plug-in does not support standard metric retrieval or the metric type
	 *  was not found.
	 */
	function getMetricFullName($metricType) {
		return null;
	}

	/**
	 * Display the import/export plugin UI.
	 * @param $args Array The array of arguments the user supplied.
	 */
	function display(&$args) {
		$templateManager = TemplateManager::getManager($this->getRequest());
		$templateManager->register_function('plugin_url', array($this, 'smartyPluginUrl'));
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		return array(
			array(
				'reports',
				__('manager.statistics.reports')
			)
		);
	}

 	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if ($verb === 'reports') {
			$request =& $this->getRequest();
			$request->redirect(null, 'manager', 'report', $this->getName());
		}
		return false;
	}

	/**
	 * Extend the {url ...} smarty to support reporting plugins.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array('plugin', $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}
		return $smarty->smartyUrl($params, $smarty);
	}
}

?>
