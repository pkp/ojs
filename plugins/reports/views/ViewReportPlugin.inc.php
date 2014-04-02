<?php

/**
 * @file plugins/reports/views/ViewReportPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ViewReportPlugin
 * @ingroup plugins_reports_views
 *
 * @brief View report plugin
 */


import('classes.plugins.ReportPlugin');

define('OJS_METRIC_TYPE_LEGACY_DEFAULT', 'ojs::legacyDefault');

class ViewReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ViewReportPlugin';
	}

	function getDisplayName() {
		return __('plugins.reports.views.displayName');
	}

	function getDescription() {
		return __('plugins.reports.views.description');
	}

	function display(&$args) {
		$journal =& Request::getJournal();

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$metricsDao =& DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */

		$columns = array(
		__('plugins.reports.views.articleId'),
		__('plugins.reports.views.articleTitle'),
		__('issue.issue'),
		__('plugins.reports.views.datePublished'),
		__('plugins.reports.views.abstractViews'),
		__('plugins.reports.views.galleyViews'),
		);
		$galleyLabels = array();
		$galleyViews = array();
		$galleyViewTotals = array();
		$abstractViewCounts = array();
		$issueIdentifications = array();
		$issueDatesPublished = array();
		$articleTitles = array();
		$articleIssueIdentificationMap = array();
		$firstTime = true;
		$result = array();

		import('classes.db.DBResultRange');
		$dbResultRange = new DBResultRange(STATISTICS_MAX_ROWS);
		$page = 3;

		$request =& Application::getRequest();
		if ($request->getUserVar('metricType') === OJS_METRIC_TYPE_COUNTER) {
			$metricType = OJS_METRIC_TYPE_COUNTER;
		} else {
			$metricType = OJS_METRIC_TYPE_LEGACY_DEFAULT;
		}

		while (true) {
			$dbResultRange->setPage($page);
			$result = $metricsDao->getMetrics($metricType,
				array(STATISTICS_DIMENSION_ASSOC_ID),
				array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_ARTICLE, STATISTICS_DIMENSION_CONTEXT_ID => $journal->getId()),
				array(),
				$dbResultRange);
			$page++;

			foreach ($result as $record) {
				$articleId = $record[STATISTICS_DIMENSION_ASSOC_ID];
				$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId, null, true);
				if (!is_a($publishedArticle, 'PublishedArticle')) {
					continue;
				}
				$issueId = $publishedArticle->getIssueId();
				if (!$issueId) {
					continue;
				}
				$articleTitles[$articleId] = $publishedArticle->getArticleTitle();

				// Store the abstract view count, making
				// sure both metric types will be counted.
				if (isset($abstractViewCounts[$articleId])) {
					$abstractViewCounts[$articleId] += $record[STATISTICS_METRIC];
				} else {
					$abstractViewCounts[$articleId] = $record[STATISTICS_METRIC];
				}
				// Make sure we get the issue identification
				$articleIssueIdentificationMap[$articleId] = $issueId;
				if (!isset($issueIdentifications[$issueId])) {
					$issue =& $issueDao->getIssueById($issueId);
					$issueIdentifications[$issueId] = $issue->getIssueIdentification();
					$issueDatesPublished[$issueId] = $issue->getDatePublished();
					unset($issue);
				}

				// For each galley, store the label and the count
				$galleysResult =& $metricsDao->getMetrics($metricType,
					array(STATISTICS_DIMENSION_ASSOC_ID),
					array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_GALLEY, STATISTICS_DIMENSION_SUBMISSION_ID => $articleId));
				$galleyViews[$articleId] = array();
				$galleyViewTotals[$articleId] = 0;
				foreach ($galleysResult as $galleyRecord) {
					$galleyId = $galleyRecord[STATISTICS_DIMENSION_ASSOC_ID];
					$galley =& $galleyDao->getGalley($galleyId);
					$label = $galley->getGalleyLabel();
					$i = array_search($label, $galleyLabels);
					if ($i === false) {
						$i = count($galleyLabels);
						$galleyLabels[] = $label;
					}

					// Make sure the array is the same size as in previous iterations
					//  so that we insert values into the right location
					if (count($galleyViews[$articleId]) !== count($galleyLabels)) {
						$galleyViews[$articleId] = array_pad($galleyViews[$articleId], count($galleyLabels), '');
					}

					$views = $galleyRecord[STATISTICS_METRIC];
					// Make sure both metric types will be counted.
					$galleyViews[$articleId][$i] += $views;

					$galleyViewTotals[$articleId] += $views;
				}

				// Clean up
				unset($publishedArticle, $galleys);
			}

			$firstTime = false;
			if (count($result) < STATISTICS_MAX_ROWS) break;
		}

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=views-' . date('Ymd') . '.csv');
		$fp = fopen('php://output', 'wt');
		fputcsv($fp, array_merge($columns, $galleyLabels));

		ksort($abstractViewCounts);
		$dateFormatShort = Config::getVar('general', 'date_format_short');
		foreach ($abstractViewCounts as $articleId => $abstractViewCount) {
			$values = array(
				$articleId,
				$articleTitles[$articleId],
				$issueIdentifications[$articleIssueIdentificationMap[$articleId]],
				strftime($dateFormatShort, strtotime($issueDatesPublished[$articleIssueIdentificationMap[$articleId]])),
				$abstractViewCount,
				$galleyViewTotals[$articleId]
			);

			fputcsv($fp, array_merge($values, $galleyViews[$articleId]));
		}

		fclose($fp);
	}
}

?>
