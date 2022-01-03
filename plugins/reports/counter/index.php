<?php

/**
 * @defgroup plugins_reports_counter
 */

/**
 * @file plugins/reports/counter/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_counter
 * @brief Wrapper for counter report plugin.
 *
 */

require_once dirname(__FILE__) . '/CounterReportPlugin.inc.php';

return new CounterReportPlugin();
