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
	function current() {
		parent::validate();

		$journal = &Request::getJournal();

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = &$issueDao->getCurrentIssue($journal->getJournalId());

		$templateMgr = &TemplateManager::getManager();

		if ($issue != null) {
			$issueIdentification = $issue->getVolume() . '.' . $issue->getNumber() . ' (' . $issue->getYear() . ')';
			$issueTitle = Locale::translate('editor.issues.toc') . ', ';
			$issueTitle .= Locale::translate('issue.volume') . ' ' . $issue->getVolume() . ' ';
			$issueTitle .= Locale::translate('issue.number') . ' ' . $issue->getNumber() . ' ';
			$issueTitle .= '(' . $issue->getYear() . ')';

			$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());

			$templateMgr->assign('issue', $issue);
			$templateMgr->assign('publishedArticles', $publishedArticles);
		} else {
			$issueIdentification = Locale::translate('current.noCurrentIssue');
			$issueTitle = Locale::translate('current.noCurrentIssue');			
		}

		$templateMgr->assign('issueIdentification', $issueIdentification);
		$templateMgr->assign('issueTitle', $issueTitle);
		$templateMgr->assign('pageHierarchy', array(array('issue/current', 'current.current')));
		$templateMgr->display('issue/current.tpl');
	}

	/**
	 * Display issue view page.
	 */
	function view($args) {
		parent::validate();

		$issueId = isset($args[0]) ? (int)$args[0] : 0;

		IssueHandler::archive($issueId);
	}

	/**
	 * Display issue archive page.
	 */
	function archive($issueId) {
		parent::validate();

		$journal = &Request::getJournal();

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issues = $issueDao->getSelectedIssues($journal->getJournalId(),1);

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
			$label = "$vol " . $currIssue->getVolume() . ", $no " . $currIssue->getNumber() . ' (' . $currIssue->getYear() . ')';
			$issueOptions[$currIssue->getIssueId()] = $label;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issueOptions', $issueOptions);

		if (isset($issue)) {
			$issueIdentification = $issue->getVolume() . '.' . $issue->getNumber() . ' (' . $issue->getYear() . ')';
			$issueTitle = Locale::translate('editor.issues.toc') . ', ';
			$issueTitle .= Locale::translate('issue.volume') . ' ' . $issue->getVolume() . ' ';
			$issueTitle .= Locale::translate('issue.number') . ' ' . $issue->getNumber() . ' ';
			$issueTitle .= '(' . $issue->getYear() . ')';

			$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());

			$templateMgr->assign('issue', $issue);
			$templateMgr->assign('publishedArticles', $publishedArticles);

			$templateMgr->assign('issueId', $issue->getIssueId());
		} else {
			$issueIdentification = Locale::translate('archive.issueUnavailable');
			$issueTitle = Locale::translate('archive.issueUnavailable');			
		}

		$templateMgr->assign('issueIdentification', $issueIdentification);
		$templateMgr->assign('issueTitle', $issueTitle);
		$templateMgr->assign('pageHierarchy', array(array('issue/archive', 'archive.archives')));
		$templateMgr->display('issue/archive.tpl');
	}

	/**
	 * Downloads the document
	 */
	function download($args) {
		$articleId = isset($args[0]) ? (int)$args[0] : 0;
		$fileId = isset($args[1]) ? (int)$args[1] : 0;

		if ($articleId && $fileId) {
			import('file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileManager->downloadFile($fileId);
		}
	}

}

?>
