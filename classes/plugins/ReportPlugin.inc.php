<?php

/**
 * @file classes/plugins/ReportPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for report plugins
 */

import('classes.plugins.Plugin');

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
	 * @param $metricType null|integer|array metrics selection
	 * @param $columns null|integer|array column (aggregation level) selection
	 * @param $filters null|array report-level filter selection
	 * @param $orderBy null|array order criteria
	 * @param $range null|DBResultRange paging specification
	 *
	 * @return null|DAOResultFactory The selected data as a simple tabular
	 *  result set or null if metrics are not supported by this plug-in,
	 *  the specified report is invalid or cannot be produced or another
	 *  error occurred.
	 */
	function getMetrics($metricType = null, $columns = null, $filters = null, $orderBy = null, $range = null) {
		return null;
	}

	/**
	 * Metric types available from this plug-in.
	 *
	 * @return null|array An array of metric identifiers (strings) or null if
	 *  the plug-in does not support standard metric retrieval.
	 */
	function getMetricTypes() {
		return null;
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
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $crumbs Array ($url, $name, $isTranslated)
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($crumbs = array(), $isSubclass = false) {
		$request =& $this->getRequest();
		$templateMgr =& TemplateManager::getManager($request);
		$pageCrumbs = array(
			array(
				$request->url(null, 'user'),
				'navigation.user'
			),
			array(
				$request->url(null, 'manager'),
				'user.role.manager'
			),
			array (
				$request->url(null, 'manager', 'reports'),
				'manager.statistics.reports'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			$request->url(null, 'manager', 'reports', array('plugin', $this->getName())),
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
		$templateManager =& TemplateManager::getManager($this->getRequest());
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
