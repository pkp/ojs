<?php

/**
* @file classes/statistics/PKPStatisticsHelper.inc.php
*
* Copyright (c) 2013-2017 Simon Fraser University
* Copyright (c) 2003-2017 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class PKPStatisticsHelper
* @ingroup statistics
*
* @brief Statistics helper class.
*
*/

// Dimensions:
// 1) publication object dimension:
define('STATISTICS_DIMENSION_CONTEXT_ID', 'context_id');
define('STATISTICS_DIMENSION_PKP_SECTION_ID', 'pkp_section_id');
define('STATISTICS_DIMENSION_ASSOC_OBJECT_TYPE', 'assoc_object_type');
define('STATISTICS_DIMENSION_ASSOC_OBJECT_ID', 'assoc_object_id');
define('STATISTICS_DIMENSION_SUBMISSION_ID', 'submission_id');
define('STATISTICS_DIMENSION_REPRESENTATION_ID', 'representation_id');
define('STATISTICS_DIMENSION_ASSOC_TYPE', 'assoc_type');
define('STATISTICS_DIMENSION_ASSOC_ID', 'assoc_id');
define('STATISTICS_DIMENSION_FILE_TYPE', 'file_type');
// 2) time dimension:
define('STATISTICS_DIMENSION_MONTH', 'month');
define('STATISTICS_DIMENSION_DAY', 'day');
// 3) geography dimension:
define('STATISTICS_DIMENSION_COUNTRY', 'country_id');
define('STATISTICS_DIMENSION_REGION', 'region');
define('STATISTICS_DIMENSION_CITY', 'city');
// 4) metric type dimension (non-additive!):
define('STATISTICS_DIMENSION_METRIC_TYPE', 'metric_type');

// Metrics:
define('STATISTICS_METRIC', 'metric');

// Odering:
define('STATISTICS_ORDER_ASC', 'ASC');
define('STATISTICS_ORDER_DESC', 'DESC');

// Global report size limit:
define('STATISTICS_MAX_ROWS', 5000);

// File type to be used in publication object dimension.
define('STATISTICS_FILE_TYPE_HTML', 1);
define('STATISTICS_FILE_TYPE_PDF', 2);
define('STATISTICS_FILE_TYPE_OTHER', 3);
define('STATISTICS_FILE_TYPE_DOC', 4);

// Geography.
define('STATISTICS_UNKNOWN_COUNTRY_ID', 'ZZ');

// Constants used to filter time dimension to current time.
define('STATISTICS_YESTERDAY', 'yesterday');
define('STATISTICS_CURRENT_MONTH', 'currentMonth');

abstract class PKPStatisticsHelper {

	function __construct() {
	}

	/**
	* Check whether the filter filters on a context
	* and if so: retrieve it.
	*
	* NB: We do not check filters below the context level as this would
	* be unnecessarily complex. We'd have to check whether the given
	* publication objects are actually from the same context. This again
	* would require us to retrieve all context objects for the filtered
	* objects, etc.
	*
	* @param $filter array
	* @return null|Context
	*/
	function &getContext($filter) {
		// Check whether the report is on context level.
		$context = null;
		if (isset($filter[STATISTICS_DIMENSION_CONTEXT_ID])) {
			$contextFilter = $filter[STATISTICS_DIMENSION_CONTEXT_ID];
			if (is_scalar($contextFilter)) {
				// Retrieve the context object.
				$contextDao = Application::getContextDAO(); /* @var $contextDao ContextDAO */
				$context = $contextDao->getById($contextFilter);
			}
		}
		return $context;
	}

