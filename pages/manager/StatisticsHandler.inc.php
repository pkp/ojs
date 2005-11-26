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

define('REPORT_TYPE_JOURNAL',	0x00001);
define('REPORT_TYPE_EDITOR',	0x00002);
define('REPORT_TYPE_REVIEWER',	0x00003);
define('REPORT_TYPE_SECTION',	0x00004);

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

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$articleStatistics = $publishedArticleDao->getArticleStatistics($journal->getJournalId());
		$templateMgr->assign('articleStatistics', $articleStatistics);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueStatistics = $issueDao->getIssueStatistics($journal->getJournalId());
		$templateMgr->assign('issueStatistics', $issueStatistics);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userStatistics = $roleDao->getUserStatistics($journal->getJournalId());
		$templateMgr->assign('userStatistics', $userStatistics);

		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$notifiableUsers = $notificationStatusDao->getNotifiableUsersCount($journal->getJournalId());
		$templateMgr->assign('notifiableUsers', $notifiableUsers);

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

		$articleDao =& DAORegistry::getDAO('ArticleDAO');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);
	}
}

?>
