<?php

/**
 * @file plugins/generic/usageStats/UsageStatsPlugin.inc.php
 *
 * Copyright (c) 2013-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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

	/**
	 * Get the template path where the statistics should be displayed.
	 * @return string
	 */
	function getStatisticsDisplayTemplate() {
		return 'frontend/pages/article.tpl';
	}

	/**
	 * Get the hook that should be used for the statistics display.
	 * @return string
	 */
	function getStatisticsDisplayTemplateHook() {
		return 'Templates::Article::Main';
	}

	/**
	 * Get the publication object ID (from the template)
	 * the statistics should be displayed for.
	 * @param $smarty TemplateManager
	 * @return integer
	 */
	function getPubObjectId($smarty) {
		$pubObject =& $smarty->get_template_vars('article');
		assert(is_a($pubObject, 'PublishedArticle'));
		$pubObjectId = $pubObject->getId();
		return $pubObjectId;
	}
}

?>
