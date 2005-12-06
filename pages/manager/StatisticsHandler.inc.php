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

	function csvEscape($value) {
		$value = str_replace('"', '""', $value);
		return '"' . $value . '"';
	}

	function reportGenerator($args) {
		parent::validate();
		$journal =& Request::getJournal();

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');

		$report =& $journalStatisticsDao->getJournalReport($journal->getJournalId(), $fromDate, $toDate);

		$templateMgr =& TemplateManager::getManager();
		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$separator = ',';

		// Display the heading row.
		echo Locale::translate('article.submissionId');
		for ($i=0; $i<$report->getMaxAuthors(); $i++) {
			echo $separator . Locale::translate('manager.statistics.reports.author', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.affiliation', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.country', array('num' => $i+1));
		}
		echo $separator . Locale::translate('article.title');
		echo $separator . Locale::translate('section.section');
		echo $separator . Locale::translate('submissions.submitted');
		echo $separator . Locale::translate('user.role.editor');

		for ($i=0; $i<$report->getMaxReviewers(); $i++) {
			echo $separator . Locale::translate('manager.statistics.reports.reviewer', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.score', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.recommendation', array('num' => $i+1));
		}

		echo $separator . Locale::translate('editor.article.decision');
		echo $separator . Locale::translate('manager.statistics.reports.daysToDecision');
		echo $separator . Locale::translate('manager.statistics.reports.daysToPublication');

		echo "\n";

		// Display the report.
		$dateFormatShort = Config::getVar('general', 'date_format_short');
		while ($row =& $report->next()) {
			echo $row['articleId'];

			for ($i=0; $i<$report->getMaxAuthors(); $i++) {
				echo $separator . StatisticsHandler::csvEscape($row['authors'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['affiliations'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['countries'][$i]);
			}

			echo $separator . StatisticsHandler::csvEscape($row['title']);

			echo $separator . StatisticsHandler::csvEscape($row['section']);

			echo $separator . $row['dateSubmitted'];

			echo $separator . StatisticsHandler::csvEscape($row['editor']);

			for ($i=0; $i<$report->getMaxReviewers(); $i++) {
				echo $separator . StatisticsHandler::csvEscape($row['reviewers'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['scores'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['recommendations'][$i]);
			}

			echo $separator . StatisticsHandler::csvEscape($row['decision']);
			echo $separator . StatisticsHandler::csvEscape($row['daysToDecision']);
			echo $separator . StatisticsHandler::csvEscape($row['daysToPublication']);
			echo "\n";
		}
	}
}

?>
