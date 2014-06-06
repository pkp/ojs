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
	 * @param $request Request
	 */
	function execute(&$request) {
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		$journalId = $journal->getId();

		$dateStart = $this->getData('dateStart');
		$dateEnd = $this->getData('dateEnd');
		if ($this->getData('useTimedViewRecords')) {
			$metricType = OJS_METRIC_TYPE_TIMED_VIEWS;
		} else {
			$metricType = OJS_METRIC_TYPE_COUNTER;
		}

		import('lib.pkp.classes.db.DBResultRange');
		$dbResultRange = new DBResultRange(STATISTICS_MAX_ROWS);

		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$columns = array(STATISTICS_DIMENSION_ASSOC_ID, STATISTICS_DIMENSION_ASSOC_TYPE, STATISTICS_DIMENSION_SUBMISSION_ID);
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => array(ASSOC_TYPE_ARTICLE, ASSOC_TYPE_GALLEY),
			STATISTICS_DIMENSION_CONTEXT_ID => $journalId);
		if ($dateStart && $dateEnd) {
			$filter[STATISTICS_DIMENSION_DAY] = array('from' => $dateStart, 'to' => $dateEnd);
		}

		// Need to consider paging of stats records for databases with
		// large amount of statistics data. We store all the records we
		// need in those total variables. In a really really large metrics
		// table, and for a considerable large period of time, this process
		// might exceed the maximum amount of memory or take so long to
		// finish that the browser will time out. Since users can generate
		// the report by smaller periods of time, this is not a big issue.
		$allReportStats = array();

		// While we still have stats records about article abstract views,
		// keep adding them to the total.
		while (true) {
			$reportStats = $metricsDao->getMetrics($metricType, $columns, $filter,
					array(STATISTICS_DIMENSION_SUBMISSION_ID => STATISTICS_ORDER_ASC,
							STATISTICS_DIMENSION_ASSOC_TYPE => STATISTICS_ORDER_ASC),
					$dbResultRange);


			$allReportStats = array_merge($allReportStats, $reportStats);
			$dbResultRange->setPage($dbResultRange->getPage() + 1);

			// It means we don't have more pages to fetch.
			if (count($reportStats) < $dbResultRange->getCount()) break;
		}

		// Format stats and retrieve submission and galleys info.
		list($articleData, $galleyLabels, $galleyViews) = $this->_formatStats($allReportStats);
		$this->_buildReport($articleData, $galleyLabels, $galleyViews);
	}

	/**
	 * Return report statistics already formatted in columns
	 * to generate the report.
	 * @param $reportStats array All metric records retrieved
	 * with MetricsDAO::getMetrics()
	 * @return array
	 */
	function _formatStats($reportStats) {
		$articleData = $galleyLabels = $galleyViews = array();

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */

		$workingArticleId = null;
		$objects = array();

		foreach ($reportStats as $record) {
			$articleId = $record[STATISTICS_DIMENSION_SUBMISSION_ID];
			if (is_null($workingArticleId)) {
				// Just started, initiated this value.
				$workingArticleId = $articleId;
			}

			if ($articleId != $workingArticleId) {
				// Finished getting data for all objects related to the
				// working article id.

				// Add the galleys total downloads.
				if (isset($articleData[$workingArticleId]) && isset($galleyViewTotal)) {
					$articleData[$workingArticleId]['galleyViews'] = $galleyViewTotal;
				}

				// Clean up and move to the next article id.
				unset($galleyViewTotal);

				// Start to work on the current article id.
				$workingArticleId = $articleId;
			}

			if ($articleId == $workingArticleId) {
				// Retrieve article and galleys data related to the
				// working article id.
				$assocType = $record[STATISTICS_DIMENSION_ASSOC_TYPE];

				// Retrieve article data, if it wasn't before.
				if (!isset($articleData[$workingArticleId])) {
					$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($workingArticleId, null, true);
					if (!$publishedArticle) continue;
					$issueId = $publishedArticle->getIssueId();
					$issue =& $issueDao->getIssueById($issueId, null, true);

					if ($assocType == ASSOC_TYPE_ARTICLE) {
						$abstractViews = $record[STATISTICS_METRIC];
					} else {
						$abstractViews = '';
					}

					$articleData[$workingArticleId] = array(
						'id' => $workingArticleId,
						'title' => $publishedArticle->getLocalizedTitle(),
						'issue' => $issue->getIssueIdentification(),
						'datePublished' => $publishedArticle->getDatePublished(),
						'totalAbstractViews' => $abstractViews
					);
				}

				// Retrieve galley data.
				if ($assocType == ASSOC_TYPE_GALLEY) {
					if (!isset($galleyViews[$workingArticleId])) {
						$galleyViews[$workingArticleId] = array();
					}
					$galleyId = $record[STATISTICS_DIMENSION_ASSOC_ID];
					$galley =& $galleyDao->getGalley($galleyId, null, true);
					$label = $galley->getLabel();
					$i = array_search($label, $galleyLabels);
					if ($i === false) {
						$i = count($galleyLabels);
						$galleyLabels[] = $label;
					}

					// Make sure the array is the same size as in previous iterations
					// so that we insert values into the right location
					$galleyViews[$workingArticleId] = array_pad($galleyViews[$workingArticleId], count($galleyLabels), '');

					$views = $record[STATISTICS_METRIC];
					$galleyViews[$workingArticleId][$i] = $views;
					if (!isset($galleyViewTotal)) $galleyViewTotal = $views;
					$galleyViewTotal += $views;
				}
			}
		}

		return array($articleData, $galleyLabels, $galleyViews);
	}

	/**
	 * Build the report using the passed data.
	 * @param $articleData array Title, journal, data, abstract views, etc.
	 * @param $galleyLabels array All galley labels to be used as columns.
	 * @param $galleyViews array All galley views per label.
	 */
	function _buildReport($articleData, $galleyLabels, $galleyViews) {
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
			if (isset($galleyViews[$articleId])) {
				fputcsv($fp, array_merge($articleData[$articleId], $galleyViews[$articleId]));
			} else {
				fputcsv($fp, $articleData[$articleId]);
			}
		}

		fclose($fp);
	}

}

?>
