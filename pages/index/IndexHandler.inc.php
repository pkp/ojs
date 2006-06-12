<?php

/**
 * IndexHandler.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.index
 *
 * Handle site index requests.
 *
 * $Id$
 */

class IndexHandler extends Handler {

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 */
	function index($args) {
		parent::validate();

		$templateMgr = &TemplateManager::getManager();
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journalPath = Request::getRequestedJournalPath();
		$templateMgr->assign('helpTopicId', 'user.home');

		if ($journalPath != 'index' && $journalDao->journalExistsByPath($journalPath)) {
			$journal = &Request::getJournal();

			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $journal->getJournalPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $journal->getJournalPageHeaderLogo(true));
			$templateMgr->assign('additionalHomeContent', $journal->getSetting('additionalHomeContent'));
			$templateMgr->assign('homepageImage', $journal->getSetting('homepageImage'));
			$templateMgr->assign('journalDescription', $journal->getSetting('journalDescription'));

			$displayCurrentIssue = $journal->getSetting('displayCurrentIssue');
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issue = &$issueDao->getCurrentIssue($journal->getJournalId());
			if ($displayCurrentIssue && isset($issue)) {
				import('pages.issue.IssueHandler');
				// The current issue TOC/cover page should be displayed below the custom home page.
				IssueHandler::setupIssueTemplate($issue);
			}

			$enableAnnouncements = $journal->getSetting('enableAnnouncements');
			if ($enableAnnouncements) {
				$enableAnnouncementsHomepage = $journal->getSetting('enableAnnouncementsHomepage');
				if ($enableAnnouncementsHomepage) {
					$numAnnouncementsHomepage = $journal->getSetting('numAnnouncementsHomepage');
					$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
					$announcements = &$announcementDao->getNumAnnouncementsNotExpiredByJournalId($journal->getJournalId(), $numAnnouncementsHomepage);
					$templateMgr->assign('announcements', $announcements);
					$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
				}
			} 
			$templateMgr->display('index/journal.tpl');
		} else {
			$siteDao = &DAORegistry::getDAO('SiteDAO');
			$site = &$siteDao->getSite();

			if ($site->getJournalRedirect() && ($journal = $journalDao->getJournal($site->getJournalRedirect())) != null) {
				Request::redirect($journal->getPath());
			}

			$templateMgr->assign('intro', $site->getIntro());
			$journals = &$journalDao->getEnabledJournals();
			$templateMgr->assign_by_ref('journals', $journals);
			$templateMgr->display('index/site.tpl');
		}
	}
}

?>