	/**
	* Identify and canonicalize the filtered metric type.
	* @param $metricType string|array A wildcard can be used to
	* identify all metric types.
	* @param $context null|Context
	* @param $defaultSiteMetricType string
	* @param $siteMetricTypes array
	* @return null|array The canonicalized metric type array. Null if an error
	*  occurred.
	*/
	function canonicalizeMetricTypes($metricType, $context, $defaultSiteMetricType, $siteMetricTypes) {
		// Metric type is null: Return the default metric for
		// the filtered context.
		if (is_null($metricType)) {
			if (is_a($context, 'Context')) {
				$metricType = $context->getDefaultMetricType();
			} else {
				$metricType = $defaultSiteMetricType;
			}
		}

		// Canonicalize the metric type to an array of metric types.
		if (!is_null($metricType)) {
			if (is_scalar($metricType) && $metricType !== '*') {
				// Metric type is a scalar value: Select a single metric.
				$metricType = array($metricType);

			} elseif ($metricType === '*') {
				// Metric type is '*': Select all available metrics.
				if (is_a($context, 'Context')) {
					$metricType = $context->getMetricTypes();
				} else {
					$metricType = $siteMetricTypes;
				}

			} else {
				// Only arrays are otherwise supported as metric type
				// specification.
				if (!is_array($metricType)) $metricType = null;

				// Metric type is an array: Select multiple metrics. This is the
				// canonical format so no change is required.
			}
		}

		return $metricType;
	}

	/**
	 * Get the report plugin that implements
	 * the passed metric type.
	 * @param $metricType string
	 * @return mixed ReportPlugin or null
	 */
	function &getReportPluginByMetricType($metricType) {
		$returner = null;

		// Retrieve site-level report plugins.
		$reportPlugins = PluginRegistry::loadCategory('reports', true, CONTEXT_SITE);
		if (!is_array($reportPlugins) || empty($metricType)) {
			return $returner;
		}

		if (is_scalar($metricType)) {
			$metricType = array($metricType);
		}

		foreach ($reportPlugins as $reportPlugin) {
			/* @var $reportPlugin ReportPlugin */
			$pluginMetricTypes = $reportPlugin->getMetricTypes();
			$metricTypeMatches = array_intersect($pluginMetricTypes, $metricType);
			if (!empty($metricTypeMatches)) {
				$returner = $reportPlugin;
				break;
			}
		}

		return $returner;
	}

	/**
	 * Get metric type display strings implemented by all
	 * available report plugins.
	 * @return array Metric type as index and the display string
	 * as values.
	 */
	function getAllMetricTypeStrings() {
		$allMetricTypes = array();
		$reportPlugins = PluginRegistry::loadCategory('reports', true, CONTEXT_SITE);
		if (is_array($reportPlugins)) {
			foreach ($reportPlugins as $reportPlugin) {
				/* @var $reportPlugin ReportPlugin */
				$reportMetricTypes = $reportPlugin->getMetricTypes();
				foreach ($reportMetricTypes as $metricType) {
					$allMetricTypes[$metricType] = $reportPlugin->getMetricDisplayType($metricType);
				}
			}
		}

		return $allMetricTypes;
	}

	/**
	* Get report column name.
	* @param $column string (optional)
	* @return array|string|null
	*/
	function getColumnNames($column = null) {
		$columns = $this->getReportColumnsArray();

		if ($column) {
			if (isset($columns[$column])) {
				return $columns[$column];
			} else {
				return null;
			}
		} else {
			return $columns;
		}
	}

	/**
	* Get object type string.
	* @param $assocType mixed int or null (optional)
	* @return mixed string or array
	*/
	function getObjectTypeString($assocType = null) {
		$objectTypes = $this->getReportObjectTypesArray();

		if (is_null($assocType)) {
			return $objectTypes;
		} else {
			if (isset($objectTypes[$assocType])) {
				return $objectTypes[$assocType];
			} else {
				assert(false);
			}
		}
	}

	/**
	 * Get file type string.
	 * @param $fileType mixed int or null (optional)
	 * @return mixed string or array
	 */
	function getFileTypeString($fileType = null) {
		$fileTypeArray = $this->getFileTypesArray();

		if (is_null($fileType)) {
			return $fileTypeArray;
		} else {
			if (isset($fileTypeArray[$fileType])) {
				return $fileTypeArray[$fileType];
			} else {
				assert(false);
			}
		}
	}

