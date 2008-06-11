<?php

/**
 * @file StatisticsHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class StatisticsHandler
 *
 * Handle requests for statistics functions. 
 *
 * $Id$
 */

class StatisticsHandler extends ManagerHandler {
	/**
	 * Display a list of journal statistics.
	 * WARNING: This implementation should be kept roughly synchronized
	 * with the reader's statistics view in the About pages.
	 */
	function statistics() {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();

		$statisticsYear = Request::getUserVar('statisticsYear');
		if (empty($statisticsYear)) $statisticsYear = date('Y');
		$templateMgr->assign('statisticsYear', $statisticsYear);

		$sectionIds = $journal->getSetting('statisticsSectionIds');
		if (!is_array($sectionIds)) $sectionIds = array();
		$templateMgr->assign('sectionIds', $sectionIds);

		foreach (StatisticsHandler::getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $journal->getSetting($name));
		}
		$templateMgr->assign('statViews', $journal->getSetting('statViews'));

		$fromDate = mktime(0, 0, 0, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

		$journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');
		$articleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getJournalId(), null, $fromDate, $toDate);
		$templateMgr->assign('articleStatistics', $articleStatistics);

		$limitedArticleStatistics = $journalStatisticsDao->getArticleStatistics($journal->getJournalId(), $sectionIds, $fromDate, $toDate);
		$templateMgr->assign('limitedArticleStatistics', $limitedArticleStatistics);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sections =& $sectionDao->getJournalSections($journal->getJournalId());
		$templateMgr->assign('sections', $sections->toArray());

		$issueStatistics = $journalStatisticsDao->getIssueStatistics($journal->getJournalId(), $fromDate, $toDate);
		$templateMgr->assign('issueStatistics', $issueStatistics);

		$reviewerStatistics = $journalStatisticsDao->getReviewerStatistics($journal->getJournalId(), $sectionIds, $fromDate, $toDate);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$allUserStatistics = $journalStatisticsDao->getUserStatistics($journal->getJournalId(), null, $toDate);
		$templateMgr->assign('allUserStatistics', $allUserStatistics);

		$userStatistics = $journalStatisticsDao->getUserStatistics($journal->getJournalId(), $fromDate, $toDate);
		$templateMgr->assign('userStatistics', $userStatistics);

		$enableSubscriptions = $journal->getSetting('enableSubscriptions');
		if ($enableSubscriptions) {
			$templateMgr->assign('enableSubscriptions', true);
			$allSubscriptionStatistics = $journalStatisticsDao->getSubscriptionStatistics($journal->getJournalId(), null, $toDate);
			$templateMgr->assign('allSubscriptionStatistics', $allSubscriptionStatistics);

			$subscriptionStatistics = $journalStatisticsDao->getSubscriptionStatistics($journal->getJournalId(), $fromDate, $toDate);
			$templateMgr->assign('subscriptionStatistics', $subscriptionStatistics);
		}

		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$notifiableUsers = $notificationStatusDao->getNotifiableUsersCount($journal->getJournalId());
		$templateMgr->assign('notifiableUsers', $notifiableUsers);

		$templateMgr->assign('reportTypes', array(
			REPORT_TYPE_JOURNAL => 'manager.statistics.reports.type.journal',
			REPORT_TYPE_EDITOR => 'manager.statistics.reports.type.editor',
			REPORT_TYPE_REVIEWER => 'manager.statistics.reports.type.reviewer',
			REPORT_TYPE_SECTION => 'manager.statistics.reports.type.section'
		));

		$templateMgr->assign('helpTopicId', 'journal.managementPages.statsAndReports');

