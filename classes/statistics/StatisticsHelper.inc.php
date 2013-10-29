<?php

/**
* @file classes/statistics/StatisticsHelper.inc.php
*
* Copyright (c) 2003-2013 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class StatisticsHelper
* @ingroup statistics
* @see StatisticsHelper
*
* @brief Statistics helper class.
*
*/

// Dimensions:
// 1) publication object dimension:
define('STATISTICS_DIMENSION_CONTEXT_ID', 'context_id');
define('STATISTICS_DIMENSION_ISSUE_ID', 'issue_id');
define('STATISTICS_DIMENSION_SUBMISSION_ID', 'submission_id');
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

// Geography.
define('STATISTICS_UNKNOWN_COUNTRY_ID', 'ZZ');

// Constants used to filter time dimension to current time.
define('STATISTICS_CURRENT_DAY', 'currentDay');
define('STATISTICS_CURRENT_MONTH', 'currentMonth');

class StatisticsHelper {

	//
	// Static methods.
	//
	/**
	* Check whether the filter filters on a journal
	* and if so: retrieve it.
	*
	* NB: We do not check filters below the journal level as this would
	* be unnecessarily complex. We'd have to check whether the given
	* publication objects are actually from the same journal. This again
	* would require us to retrieve all journal objects for the filtered
	* objects, etc.
	*
	* @param $filter array
	* @return null|Journal
	*/
	function &getContext($filter) {
		// Check whether the report is on journal level.
		$journal = null;
		if (isset($filter[STATISTICS_DIMENSION_CONTEXT_ID])) {
			$journalFilter = $filter[STATISTICS_DIMENSION_CONTEXT_ID];
			if (is_scalar($journalFilter)) {
				// Retrieve the journal.
				$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
				$journal =& $journalDao->getById($journalFilter);
			}
		}
		return $journal;
	}

	/**
	* Identify and canonicalize the filtered metric type.
	* @param $metricType string|array A wildcard can be used to
	* identify all metric types.
	* @param $journal null|Journal
	* @param $defaultSiteMetricType string
	* @param $siteMetricTypes array
	* @return null|array The canonicalized metric type array. Null if an error
	*  occurred.
	*/
	function canonicalizeMetricTypes($metricType, &$journal, $defaultSiteMetricType, $siteMetricTypes) {
		// Metric type is null: Return the default metric for
		// the filtered context.
		if (is_null($metricType)) {
			if (is_a($journal, 'Journal')) {
				$metricType = $journal->getDefaultMetricType();
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
				if (is_a($journal, 'Journal')) {
					$metricType = $journal->getMetricTypes();
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
	* Get report column names.
	* @return array
	*/
	function getColumnNames() {
		return array(
			STATISTICS_DIMENSION_ASSOC_ID => __('common.id'),
			STATISTICS_DIMENSION_ASSOC_TYPE => __('common.type'),
			STATISTICS_DIMENSION_SUBMISSION_ID => __('article.article'),
			STATISTICS_DIMENSION_ISSUE_ID => __('issue.issue'),
			STATISTICS_DIMENSION_CONTEXT_ID => __('common.journal'),
			STATISTICS_DIMENSION_CITY => __('manager.statistics.city'),
			STATISTICS_DIMENSION_REGION => __('manager.statistics.region'),
			STATISTICS_DIMENSION_COUNTRY => __('common.country'),
			STATISTICS_DIMENSION_DAY => __('common.day'),
			STATISTICS_DIMENSION_MONTH => __('common.month'),
			STATISTICS_DIMENSION_FILE_TYPE => __('common.fileType'),
			STATISTICS_DIMENSION_METRIC_TYPE => __('common.metric'),
			STATISTICS_METRIC => __('submission.views')
		);
	}

	/**
	* Get object type string.
	* @param $assocType mixed int or null (optional)
	* @return mixed string or array
	*/
	function getObjectTypeString($assocType = null) {
		$objectTypeStrings = array(
			ASSOC_TYPE_JOURNAL => __('journal.journal'),
			ASSOC_TYPE_ISSUE => __('issue.issue'),
			ASSOC_TYPE_ISSUE_GALLEY => __('editor.issues.galley'),
			ASSOC_TYPE_ARTICLE => __('article.article'),
			ASSOC_TYPE_GALLEY => __('submission.galley')
		);

		if (is_null($assocType)) {
			return $objectTypeStrings;
		} else {
			if (isset($objectTypeStrings[$assocType])) {
				return $objectTypeStrings[$assocType];
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
		$fileTypeStrings = array(
			STATISTICS_FILE_TYPE_PDF => 'PDF',
			STATISTICS_FILE_TYPE_HTML => 'HTML',
			STATISTICS_FILE_TYPE_OTHER => __('common.other')
		);

		if (is_null($fileType)) {
			return $fileTypeStrings;
		} else {
			if (isset($fileTypeStrings[$fileType])) {
				return $fileTypeStrings[$fileType];
			} else {
				assert(false);
			}
		}
	}

	/**
	* Get the geo location tool.
	* @return GeoLocationTool
	*/
	function &getGeoLocationTool() {
		$geoLocationTool = null;
		$plugin =& PluginRegistry::getPlugin('generic', 'usagestatsplugin'); /* @var $plugin UsageStatsPlugin */
		if (is_a($plugin, 'UsageStatsPlugin')) {
			$geoLocationTool =& $plugin->getGeoLocationTool();
		}
		return $geoLocationTool;
	}
}

?>