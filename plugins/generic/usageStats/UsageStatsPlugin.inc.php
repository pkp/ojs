<?php

/**
 * @file plugins/generic/usageStats/UsageStatsPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsPlugin
 * @ingroup plugins_generic_usageStats
 *
 * @brief Provide usage statistics to data objects.
 */


import('lib.pkp.plugins.generic.usageStats.PKPUsageStatsPlugin');

class UsageStatsPlugin extends PKPUsageStatsPlugin {

	/**
	 * Constructor.
	 */
	function UsageStatsPlugin() {
		parent::PKPUsageStatsPlugin();
	}
}

?>
