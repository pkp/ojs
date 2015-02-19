<?php

/**
 * @defgroup plugins_reports_counter
 */

/**
 * @file plugins/reports/counter/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_counter
 * @brief Wrapper for counter report plugin.
 *
 */

require_once(dirname(__FILE__) . '/CounterReportPlugin.inc.php');

return new CounterReportPlugin();

?>
