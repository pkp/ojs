<?php

/**
 * @defgroup plugins_reports_counter
 */

/**
 * @file plugins/reports/counter/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_counter
 * @brief Wrapper for counter report plugin.
 *
 */

// Because of the use of Namespaces, this plugin now requires PHP 5.3 or better
if (version_compare(PHP_VERSION, '5.3.0') >= 0) {

require_once(dirname(__FILE__) . '/CounterReportPlugin.inc.php');

return new CounterReportPlugin();

}


