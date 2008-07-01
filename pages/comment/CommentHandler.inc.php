<?php

/**
 * @file CommentHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommentHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user comments.
 *
 */

// $Id$


import('rt.ojs.RTDAO');
import('rt.ojs.JournalRT');

class CommentHandler extends Handler {
	function view($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $issue, $article) = CommentHandler::validate($articleId);

		$user = &Request::getUser();
		$userId = isset($user)?$user->getUserId():null;

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$comment = &$commentDao->getComment($commentId, $articleId, 2);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$isManager = $roleDao->roleExists($journal->getJournalId(), $userId, ROLE_ID_JOURNAL_MANAGER);

		if (!$comment) $comments = &$commentDao->getRootCommentsByArticleId($articleId, 1);
		else $comments = &$comment->getChildren();

		CommentHandler::setupTemplate($article, $galleyId, $comment);

		$templateMgr = &TemplateManager::getManager();
		if (Request::getUserVar('refresh')) $templateMgr->setCacheability(CACHEABILITY_NO_CACHE);
		if ($comment) {
			$templateMgr->assign_by_ref('comment', $comment);
			$templateMgr->assign_by_ref('parent', $commentDao->getComment($comment->getParentCommentId(), $articleId));
		}
		$templateMgr->assign_by_ref('comments', $comments);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('enableComments', $journal->getSetting('enableComments'));
		$templateMgr->assign('isManager', $isManager);

		$templateMgr->display('comment/comments.tpl');
	}

	function add($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$parentId = isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $issue, $article) = CommentHandler::validate($articleId);

		// Bring in comment constants
		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$enableComments = $journal->getSetting('enableComments');
		switch ($enableComments) {
			case COMMENTS_UNAUTHENTICATED:
				break;
			case COMMENTS_AUTHENTICATED:
			case COMMENTS_ANONYMOUS:
				// The user must be logged in to post comments.
				if (!Request::getUser()) {
					Validation::redirectLogin();
				}
				break;
			default:
				// Comments are disabled.
				Validation::redirectLogin();
		}

		$parent = &$commentDao->getComment($parentId, $articleId);
		if (isset($parent) && $parent->getArticleId() != $articleId) {
			Request::redirect(null, null, 'view', array($articleId, $galleyId));
		}

		import('comment.form.CommentForm');
		$commentForm = &new CommentForm(null, $articleId, $galleyId, isset($parent)?$parentId:null);
		$commentForm->initData();

		if (isset($args[3]) && $args[3]=='save') {
			$commentForm->readInputData();
			if ($commentForm->validate()) {
				$commentForm->execute();
				Request::redirect(null, null, 'view', array($articleId, $galleyId, $parentId), array('refresh' => 1));
			}
		}

		CommentHandler::setupTemplate($article, $galleyId, $parent);
		$commentForm->display();
	}

	/**
	 * Delete the specified comment and all its children.
	 */
	function delete($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $issue, $article) = CommentHandler::validate($articleId);
		$user = &Request::getUser();
		$userId = isset($user)?$user->getUserId():null;

		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		if (!$roleDao->roleExists($journal->getJournalId(), $userId, ROLE_ID_JOURNAL_MANAGER)) {
			Request::redirect(null, 'index');
		}

		$comment = &$commentDao->getComment($commentId, $articleId, ARTICLE_COMMENT_RECURSE_ALL);
		if ($comment)$commentDao->deleteComment($comment);

		Request::redirect(null, null, 'view', array($articleId, $galleyId), array('refresh' => '1'));
	}

	/**
	 * Validation
	 */
	function validate($articleId) {

		parent::validate();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$article = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		// Bring in comment constants
		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$enableComments = $journal->getSetting('enableComments');

		if ((!Validation::isLoggedIn() && $journalSettingsDao->getSetting($journalId,'restrictArticleAccess')) || ($article && !$article->getEnableComments()) || ($enableComments != COMMENTS_ANONYMOUS && $enableComments != COMMENTS_AUTHENTICATED && $enableComments != COMMENTS_UNAUTHENTICATED)) {
			Validation::redirectLogin();
		}

		// Subscription Access
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = &$issueDao->getIssueByArticleId($articleId);

		if (isset($issue) && isset($article)) {
			import('issue.IssueAction');
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$subscribedUser = IssueAction::subscribedUser($journal, $issue->getIssueId(), $articleId);

			if (!(!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser)) {
				Request::redirect(null, 'index');
			}
		} else {
			Request::redirect(null, 'index');
		}

		return array(&$journal, &$issue, &$article);
	}

	function setupTemplate($article, $galleyId, $comment = null) {
		$templateMgr = &TemplateManager::getManager();
		$journal =& Request::getJournal();

		if (!$journal || !$journal->getSetting('restrictSiteAccess')) {
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}

		$pageHierarchy = array(
			array(
				Request::url(null, 'article', 'view', array(
					$article->getBestArticleId(Request::getJournal()), $galleyId
				)),
				String::stripUnsafeHtml($article->getArticleTitle()),
				true
			)
		);

		if ($comment) $pageHierarchy[] = array(Request::url(null, 'comment', 'view', array($article->getArticleId(), $galleyId)), 'comments.readerComments');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
