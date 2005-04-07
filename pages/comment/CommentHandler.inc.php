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
		$commentId = isset($args[1]) ? (int) $args[1] : 0;

		CommentHandler::validate($articleId);

		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
                $journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$comment = &$commentDao->getComment($commentId, $articleId, 2);

		if (!$comment) $comments = &$commentDao->getRootCommentsByArticleId($articleId);
		else $comments = &$comment->getChildren();

		CommentHandler::setupTemplate();

		$templateMgr = &TemplateManager::getManager();
		if ($comment) {
			$templateMgr->assign('comment', &$comment);
			$templateMgr->assign('parent', $commentDao->getComment($comment->getParentCommentId(), $articleId));
		}
		$templateMgr->assign('comments', &$comments);
		$templateMgr->assign('journalRt', &$journalRt);
		$templateMgr->assign('articleId', $articleId);

		$templateMgr->display('comment/comments.tpl');
	}

	function add($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$parentId = isset($args[1]) ? (int) $args[1] : 0;

		CommentHandler::validate($articleId);

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$parent = &$commentDao->getComment($parentId, $articleId);
		if (isset($parent) && $parent->getArticleId() != $articleId) {
			Request::redirect('comment/view/' . $articleId);
		}

		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
                $journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		import('comment.form.CommentForm');
		$commentForm = new CommentForm(null, $articleId, isset($parent)?$parentId:null);

		if (isset($args[2]) && $args[2]=='save') {
			$commentForm->readInputData();
			$commentForm->execute();
			Request::redirect('comment/view/' . $articleId . '/' . $parentId);
		} else {
			CommentHandler::setupTemplate();
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
		
		if (!Validation::isLoggedIn() && $journalSettingsDao->getSetting($journalId,'restrictArticleAccess')) {
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

	}

	function setupTemplate() {
	}
}

?>
