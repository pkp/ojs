<?php

/**
 * IssueHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.issue
 *
 * Handle requests for issue functions. 
 *
 * $Id$
 */

class IssueHandler extends Handler {

	/**
	 * Display about index page.
	 */
	function index($args) {
		parent::validate();
		IssueHandler::current();
	}

	/**
	 * Display current issue page.
	 */
	function current($args) {
		parent::validate();

		$journal = &Request::getJournal();

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = &$issueDao->getCurrentIssue($journal->getJournalId());

		$templateMgr = &TemplateManager::getManager();

		if ($issue != null) {
			$issueTitle = $issue->getIssueIdentification();
			$issueCrumbTitle = $issue->getIssueIdentification(false, true);

			$arg = isset($args[0]) ? $args[0] : '';
			$showToc = ($arg == 'showToc') ? true : false;

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
				$issueTitle = $issueTitle;
				$showToc = true;
			}

			$templateMgr->assign('issue', $issue);
			$templateMgr->assign('showToc', $showToc);
			
		} else {
			$issueCrumbTitle = Locale::translate('current.noCurrentIssue');
			$issueTitle = Locale::translate('current.noCurrentIssue');			
		}

		$templateMgr->assign('issueCrumbTitle', $issueCrumbTitle);
		$templateMgr->assign('issueTitle', $issueTitle);
		$templateMgr->assign('pageHierarchy', array(array('issue/current', 'current.current')));
		$templateMgr->display('issue/current.tpl');
	}

	/**
	 * Display issue view page.
	 */
	function view($args) {
		parent::validate();

		IssueHandler::archive($args);
	}

	/**
	 * Display issue archive page.
	 */
	function archive($args) {
		parent::validate();

		$issueId = isset($args[0]) ? (int)$args[0] : 0;
		$showToc = isset($args[1]) ? $args[1] : '';

		$journal = &Request::getJournal();

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issues = $issueDao->getPublishedIssues($journal->getJournalId());

		$vol = Locale::Translate('editor.issues.vol');
		$no = Locale::Translate('editor.issues.no');
		$issueOptions = array();
		foreach ($issues as $currIssue) {
			if (!isset($issue) && !$issueId) {
				$issue = $currIssue;
			} else {
				if ($issueId == $currIssue->getIssueId()) {
					$issue = $currIssue;
				}
			}
			$issueOptions[$currIssue->getIssueId()] = $currIssue->getIssueIdentification();
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issueOptions', $issueOptions);

		if (isset($issue)) {

			$issueTitle = $issue->getIssueIdentification();
			$issueCrumbTitle = $issue->getIssueIdentification(false, true);

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

				$issueTitle = Locale::translate('issue.toc') . ', ' . $issueTitle;

				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());

				$templateMgr->assign('publishedArticles', $publishedArticles);
				$showToc = true;
			}
			$templateMgr->assign('showToc', $showToc);
			$templateMgr->assign('issueId', $issue->getIssueId());
			$templateMgr->assign('issue', $issue);

		} else {
			$issueCrumbTitle = Locale::translate('archive.issueUnavailable');
			$issueTitle = Locale::translate('archive.issueUnavailable');			
		}

		$templateMgr->assign('issueCrumbTitle', $issueCrumbTitle);
		$templateMgr->assign('issueTitle', $issueTitle);
		$templateMgr->assign('pageHierarchy', array(array('issue/archive', 'archive.archives')));
		$templateMgr->display('issue/archive.tpl');
	}

}

?>
