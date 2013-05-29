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
	function getContents(&$templateMgr, $request) {
		// The plugin is only available on journal level.
		$journal = $request->getJournal();
		if (!is_a($journal, 'Journal')) {
			assert(false);
			return '';
		}

		// Specify the metrics report we need for this plugin.
		// See: http://pkp.sfu.ca/wiki/index.php/OJSdeStatisticsConcept#Input_and_Output_Formats_.28Aggregation.2C_Filters.2C_Metrics_Data.29
		$metricType = null; // Use the main metric.
		$columns = STATISTICS_DIMENSION_ARTICLE_ID;
		$filter = array(
			STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_GALLEY
		);
		$orderBy = array(STATISTICS_METRIC => STATISTICS_ORDER_DESC);
		import('lib.pkp.classes.db.DBResultRange');
		$range = new DBResultRange(10); // Get the first 10 results only.

		// Reports will be generated for different time spans.
		$today = date('Ymd');
		$oneMonthAgo = date('Ymd', strtotime('-1 month'));
		$oneYearAgo = date('Ymd', strtotime('-1 year'));
		$timeSpans = array(
			'month' => array('from' => $oneMonthAgo, 'to' => $today),
			'year' => array('from' => $oneYearAgo, 'to' => $today),
			'ever' => null
		);

		// Generate reports.
		$articleRanking = array();
		$router = $request->getRouter();
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		foreach ($timeSpans as $timeSpanName => $timeSpan) {
			if (is_null($timeSpan)) {
				unset($filter[STATISTICS_DIMENSION_DAY]);
			} else {
				$filter[STATISTICS_DIMENSION_DAY] = $timeSpan;
			}
			$articleRanking[$timeSpanName] = $journal->getMetrics($metricType, $columns, $filter, $orderBy, $range);

			// Add article meta-data to the results.
			foreach ($articleRanking[$timeSpanName] as $articleIndex => &$articleInfo) {
				$articleInfo['rank'] = $articleIndex + 1;
				$articleId = $articleInfo['submission_id'];
				$article = $articleDao->getById($articleId, $journal->getId(), true); /* @var $article Article */
				if (!is_a($article, 'Article')) continue;
				$articleInfo['title'] = $article->getLocalizedTitle();
				$articleInfo['url'] = $router->url(
					$request, null, 'article', 'view', array($article->getBestArticleId($journal))
				);
			}
		}
		$templateMgr->assign('articleRanking', $articleRanking);

		// Add time span selection.
		$timeSpans = array(
			'month' => 'plugins.block.mostRead.previousMonth',
			'year' => 'plugins.block.mostRead.previousYear',
			'ever' => 'plugins.block.mostRead.allTimes'
		);
		$templateMgr->assign('timeSpans', $timeSpans);
		$templateMgr->assign('defaultTimeSpan', 'month');

		return parent::getContents($templateMgr, $request);
	}
}

?>
