<?php

/**
 * @file plugins/reports/counter/CounterReportPluginUnsupported.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterReportPlugin
 * @ingroup plugins_reports_counter
 *
 * @brief Counter report plugin
 */

import('classes.plugins.ReportPlugin');

class CounterReportPlugin extends ReportPlugin {

	/**
	 * @see PKPPlugin::register($category, $path)
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
	}

	/**
	 * @see PKPPlugin::getName()
	 */
	function getName() {
		return 'CounterReportPlugin';
	}

	/**
	 * @see PKPPlugin::getHideManagement()
	 */
	function getHideManagement() {
		return true;
	}

}

?>
