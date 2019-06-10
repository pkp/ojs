<?php

/**
 * @file plugins/reports/views/ViewReportPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ViewReportPlugin
 * @ingroup plugins_reports_views
 *
 * @brief View report plugin
 */


import('lib.pkp.classes.plugins.ReportPlugin');

class ViewReportPlugin extends ReportPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
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

	/**
	 * @copydoc ReportPlugin::display()
	 */
	function display($args, $request) {
		$journal = $request->getJournal();

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

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedSubmissionDao = DAORegistry::getDAO('PublishedSubmissionDAO');

		$publishedSubmissions =& $publishedSubmissionDao->getPublishedSubmissionsByJournalId($journal->getId());
		while ($publishedSubmission = $publishedSubmissions->next()) {
			$articleId = $publishedSubmission->getId();
			$issueId = $publishedSubmission->getIssueId();
			$articleTitles[$articleId] = PKPString::regexp_replace( "/\r|\n/", "", $publishedSubmission->getLocalizedTitle() );

			// Store the abstract view count
			$abstractViewCounts[$articleId] = $publishedSubmission->getViews();
			// Make sure we get the issue identification
			$articleIssueIdentificationMap[$articleId] = $issueId;
			if (!isset($issueIdentifications[$issueId])) {
				$issue = $issueDao->getById($issueId);
				$issueIdentifications[$issueId] = $issue->getIssueIdentification();
				$issueDatesPublished[$issueId] = $issue->getDatePublished();
				unset($issue);
			}

			// For each galley, store the label and the count
			$galleys = $publishedSubmission->getGalleys();
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
		}

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=views-' . date('Ymd') . '.csv');
		$fp = fopen('php://output', 'wt');
		//Add BOM (byte order mark) to fix UTF-8 in Excel
		fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
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


