<?php

/**
 * @file plugins/blocks/mostRead/MostReadBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MostReadBlockPlugin
 * @ingroup plugins_blocks_mostRead
 *
 * @brief Class for "most read articles" block plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class MostReadBlockPlugin extends BlockPlugin {

	//
	// Implement template methods from PKPPlugin.
	//
	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.block.mostRead.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.block.mostRead.description');
	}

	/**
	 * @see PKPPlugin::getContextSpecificPluginSettingsFile()
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	//
	// Implement template methods from BlockPlugin.
	//
	/**
	 * @see BlockPlugin::getContents()
	 */
	function getContents($templateMgr, $request) {
		// The plugin is only available on journal level.
		$journal = $request->getJournal();
		if (!is_a($journal, 'Journal')) {
			assert(false);
			return '';
		}

		// Specify the metrics report we need for this plugin.
		// See: http://pkp.sfu.ca/wiki/index.php/OJSdeStatisticsConcept#Input_and_Output_Formats_.28Aggregation.2C_Filters.2C_Metrics_Data.29
		$metricType = null; // Use the main metric.
		$columns = STATISTICS_DIMENSION_ASSOC_ID;
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_GALLEY);
		$orderBy = array(STATISTICS_METRIC => STATISTICS_ORDER_DESC);
		import('lib.pkp.classes.db.DBResultRange');
		$range = new DBResultRange(10); // Get the first 10 results only.

		$metricsReport = $journal->getMetrics($metricType, $columns, $filter, $orderBy, $range);

		// Retrieve metadata for the report.
		// TODO: continue here.

		return parent::getContents($templateMgr, $request);
	}
}

?>
