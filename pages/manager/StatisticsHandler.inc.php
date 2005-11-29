<?php

/**
 * StatisticsHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for statistics functions. 
 *
 * $Id$
 */

class StatisticsHandler extends ManagerHandler {

	/**
	 * Display a list of the emails within the current journal.
	 */
	function statistics() {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId','journal.managementPages.statistics');

		$statisticsYear = Request::getUserVar('statisticsYear');
		if (empty($statisticsYear)) $statisticsYear = date('Y');
		$templateMgr->assign('statisticsYear', $statisticsYear);
		
		$fromDate = mktime(0, 0, 1, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

		$journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');
		$articleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getJournalId(), $fromDate, $toDate);
		$templateMgr->assign('articleStatistics', $articleStatistics);

		$issueStatistics = $journalStatisticsDao->getIssueStatistics($journal->getJournalId(), $fromDate, $toDate);
		$templateMgr->assign('issueStatistics', $issueStatistics);

		$reviewerStatistics = $journalStatisticsDao->getReviewerStatistics($journal->getJournalId(), $fromDate, $toDate);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$userStatistics = $journalStatisticsDao->getUserStatistics($journal->getJournalId());
		$templateMgr->assign('userStatistics', $userStatistics);

		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$notifiableUsers = $notificationStatusDao->getNotifiableUsersCount($journal->getJournalId());
		$templateMgr->assign('notifiableUsers', $notifiableUsers);

		$countries = $journalStatisticsDao->getCountryDistribution($journal->getJournalId());
		$templateMgr->assign_by_ref('countryDistribution', $countries);

		$templateMgr->assign('reportTypes', array(
			REPORT_TYPE_JOURNAL => 'manager.statistics.reports.type.journal',
			REPORT_TYPE_EDITOR => 'manager.statistics.reports.type.editor',
			REPORT_TYPE_REVIEWER => 'manager.statistics.reports.type.reviewer',
			REPORT_TYPE_SECTION => 'manager.statistics.reports.type.section'
		));


		$templateMgr->display('manager/statistics/index.tpl');
	}

	function reportGenerator($args) {
		parent::validate();
		$journal =& Request::getJournal();

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');

		$fields = Request::getUserVar('fields');
		if (!is_array($fields)) {
			if (empty($fields)) $fields = array();
			else $fields = array($fields);
		}

		$report =& $journalStatisticsDao->getReport($journal->getJournalId(), Request::getUserVar('reportType'), $fields, $fromDate, $toDate);

		$templateMgr =& TemplateManager::getManager();
		header('content-disposition: attachment; filename=report.csv');
		header('content-type: text/comma-separated-values');
		$templateMgr->assign_by_ref('report', $report);
		$templateMgr->assign_by_ref('fields', $fields);
		$templateMgr->display('manager/statistics/report.tpl');
	}
}

?>
