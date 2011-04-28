<?php

/**
 * @file ViewReportPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ViewReportPlugin
 * @ingroup plugins_reports_views
 *
 * @brief View report plugin
 */

// $Id: ViewReportPlugin.inc.php,v 1.3.2.2 2008/10/28 22:23:05 mcrider Exp $


import('classes.plugins.ReportPlugin');

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
		return Locale::translate('plugins.reports.views.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.reports.views.description');
	}

	function display(&$args) {
		$journal =& Request::getJournal();

		// FIXME: Localize this.
		$columns = array("Article ID", "Article Title", "Issue", "Date Published", "Abstract Views", "Total Galley Views");
		$galleyLabels = array();
		$galleyViews = array();
		$galleyViewTotals = array();
		$abstractViewCounts = array();
		$issueIdentifications = array();
		$issueDatesPublished = array();
		$articleTitles = array();
		$articleIssueIdentificationMap = array();

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');

		$publishedArticles =& $publishedArticleDao->getPublishedArticlesByJournalId($journal->getJournalId());
		while ($publishedArticle =& $publishedArticles->next()) {
			$articleId = $publishedArticle->getArticleId();
			$issueId = $publishedArticle->getIssueId();
			$articleTitles[$articleId] = $publishedArticle->getArticleTitle();

			// Store the abstract view count
			$abstractViewCounts[$articleId] = $publishedArticle->getViews();
			// Make sure we get the issue identification
			$articleIssueIdentificationMap[$articleId] = $issueId;
			if (!isset($issueIdentifications[$issueId])) {
				$issue =& $issueDao->getIssueById($issueId);
				$issueIdentifications[$issueId] = $issue->getIssueIdentification();
				$issueDatesPublished[$issueId] = $issue->getDatePublished();
				unset($issue);
			}

			// For each galley, store the label and the count
			$galleys =& $publishedArticle->getGalleys();
			$galleyViews[$articleId] = array();
			$galleyViewTotals[$articleId] = 0;
			foreach ($galleys as $galley) {
				$label = $galley->getGalleyLabel();
				$i = array_search($label, $galleyLabels);
				if ($i === false) {
					$i = count($galleyLabels);
					$galleyLabels[] = $label;
				}

				// Make sure the array is the same size as in previous iterations
				//  so that we insert values into the right location
				$galleyViews[$articleId] = array_pad($galleyViews[$articleId], count($galleyLabels), '');

				$views = $galley->getViews();
				$galleyViews[$articleId][$i] = $views;
				$galleyViewTotals[$articleId] += $views;
			}

			// Clean up
			unset($publishedArticle, $galleys);
		}

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');
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
