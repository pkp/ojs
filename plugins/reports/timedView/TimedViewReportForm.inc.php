<?php
/**
 * @file plugins/generic/timedView/TimedViewReportForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TimedViewReportForm
 */

import('lib.pkp.classes.form.Form');

class TimedViewReportForm extends Form {

	/**
	 * Constructor
	 */
	function TimedViewReportForm(&$plugin) {
		parent::Form($plugin->getTemplatePath() . 'timedViewReportForm.tpl');

		// Start date is provided and is valid
		$this->addCheck(new FormValidator($this, 'dateStartYear', 'required', 'plugins.reports.timedView.form.dateStartRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateStartYear', 'required', 'plugins.reports.timedView.form.dateStartValid', create_function('$dateStartYear', '$minYear = date(\'Y\') + TIMED_VIEW_REPORT_YEAR_OFFSET_PAST; $maxYear = date(\'Y\') + TIMED_VIEW_REPORT_YEAR_OFFSET_FUTURE; return ($dateStartYear >= $minYear && $dateStartYear <= $maxYear) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateStartMonth', 'required', 'plugins.reports.timedView.form.dateStartRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateStartMonth', 'required', 'plugins.reports.timedView.form.dateStartValid', create_function('$dateStartMonth', 'return ($dateStartMonth >= 1 && $dateStartMonth <= 12) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateStartDay', 'required', 'plugins.reports.timedView.form.dateStartRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateStartDay', 'required', 'plugins.reports.timedView.form.dateStartValid', create_function('$dateStartDay', 'return ($dateStartDay >= 1 && $dateStartDay <= 31) ? true : false;')));

		// End date is provided and is valid
		$this->addCheck(new FormValidator($this, 'dateEndYear', 'required', 'plugins.reports.timedView.form.dateEndRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateEndYear', 'required', 'plugins.reports.timedView.form.dateEndValid', create_function('$dateEndYear', '$minYear = date(\'Y\') + TIMED_VIEW_REPORT_YEAR_OFFSET_PAST; $maxYear = date(\'Y\') + TIMED_VIEW_REPORT_YEAR_OFFSET_FUTURE; return ($dateEndYear >= $minYear && $dateEndYear <= $maxYear) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateEndMonth', 'required', 'plugins.reports.timedView.form.dateEndRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateEndMonth', 'required', 'plugins.reports.timedView.form.dateEndValid', create_function('$dateEndMonth', 'return ($dateEndMonth >= 1 && $dateEndMonth <= 12) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateEndDay', 'required', 'plugins.reports.timedView.form.dateEndRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateEndDay', 'required', 'plugins.reports.timedView.form.dateEndValid', create_function('$dateEndDay', 'return ($dateEndDay >= 1 && $dateEndDay <= 31) ? true : false;')));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$journal = &Request::getJournal();

		$templateMgr->assign('yearOffsetPast', TIMED_VIEW_REPORT_YEAR_OFFSET_PAST);
		$templateMgr->assign('yearOffsetFuture', TIMED_VIEW_REPORT_YEAR_OFFSET_FUTURE);

		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('dateStartYear', 'dateStartMonth', 'dateStartDay', 'dateEndYear', 'dateEndMonth', 'dateEndDay', 'useTimedViewRecords'));

		$this->_data['dateStart'] = date('Ymd', mktime(0, 0, 0, $this->_data['dateStartMonth'], $this->_data['dateStartDay'], $this->_data['dateStartYear']));
		$this->_data['dateEnd'] = date('Ymd', mktime(0, 0, 0, $this->_data['dateEndMonth'], $this->_data['dateEndDay'], $this->_data['dateEndYear']));
	}

	/**
	 * Save subscription.
	 */
	function execute() {
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$articleData = $galleyLabels = $galleyViews = array();

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */

		$dateStart = $this->getData('dateStart');
		$dateEnd = $this->getData('dateEnd');
		if ($this->getData('useTimedViewRecords')) {
			$metricType = OJS_METRIC_TYPE_TIMED_VIEWS;
		} else {
			$metricType = OJS_METRIC_TYPE_COUNTER;
		}

		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$columns = array(STATISTICS_DIMENSION_ASSOC_ID);
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_ARTICLE,
			STATISTICS_DIMENSION_CONTEXT_ID => $journalId);
		if ($dateStart && $dateEnd) {
			$filter[STATISTICS_DIMENSION_DAY] = array('from' => $dateStart, 'to' => $dateEnd);
		}
		$abstractViewCounts = $metricsDao->getMetrics($metricType, $columns, $filter);

		foreach ($abstractViewCounts as $row) {
			$galleyViewTotal = 0;
			$articleId = $row['assoc_id'];
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);
			if (!$publishedArticle) continue;
			$issueId = $publishedArticle->getIssueId();
			$issue =& $issueDao->getIssueById($issueId);

			$articleData[$articleId] = array(
				'id' => $articleId,
				'title' => $publishedArticle->getLocalizedTitle(),
				'issue' => $issue->getIssueIdentification(),
				'datePublished' => $publishedArticle->getDatePublished(),
				'totalAbstractViews' => $row['metric']
			);

			// For each galley, store the label and the count
			$columns = array(STATISTICS_DIMENSION_ASSOC_ID);
			$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_GALLEY, STATISTICS_DIMENSION_SUBMISSION_ID => $articleId);
			if ($dateStart && $dateEnd) {
				$filter[STATISTICS_DIMENSION_DAY] = array('from' => $dateStart, 'to' => $dateEnd);
			}
			$galleyCounts = $metricsDao->getMetrics($metricType, $columns, $filter);
			$galleyViews[$articleId] = array();
			$galleyViewTotal = 0;
			foreach ($galleyCounts as $record) {
				$galleyId = $record['assoc_id'];
				$galley =& $galleyDao->getGalley($galleyId);
				$label = $galley->getLabel();
				$i = array_search($label, $galleyLabels);
				if ($i === false) {
					$i = count($galleyLabels);
					$galleyLabels[] = $label;
				}

				// Make sure the array is the same size as in previous iterations
				//  so that we insert values into the right location
				$galleyViews[$articleId] = array_pad($galleyViews[$articleId], count($galleyLabels), '');

				$views = $record['metric'];
				$galleyViews[$articleId][$i] = $views;
				$galleyViewTotal += $views;
			}

			$articleData[$articleId]['galleyViews'] = $galleyViewTotal;

			// Clean up
			unset($row, $galleys);
		}

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');
		$fp = fopen('php://output', 'wt');
		$reportColumns = array(
			__('plugins.reports.timedView.report.articleId'),
			__('plugins.reports.timedView.report.articleTitle'),
			__('issue.issue'),
			__('plugins.reports.timedView.report.datePublished'),
			__('plugins.reports.timedView.report.abstractViews'),
			__('plugins.reports.timedView.report.galleyViews'),
		);

		fputcsv($fp, array_merge($reportColumns, $galleyLabels));

		$dateFormatShort = Config::getVar('general', 'date_format_short');
		foreach ($articleData as $articleId => $article) {
			fputcsv($fp, array_merge($articleData[$articleId], $galleyViews[$articleId]));
		}

		fclose($fp);
	}

}

?>