		$templateMgr->display('manager/statistics/index.tpl');
	}

	function saveStatisticsSections() {
		// The manager wants to save the list of sections used to
		// generate statistics.

		parent::validate();

		$journal = &Request::getJournal();

		$sectionIds = Request::getUserVar('sectionIds');
		if (!is_array($sectionIds)) {
			if (empty($sectionIds)) $sectionIds = array();
			else $sectionIds = array($sectionIds);
		}

		$journal->updateSetting('statisticsSectionIds', $sectionIds);
		Request::redirect(null, null, 'statistics', null, array('statisticsYear' => Request::getUserVar('statisticsYear')));
	}

	function getPublicStatisticsNames() {
		return array(
			'statNumPublishedIssues',
			'statItemsPublished',
			'statNumSubmissions',
			'statPeerReviewed',
			'statCountAccept',
			'statCountDecline',
			'statCountRevise',
			'statDaysPerReview',
			'statDaysToPublication',
			'statRegisteredUsers',
			'statRegisteredReaders',
			'statSubscriptions',
		);
	}

	function savePublicStatisticsList() {
		parent::validate();

		$journal =& Request::getJournal();
		foreach (StatisticsHandler::getPublicStatisticsNames() as $name) {
			$journal->updateSetting($name, Request::getUserVar($name)?true:false);
		}
		$journal->updateSetting('statViews', Request::getUserVar('statViews')?true:false);
		Request::redirect(null, null, 'statistics', null, array('statisticsYear' => Request::getUserVar('statisticsYear')));
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

		$reportType = (int) Request::getUserVar('reportType');

		switch ($reportType) {
			case REPORT_TYPE_EDITOR:
				$report =& $journalStatisticsDao->getEditorReport($journal->getJournalId(), $fromDate, $toDate);
				break;
			case REPORT_TYPE_REVIEWER:
				$report =& $journalStatisticsDao->getReviewerReport($journal->getJournalId(), $fromDate, $toDate);
				break;
			case REPORT_TYPE_SECTION:
				$report =& $journalStatisticsDao->getSectionReport($journal->getJournalId(), $fromDate, $toDate);
				break;
			case REPORT_TYPE_JOURNAL:
			default:
				$reportType = REPORT_TYPE_JOURNAL;
				$report =& $journalStatisticsDao->getJournalReport($journal->getJournalId(), $fromDate, $toDate);
				break;
		}

		$templateMgr =& TemplateManager::getManager();
		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$separator = ',';

		// Display the heading row.
		switch ($reportType) {
			case REPORT_TYPE_EDITOR:
				echo Locale::translate('user.role.editor') . $separator;
				break;
			case REPORT_TYPE_REVIEWER:
				echo Locale::translate('user.role.reviewer') . $separator;
				echo Locale::translate('manager.statistics.reports.singleScore') . $separator;
				echo Locale::translate('user.affiliation') . $separator;
				break;
			case REPORT_TYPE_SECTION:
				echo Locale::translate('section.section') . $separator;
				break;
		}

		echo Locale::translate('article.submissionId');
		for ($i=0; $i<$report->getMaxAuthors(); $i++) {
			echo $separator . Locale::translate('manager.statistics.reports.author', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.affiliation', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.country', array('num' => $i+1));
		}
		echo $separator . Locale::translate('article.title');

		if ($reportType !== REPORT_TYPE_SECTION) echo $separator . Locale::translate('section.section');

		echo $separator . Locale::translate('submissions.submitted');

		if ($reportType !== REPORT_TYPE_EDITOR) for ($i=0; $i<$report->getMaxEditors(); $i++) {
			echo $separator . Locale::translate('manager.statistics.reports.editor', array('num' => $i+1));
		}

		if ($reportType !== REPORT_TYPE_REVIEWER) for ($i=0; $i<$report->getMaxReviewers(); $i++) {
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
			switch ($reportType) {
				case REPORT_TYPE_EDITOR:
					echo $row['editor'] . $separator;
					break;
				case REPORT_TYPE_REVIEWER:
					echo $row['reviewer'] . $separator;
					echo $row['score'] . $separator;
					echo $row['affiliation'] . $separator;
					break;
				case REPORT_TYPE_SECTION:
					echo $row['section'] . $separator;
					break;
			}

			echo $row['articleId'];

			for ($i=0; $i<$report->getMaxAuthors(); $i++) {
				echo $separator . StatisticsHandler::csvEscape($row['authors'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['affiliations'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['countries'][$i]);
			}

			echo $separator . StatisticsHandler::csvEscape($row['title']);

			if ($reportType !== REPORT_TYPE_SECTION) echo $separator . StatisticsHandler::csvEscape($row['section']);

			echo $separator . $row['dateSubmitted'];

			if ($reportType !== REPORT_TYPE_EDITOR) for ($i=0; $i<$report->getMaxEditors(); $i++) {
				echo $separator . StatisticsHandler::csvEscape($row['editors'][$i]);
			}

			if ($reportType !== REPORT_TYPE_REVIEWER) for ($i=0; $i<$report->getMaxReviewers(); $i++) {
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
