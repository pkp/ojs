<?php

/**
 * IndexHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
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
		
		if ($journalPath != 'index' && $journalDao->journalExistsByPath($journalPath)) {
			$journal = &Request::getJournal();
			
			// Assign header and content for home page
			$templateMgr->assign('pageHeaderTitle', $journal->getJournalPageHeaderTitle(true));
			$templateMgr->assign('pageHeaderLogo', $journal->getJournalPageHeaderLogo(true));
			$templateMgr->assign('additionalHomeContent', $journal->getSetting('additionalContent'));
			$templateMgr->assign('homepageImage', $journal->getSetting('homepageImage'));
			$templateMgr->assign('journalDescription', $journal->getSetting('journalDescription'));

			$displayCurrentIssue = $journal->getSetting('displayCurrentIssue');
			$templateMgr->assign('displayCurrentIssue', $displayCurrentIssue);
			if ($displayCurrentIssue) {
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$issue = &$issueDao->getCurrentIssue($journal->getJournalId());
				if ($issue != null) {

					$showToc = isset($args[0]) ? $args[0] : '';
					$showToc = ($showToc == 'showToc') ? true : false;

					if (!$showToc && $issue->getFileName() && $issue->getShowCoverPage()) {
						$templateMgr->assign('fileName', $issue->getFileName());
						$templateMgr->assign('originalFileName', $issue->getOriginalFileName());

						$publicFileManager = new PublicFileManager();
						$coverPagePath = Request::getBaseUrl() . '/';
						$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/';
						$coverPagePath .= $issue->getFileName();
						$templateMgr->assign('coverPagePath', $coverPagePath);
						$showToc = false;
					} else {
						$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
						$publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());
						$templateMgr->assign('publishedArticles', $publishedArticles);
						$showToc = true;
					}
					$templateMgr->assign('showToc', $showToc);
					$templateMgr->assign('issue', $issue);
				}
			}
			
			$templateMgr->display('index/journal.tpl');
			
		} else {
			$siteDao = &DAORegistry::getDAO('SiteDAO');
			$site = &$siteDao->getSite();
			
			if ($site->getJournalRedirect() && ($journal = $journalDao->getJournal($site->getJournalRedirect())) != null) {
				Request::redirect($journal->getPath(), false);
			}
			
			$templateMgr->assign('intro', $site->getIntro());
			$journals = &$journalDao->getEnabledJournals();
			$templateMgr->assign('journals', $journals);
			$templateMgr->display('index/site.tpl');
		}
	}
}

?>
