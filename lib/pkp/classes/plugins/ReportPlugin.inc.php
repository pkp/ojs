<?php

/**
 * @file classes/plugins/ReportPlugin.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for report plugins
 */

import('lib.pkp.classes.plugins.Plugin');

abstract class ReportPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Public methods to be implemented by subclasses.
	//
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
	 * Get the columns used in reports by the passed
	 * metric type.
	 * @param $metricType string One of the values returned from getMetricTypes()
	 * @return null|array Return an array with STATISTICS_DIMENSION_...
	 * constants.
	 */
	function getColumns($metricType) {
		return null;
	}

	/**
	 * Get optional columns that are not required for this report
	 * to implement the passed metric type.
	 * @param $metricType string One of the values returned from getMetricTypes()
	 * @return array Return an array with STATISTICS_DIMENSION_...
	 * constants.
	 */
	function getOptionalColumns($metricType) {
		return array();
	}

	/**
	 * Get the object types that the passed metric type
	 * counts statistics for.
	 * @param $metricType string One of the values returned from getMetricTypes()
	 * @return null|array Return an array with ASSOC_TYPE_...
	 * constants.
	 */
	function getObjectTypes($metricType) {
		return null;
	}

	/**
	 * Get the default report templates that each report
	 * plugin can implement, with an string to represent it.
	 * Subclasses can override this method to add/remove
	 * default formats.
	 * @param $metricTypes string|array|null Define one or more metric types
	 * if you don't want to use all the implemented report metric types.
	 * @return array
	 */
	function getDefaultReportTemplates($metricTypes = null) {
		return array();
	}


	//
	// Public methods.
	//
	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new RedirectAction($dispatcher->url(
						$request, ROUTE_PAGE,
						null, 'manager', 'reports', array('plugin' => $this->getName())
					)),
					__('manager.statistics.reports'),
					null
				)
			):array(),
			parent::getActions($request, $actionArgs)
		);
	}
}

?>
