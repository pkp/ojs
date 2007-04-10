<?php

/**
 * IssueHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.issue
 *
 * Handle requests for issue functions.
 *
 * $Id$
 */

import ('issue.IssueAction');

class IssueHandler extends Handler {

	/**
	 * Display about index page.
	 */
	function index($args) {
		IssueHandler::current();
	}

	/**
	 * Display current issue page.
	 */
	function current($args = null) {
		parent::validate(true);

		$journal = &Request::getJournal();

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = &$issueDao->getCurrentIssue($journal->getJournalId());

		$templateMgr = &TemplateManager::getManager();

		if ($issue != null) {
			if ($styleFileName = $issue->getStyleFileName()) {
				import('file.PublicFileManager');
				$publicFileManager = &new PublicFileManager();
				$templateMgr->addStyleSheet(
					Request::getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/' . $styleFileName
				);
			}

			$issueHeadingTitle = $issue->getIssueIdentification(false, true);
			$issueCrumbTitle = $issue->getIssueIdentification(false, true);

			$arg = isset($args[0]) ? $args[0] : '';
			$showToc = ($arg == 'showToc') ? true : false;

			if (!$showToc && $issue->getFileName() && $issue->getShowCoverPage()) {
				$templateMgr->assign('fileName', $issue->getFileName());
				$templateMgr->assign('width', $issue->getWidth());
				$templateMgr->assign('height', $issue->getHeight());
				$templateMgr->assign('originalFileName', $issue->getOriginalFileName());

				import('file.PublicFileManager');
				$publicFileManager = &new PublicFileManager();
				$coverPagePath = Request::getBaseUrl() . '/';
				$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/';
				$coverPagePath .= $issue->getFileName();
				$templateMgr->assign('coverPagePath', $coverPagePath);
				$showToc = false;
			} else {

				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId(), true);
				$templateMgr->assign_by_ref('publishedArticles', $publishedArticles);
				$showToc = true;
			}

			$templateMgr->assign_by_ref('issue', $issue);
			$templateMgr->assign('showToc', $showToc);

			// Subscription Access
			import('issue.IssueAction');
			$templateMgr->assign('subscriptionRequired', IssueAction::subscriptionRequired($issue));
			$templateMgr->assign('subscribedUser', IssueAction::subscribedUser($journal));
			$templateMgr->assign('subscribedDomain', IssueAction::subscribedDomain($journal));

		} else {
			$issueCrumbTitle = Locale::translate('current.noCurrentIssue');
			$issueHeadingTitle = Locale::translate('current.noCurrentIssue');
		}

		$templateMgr->assign('issueCrumbTitle', $issueCrumbTitle);
		$templateMgr->assign('issueHeadingTitle', $issueHeadingTitle);
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'issue', 'current'), 'current.current')));
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		$templateMgr->display('issue/current.tpl');
	}

	/**
	 * Display issue view page.
	 */
	function view($args) {
		parent::validate(true);

		$issueId = isset($args[0]) ? $args[0] : 0;
		$showToc = isset($args[1]) ? $args[1] : '';

		$journal = &Request::getJournal();

		$issueDao = &DAORegistry::getDAO('IssueDAO');

		if ($journal->getSetting('enablePublicIssueId')) {
			$issue = &$issueDao->getIssueByBestIssueId($issueId, $journal->getJournalId());
		} else {
			$issue = &$issueDao->getIssueById((int) $issueId);
		}

		if (!$issue) Request::redirect(null, null, 'current');

		$templateMgr = &TemplateManager::getManager();
		IssueHandler::setupIssueTemplate($issue, ($showToc == 'showToc') ? true : false);
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'issue', 'archive'), 'archive.archives')));
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		$templateMgr->display('issue/viewPage.tpl');

	}

	/**
	 * Given an issue, set up the template with all the required variables for
	 * issues/view.tpl to function properly.
	 * @param $issue object The issue to display
	 * @param $showToc boolean iff false and a custom cover page exists,
	 * 	the cover page will be displayed. Otherwise table of contents
	 * 	will be displayed.
	 */
	function setupIssueTemplate(&$issue, $showToc = false) {
		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
		$templateMgr = &TemplateManager::getManager();
		if (isset($issue) && ($issue->getPublished() || Validation::isEditor($journalId) || Validation::isLayoutEditor($journalId) || Validation::isProofreader($journalId)) && $issue->getJournalId() == $journalId) {

			$issueHeadingTitle = $issue->getIssueIdentification(false, true);
			$issueCrumbTitle = $issue->getIssueIdentification(false, true);

			if (!$showToc && $issue->getFileName() && $issue->getShowCoverPage()) {
				$templateMgr->assign('fileName', $issue->getFileName());
				$templateMgr->assign('width', $issue->getWidth());
				$templateMgr->assign('height', $issue->getHeight());
				$templateMgr->assign('originalFileName', $issue->getOriginalFileName());

				import('file.PublicFileManager');
				$publicFileManager = &new PublicFileManager();
				$coverPagePath = Request::getBaseUrl() . '/';
				$coverPagePath .= $publicFileManager->getJournalFilesPath($journalId) . '/';
				$coverPagePath .= $issue->getFileName();
				$templateMgr->assign('coverPagePath', $coverPagePath);

				$showToc = false;
			} else {
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId(), true);

				$templateMgr->assign('publishedArticles', $publishedArticles);
				$showToc = true;
			}
			$templateMgr->assign('showToc', $showToc);
			$templateMgr->assign('issueId', $issue->getBestIssueId());
			$templateMgr->assign('issue', $issue);

			// Subscription Access
			import('issue.IssueAction');
			$templateMgr->assign('subscriptionRequired', IssueAction::subscriptionRequired($issue));
			$templateMgr->assign('subscribedUser', IssueAction::subscribedUser($journal));
			$templateMgr->assign('subscribedDomain', IssueAction::subscribedDomain($journal));

		} else {
			$issueCrumbTitle = Locale::translate('archive.issueUnavailable');
			$issueHeadingTitle = Locale::translate('archive.issueUnavailable');
		}

		if ($styleFileName = $issue->getStyleFileName()) {
			import('file.PublicFileManager');
			$publicFileManager = &new PublicFileManager();
			$templateMgr->addStyleSheet(
				Request::getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journalId) . '/' . $styleFileName
			);
		}

		$templateMgr->assign('issueCrumbTitle', $issueCrumbTitle);
		$templateMgr->assign('issueHeadingTitle', $issueHeadingTitle);
	}

	/**
	 * Display the issue archive listings
	 */
	function archive() {
		parent::validate(true);

		$journal = &Request::getJournal();
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$rangeInfo = Handler::getRangeInfo('issues');

		$publishedIssuesIterator = $issueDao->getPublishedIssues($journal->getJournalId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $publishedIssuesIterator);
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		$templateMgr->display('issue/archive.tpl');
	}

}

?>
