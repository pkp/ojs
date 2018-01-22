<?php

/**
 * @defgroup plugins_generic_usageStats Usage Statistics Plugin
 */

/**
 * @file plugins/generic/usageStats/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_usageStats
 * @brief Wrapper for usage statistics plugin.
 *
 */
require_once('UsageStatsPlugin.inc.php');

return new UsageStatsPlugin();

?>
