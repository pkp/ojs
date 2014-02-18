<?php

/**
 * @file plugins/generic/timedView/TimedViewReportPlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TimedViewReportPlugin
 * @ingroup plugins_generic_timedView
 *
 * @brief Timed View report plugin
 */

define('TIMED_VIEW_REPORT_YEAR_OFFSET_PAST', '-20');
define('TIMED_VIEW_REPORT_YEAR_OFFSET_FUTURE', '+0');

import('classes.plugins.ReportPlugin');

class TimedViewReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);

		if($success) {
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
		return __('plugins.generic.timedView.displayName');
	}

	function getDescription() {
		return __('plugins.generic.timedView.description');
	}

	function display(&$args, $request) {
		parent::display($args);

		$form = new TimedViewReportForm($this);

		if ($request->getUserVar('generate')) {
			$form->readInputData();
			if ($form->validate()) {
				$form->execute();
			} else {
				$form->display($request);
			}
		} elseif ($request->getUserVar('clearLogs')) {
			$dateClear = (int) $request->getUserVar('dateClearYear') . '-' . (int) $request->getUserVar('dateClearMonth') . '-' . (int) $request->getUserVar('dateClearDay') . ' 00:00:00';
			$timedViewReportDao = DAORegistry::getDAO('TimedViewReportDAO');
			$journal = $request->getJournal();
			$timedViewReportDao->clearLogs($dateClear, $journal->getId());
			$form->display($request);
		} else {
			$form->initData();
			$form->display($request);
		}
	}
}

?>
