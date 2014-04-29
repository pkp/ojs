<?php

/**
 * @file plugins/reports/timedView/TimedViewReportPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TimedViewReportPlugin
 * @ingroup plugins_reports_timedView
 *
 * @brief Timed View report plugin
 */

define('TIMED_VIEW_REPORT_YEAR_OFFSET_PAST', '-20');
define('TIMED_VIEW_REPORT_YEAR_OFFSET_FUTURE', '+0');
define('OJS_METRIC_TYPE_TIMED_VIEWS', 'ojs::timedViews');

import('classes.plugins.ReportPlugin');

class TimedViewReportPlugin extends ReportPlugin {
	/**
	 * Constructor
	 * @param $parentPluginName Name of parent plugin
	 */
	function TimedViewReportPlugin() {
		parent::ReportPlugin();
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);

		if($success) {
			$this->import('TimedViewReportForm');
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'TimedViewReportPlugin';
	}

	function getDisplayName() {
		return __('plugins.reports.timedView.displayName');
	}

	function getDescription() {
		return __('plugins.reports.timedView.description');
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs() {
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
			array(
				Request::url(null, 'manager', 'statistics'),
				'manager.statistics'
			)
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	function display(&$args, $request) {
		parent::display($args);
		$this->setBreadcrumbs();

		$form = new TimedViewReportForm($this);

		if ($request->getUserVar('generate')) {
			$form->readInputData();
			if ($form->validate()) {
				$form->execute();
			} else {
				$form->display();
			}
		} elseif ($request->getUserVar('clearLogs')) {
			$dateClear = date('Ymd', mktime(0, 0, 0, $request->getUserVar('dateClearMonth'), $request->getUserVar('dateClearDay'), $request->getUserVar('dateClearYear')));
			$journal =& $request->getJournal();
			$metricsDao =& DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
			$metricsDao->purgeRecords(OJS_METRIC_TYPE_TIMED_VIEWS, $dateClear);
			$form->display();
		} else {
			$form->initData();
			$form->display();
		}
	}
}

?>
