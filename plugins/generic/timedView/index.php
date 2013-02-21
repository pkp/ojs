<?php

/**
 * @defgroup plugins_reports_timedView
 */

/**
 * @file plugins/reports/timedView/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_timedView
 * @brief Wrapper for timedView report plugin.
 *
 */

require_once(dirname(__FILE__) . '/TimedViewPlugin.inc.php');

return new TimedViewPlugin();

?>
