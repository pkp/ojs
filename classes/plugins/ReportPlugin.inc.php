<?php

/**
 * @file classes/plugins/ReportPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for report plugins
 */

import('classes.plugins.Plugin');

class ReportPlugin extends Plugin {

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
	 * Get the columns used in reports by the passed
	 * metric type.
	 * @param $metricType string One of the values returned from getMetricTypes()
	 * @return null|array Return an array with STATISTICS_DIMENSION_...
	 * constants.
	 */
	function getColumns($metricType) {
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
		return array();
	}

	/**
	 * Get the default report templates that each report
	 * plugin can implement, with an string to represent it.
	 * Subclasses can override this method to add/remove
	 * default formats.
	 * @param $metricType string
	 * @return array
	 */
	function getDefaultReportTemplates($metricType) {
		$reports = array();

		// Define aggregation columns, the ones that
		// can be part of the reports not changing
		// it's main purpose.
		$aggregationColumns = array(STATISTICS_DIMENSION_COUNTRY,
			STATISTICS_DIMENSION_REGION,
			STATISTICS_DIMENSION_CITY,
			STATISTICS_DIMENSION_MONTH,
			STATISTICS_DIMENSION_DAY);

		// Articles file downloads.
		$columns = array(STATISTICS_DIMENSION_ASSOC_TYPE,
			STATISTICS_DIMENSION_ISSUE_ID,
			STATISTICS_DIMENSION_SUBMISSION_ID,
			STATISTICS_DIMENSION_MONTH,
			STATISTICS_DIMENSION_COUNTRY);
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_GALLEY);
		$reports[] = array(	'nameLocaleKey' => 'manager.statistics.reports.defaultReport.articleDownloads',
			'metricType' => $metricType, 'columns' => $columns, 'filter' => $filter,
			'aggregationColumns' => $aggregationColumns);

		// Articles abstract views.
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_ARTICLE);
		$reports[] = array('nameLocaleKey' => 'manager.statistics.reports.defaultReport.articleAbstract',
			'metricType' => $metricType, 'columns' => $columns, 'filter' => $filter,
			'aggregationColumns' => $aggregationColumns);

		// Issues file downloads.
		$columns = array(STATISTICS_DIMENSION_ASSOC_TYPE,
			STATISTICS_DIMENSION_ISSUE_ID,
			STATISTICS_DIMENSION_MONTH,
			STATISTICS_DIMENSION_COUNTRY);
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_ISSUE_GALLEY);
		$reports[] = array('nameLocaleKey' => 'manager.statistics.reports.defaultReport.issueDownloads',
			'metricType' => $metricType, 'columns' => $columns, 'filter' => $filter,
			'aggregationColumns' => $aggregationColumns);

		// Issue table of contents page views.
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_ISSUE);
		$reports[] = array('nameLocaleKey' => 'manager.statistics.reports.defaultReport.issueTableOfContents',
			'metricType' => $metricType, 'columns' => $columns, 'filter' => $filter,
			'aggregationColumns' => $aggregationColumns);

		// Journal index page views.
		$columns = array(STATISTICS_DIMENSION_ASSOC_TYPE,
			STATISTICS_DIMENSION_CONTEXT_ID,
			STATISTICS_DIMENSION_MONTH,
			STATISTICS_DIMENSION_COUNTRY);
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_JOURNAL);
		$reports[] = array('nameLocaleKey' => 'manager.statistics.reports.defaultReport.journalIndexPageViews',
			'metricType' => $metricType, 'columns' => $columns, 'filter' => $filter,
			'aggregationColumns' => $aggregationColumns);

		return $reports;
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $crumbs Array ($url, $name, $isTranslated)
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($crumbs = array(), $isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			),
			array (
				Request::url(null, 'manager', 'reports'),
				'manager.statistics.reports'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'reports', array('plugin', $this->getName())),
			$this->getDisplayName(),
			true
		);

		$templateMgr->assign('pageHierarchy', array_merge($pageCrumbs, $crumbs));
	}

	/**
	 * Display the import/export plugin UI.
	 * @param $args Array The array of arguments the user supplied.
	 */
	function display(&$args) {
		$templateManager =& TemplateManager::getManager();
		$templateManager->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
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
	 * Perform management functions
	 */
	function manage($verb, $args) {
		if ($verb === 'reports') {
			Request::redirect(null, 'manager', 'report', $this->getName());
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