	/**
	 * Get an url that requests a statiscs report,
	 * using the passed parameters as request arguments.
	 * @param $request PKPRequest
	 * @param $metricType string Report metric type.
	 * @param $columns array Report columns
	 * @param $filter array Report filters.
	 * @param $orderBy array (optional) Report order by values.
	 * @return string
	 */
	function getReportUrl($request, $metricType, $columns, $filter, $orderBy = array()) {
		$dispatcher = $request->getDispatcher(); /* @var $dispatcher Dispatcher */
		$args = array(
			'metricType' => $metricType,
			'columns' => $columns,
			'filters' => serialize($filter)
		);

		if (!empty($orderBy)) {
			$args['orderBy'] = serialize($orderBy);
		}

		return $dispatcher->url($request, ROUTE_PAGE, null, 'management', 'tools', 'generateReport', $args);
	}


	/**
	* Get the geo location tool.
	* @return mixed GeoLocationTool object or null
	*/
	function &getGeoLocationTool() {
		$geoLocationTool = null;
		$plugin = PluginRegistry::getPlugin('generic', 'usagestatsplugin'); /* @var $plugin UsageStatsPlugin */
		if (is_a($plugin, 'UsageStatsPlugin')) {
			$geoLocationTool = $plugin->getGeoLocationTool();
		}
		return $geoLocationTool;
	}


	//
	// Protected methods.
	//
	/**
	 * Get all statistics report columns, with their respective
	 * names as array values.
	 * @return array
	 */
	protected function getReportColumnsArray() {
		return array(
			STATISTICS_DIMENSION_ASSOC_ID => __('common.id'),
			STATISTICS_DIMENSION_ASSOC_TYPE => __('common.type'),
			STATISTICS_DIMENSION_FILE_TYPE => __('common.fileType'),
			STATISTICS_DIMENSION_SUBMISSION_ID => $this->getAppColumnTitle(STATISTICS_DIMENSION_SUBMISSION_ID),
			STATISTICS_DIMENSION_CONTEXT_ID => $this->getAppColumnTitle(STATISTICS_DIMENSION_CONTEXT_ID),
			STATISTICS_DIMENSION_PKP_SECTION_ID => $this->getAppColumnTitle(STATISTICS_DIMENSION_PKP_SECTION_ID),
			STATISTICS_DIMENSION_CITY => __('manager.statistics.city'),
			STATISTICS_DIMENSION_REGION => __('manager.statistics.region'),
			STATISTICS_DIMENSION_COUNTRY => __('common.country'),
			STATISTICS_DIMENSION_DAY => __('common.day'),
			STATISTICS_DIMENSION_MONTH => __('common.month'),
			STATISTICS_DIMENSION_METRIC_TYPE => __('common.metric'),
			STATISTICS_METRIC => __('common.count')
		);
	}

	/**
	 * Get all statistics report public objects, with their
	 * respective names as array values.
	 * @return array
	 */
	protected function getReportObjectTypesArray() {
		return array(
			ASSOC_TYPE_SUBMISSION_FILE => __('submission.submit.submissionFiles')
		);
	}

	/**
	 * Get all file types that have statistics, with
	 * their respective names as array values.
	 * @return array
	 */
	protected function getFileTypesArray() {
		return array(
			STATISTICS_FILE_TYPE_PDF => 'PDF',
			STATISTICS_FILE_TYPE_HTML => 'HTML',
			STATISTICS_FILE_TYPE_OTHER => __('common.other'),
			STATISTICS_FILE_TYPE_DOC => 'DOC',
		);
	}

	/**
	 * Get an application specific column name.
	 * @param $column string One of the statistics column constant.
	 * @return string A localized text.
	 */
	abstract protected function getAppColumnTitle($column);
}

?>
