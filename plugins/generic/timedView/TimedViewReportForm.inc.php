<?php
/**
 * @file plugins/generic/timedView/TimedViewReportForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
		parent::Form($plugin->getTemplatePath() . 'plugins/generic/timedView/timedViewReportForm.tpl');

		// Start date is provided and is valid
		$this->addCheck(new FormValidator($this, 'dateStartYear', 'required', 'plugins.generic.timedView.form.dateStartRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateStartYear', 'required', 'plugins.generic.timedView.form.dateStartValid', create_function('$dateStartYear', '$minYear = date(\'Y\') + TIMED_VIEW_REPORT_YEAR_OFFSET_PAST; $maxYear = date(\'Y\') + TIMED_VIEW_REPORT_YEAR_OFFSET_FUTURE; return ($dateStartYear >= $minYear && $dateStartYear <= $maxYear) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateStartMonth', 'required', 'plugins.generic.timedView.form.dateStartRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateStartMonth', 'required', 'plugins.generic.timedView.form.dateStartValid', create_function('$dateStartMonth', 'return ($dateStartMonth >= 1 && $dateStartMonth <= 12) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateStartDay', 'required', 'plugins.generic.timedView.form.dateStartRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateStartDay', 'required', 'plugins.generic.timedView.form.dateStartValid', create_function('$dateStartDay', 'return ($dateStartDay >= 1 && $dateStartDay <= 31) ? true : false;')));

		// End date is provided and is valid
		$this->addCheck(new FormValidator($this, 'dateEndYear', 'required', 'plugins.generic.timedView.form.dateEndRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateEndYear', 'required', 'plugins.generic.timedView.form.dateEndValid', create_function('$dateEndYear', '$minYear = date(\'Y\') + TIMED_VIEW_REPORT_YEAR_OFFSET_PAST; $maxYear = date(\'Y\') + TIMED_VIEW_REPORT_YEAR_OFFSET_FUTURE; return ($dateEndYear >= $minYear && $dateEndYear <= $maxYear) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateEndMonth', 'required', 'plugins.generic.timedView.form.dateEndRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateEndMonth', 'required', 'plugins.generic.timedView.form.dateEndValid', create_function('$dateEndMonth', 'return ($dateEndMonth >= 1 && $dateEndMonth <= 12) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateEndDay', 'required', 'plugins.generic.timedView.form.dateEndRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateEndDay', 'required', 'plugins.generic.timedView.form.dateEndValid', create_function('$dateEndDay', 'return ($dateEndDay >= 1 && $dateEndDay <= 31) ? true : false;')));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display($request) {
		$templateMgr = TemplateManager::getManager($request);
		$journal = $request->getJournal();

		$templateMgr->assign('yearOffsetPast', TIMED_VIEW_REPORT_YEAR_OFFSET_PAST);
		$templateMgr->assign('yearOffsetFuture', TIMED_VIEW_REPORT_YEAR_OFFSET_FUTURE);

		parent::display($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('dateStartYear', 'dateStartMonth', 'dateStartDay', 'dateEndYear', 'dateEndMonth', 'dateEndDay'));
		$this->_data['dateStart'] = $this->_data['dateStartYear'] . '-' . $this->_data['dateStartMonth'] . '-' . $this->_data['dateStartDay'];
		$this->_data['dateEnd'] = $this->_data['dateEndYear'] . '-' . $this->_data['dateEndMonth'] . '-' . $this->_data['dateEndDay'];
	}

	/**
	 * Save subscription.
	 */
	function execute() {
		$journal = Request::getJournal();

		$columns = array(
			__('plugins.generic.timedView.report.articleId'),
			__('plugins.generic.timedView.report.articleTitle'),
			__('issue.issue'),
			__('plugins.generic.timedView.report.datePublished'),
			__('plugins.generic.timedView.report.abstractViews'),
			__('plugins.generic.timedView.report.galleyViews'),
		);

		$articleData = $galleyLabels = $galleyViews = array();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

		$timedViewReportDao = DAORegistry::getDAO('TimedViewReportDAO');
		$abstractViewCounts =& $timedViewReportDao->getAbstractViewCount($journal->getId(), $this->getData('dateStart'), $this->getData('dateEnd'));

		while ($row = $abstractViewCounts->next()) {
			$galleyViewTotal = 0;
			$articleId = $row['submission_id'];
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);
			$issueId = $publishedArticle->getIssueId();
			$issue = $issueDao->getById($issueId);

			$articleData[$articleId] = array(
				'id' => $articleId,
				'title' => $publishedArticle->getLocalizedTitle(),
				'issue' => $issue->getIssueIdentification(),
				'datePublished' => $publishedArticle->getDatePublished(),
				'totalAbstractViews' => $row['total_abstract_views']
			);

			// For each galley, store the label and the count
			$galleyCounts = $timedViewReportDao->getGalleyViewCountsForArticle($articleId, $this->getData('dateStart'), $this->getData('dateEnd'));
			$galleyViews[$articleId] = array();
			$galleyViewTotal = 0;
			while ($galley = $galleyCounts->next()) {
				$label = $galley['label'];
				$i = array_search($label, $galleyLabels);
				if ($i === false) {
					$i = count($galleyLabels);
					$galleyLabels[] = $label;
				}

				// Make sure the array is the same size as in previous iterations
				//  so that we insert values into the right location
				$galleyViews[$articleId] = array_pad($galleyViews[$articleId], count($galleyLabels), '');

				$views = $galley['total_galley_views'];
				$galleyViews[$articleId][$i] = $views;
				$galleyViewTotal += $views;
			}

			$articleData[$articleId]['galleyViews'] = $galleyViewTotal;
		}

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');
		$fp = fopen('php://output', 'wt');
		fputcsv($fp, array_merge($columns, $galleyLabels));

		$dateFormatShort = Config::getVar('general', 'date_format_short');
		foreach ($articleData as $articleId => $article) {
			fputcsv($fp, array_merge($articleData[$articleId], $galleyViews[$articleId]));
		}

		fclose($fp);
	}

}

?>
