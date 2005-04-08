<?php

/**
 * CommentHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 *
 * Handle requests for user comments.
 *
 * $Id$
 */

import('rt.ojs.RTDAO');
import('rt.ojs.JournalRT');

class CommentHandler extends Handler {
	function view($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		$article = CommentHandler::validate($articleId);

		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
                $journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$comment = &$commentDao->getComment($commentId, $articleId, 2);

		if (!$comment) $comments = &$commentDao->getRootCommentsByArticleId($articleId);
		else $comments = &$comment->getChildren();

		CommentHandler::setupTemplate(&$article, $galleyId, $comment);

		$templateMgr = &TemplateManager::getManager();
		if ($comment) {
			$templateMgr->assign('comment', &$comment);
			$templateMgr->assign('parent', $commentDao->getComment($comment->getParentCommentId(), $articleId));
		}
		$templateMgr->assign('comments', &$comments);
		$templateMgr->assign('journalRt', &$journalRt);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);

		$templateMgr->display('comment/comments.tpl');
	}

	function add($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$parentId = isset($args[2]) ? (int) $args[2] : 0;

		$article = CommentHandler::validate($articleId);

		$journal = &Request::getJournal();
		$enableComments = $journal->getSetting('enableComments');
		switch ($enableComments) {
			case 'unauthenticated':
				break;
			case 'authenticated':
			case 'anonymous':
				// The user must be logged in to post comments.
				if (!Request::getUser()) {
					Validation::redirectLogin();
				}
				break;
			default:
				// Comments are disabled.
				Validation::redirectLogin();
		}

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$parent = &$commentDao->getComment($parentId, $articleId);
		if (isset($parent) && $parent->getArticleId() != $articleId) {
			Request::redirect('comment/view/' . $articleId . '/' . $galleyId);
		}

		$rtDao = &DAORegistry::getDAO('RTDAO');
                $journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		import('comment.form.CommentForm');
		$commentForm = new CommentForm(null, $articleId, $galleyId, isset($parent)?$parentId:null);

		if (isset($args[2]) && $args[2]=='save') {
			$commentForm->readInputData();
			$commentForm->execute();
			Request::redirect('comment/view/' . $articleId . '/' . $galleyId . '/' . $parentId);
		} else {
			CommentHandler::setupTemplate(&$article, $galleyId, $parent);
			$commentForm->display();
		}
	}

	/**
	 * Validation
	 */
	function validate($articleId) {

		parent::validate();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

		$enableComments = $journal->getSetting('enableComments');

		if (!Validation::isLoggedIn() && $journalSettingsDao->getSetting($journalId,'restrictArticleAccess') || ($enableComments != 'anonymous' && $enableComments != 'authenticated' && $enableComments != 'unauthenticated')) {
			Validation::redirectLogin();
		}

		// Subscription Access
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = &$issueDao->getIssueByArticleId($articleId);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$article = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		if (isset($issue) && isset($article)) {
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$subscribedUser = IssueAction::subscribedUser();

			if (!(!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser)) {
				Request::redirect('index');
			}
		} else {
			Request::redirect('index');
		}

		return $article;
	}

	function setupTemplate($article, $galleyId, $comment = null) {
		$templateMgr = &TemplateManager::getManager();

		$pageHierarchy = array(array('article/view/' . $article->getArticleId() . '/' . $galleyId, $article->getArticleTitle(), true));
		if ($comment) $pageHierarchy[] = array('comment/view/' . $article->getArticleId() . '/' . $galleyId, 'comments.readerComments');
		$templateMgr->assign('pageHierarchy', &$pageHierarchy);
	}
}

?>
