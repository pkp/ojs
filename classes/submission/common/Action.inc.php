<?php

/**
 * Action.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Action class.
 *
 * $Id$
 */

/* These constants correspond to editing decision "decision codes". */
define('SUBMISSION_EDITOR_DECISION_ACCEPT', 1);
define('SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS', 2);
define('SUBMISSION_EDITOR_DECISION_RESUBMIT', 3);
define('SUBMISSION_EDITOR_DECISION_DECLINE', 4);

/* These constants are used as search fields for the various submission lists */
define('SUBMISSION_FIELD_AUTHOR', 1);
define('SUBMISSION_FIELD_EDITOR', 2);
define('SUBMISSION_FIELD_TITLE', 3);

define('SUBMISSION_FIELD_DATE_SUBMITTED', 4);
define('SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE', 5);
define('SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE', 6);
define('SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE', 7);

class Action {

	/**
	 * Constructor.
	 */
	function Action() {

	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * View metadata of an article.
	 * @param $article object
	 */
	function viewMetadata($article, $roleId) {
		import("submission.form.MetadataForm");
		$metadataForm = new MetadataForm($article, $roleId);
		$metadataForm->initData();
		$metadataForm->display();
	}
	
	/**
	 * Save metadata.
	 * @param $article object
	 */
	function saveMetadata($article) {
		import("submission.form.MetadataForm");
		$metadataForm = new MetadataForm($article);
		$metadataForm->readInputData();
		
		// Check for any special cases before trying to save
		if (Request::getUserVar('addAuthor')) {
			// Add an author
			$editData = true;
			$authors = $metadataForm->getData('authors');
			array_push($authors, array());
			$metadataForm->setData('authors', $authors);
			
		} else if (($delAuthor = Request::getUserVar('delAuthor')) && count($delAuthor) == 1) {
			// Delete an author
			$editData = true;
			list($delAuthor) = array_keys($delAuthor);
			$delAuthor = (int) $delAuthor;
			$authors = $metadataForm->getData('authors');
			if (isset($authors[$delAuthor]['authorId']) && !empty($authors[$delAuthor]['authorId'])) {
				$deletedAuthors = explode(':', $metadataForm->getData('deletedAuthors'));
				array_push($deletedAuthors, $authors[$delAuthor]['authorId']);
				$metadataForm->setData('deletedAuthors', join(':', $deletedAuthors));
			}
			array_splice($authors, $delAuthor, 1);
			$metadataForm->setData('authors', $authors);
					
			if ($metadataForm->getData('primaryContact') == $delAuthor) {
				$metadataForm->setData('primaryContact', 0);
			}
					
		} else if (Request::getUserVar('moveAuthor')) {
			// Move an author up/down
			$editData = true;
			$moveAuthorDir = Request::getUserVar('moveAuthorDir');
			$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
			$moveAuthorIndex = (int) Request::getUserVar('moveAuthorIndex');
			$authors = $metadataForm->getData('authors');
			
			if (!(($moveAuthorDir == 'u' && $moveAuthorIndex <= 0) || ($moveAuthorDir == 'd' && $moveAuthorIndex >= count($authors) - 1))) {
				$tmpAuthor = $authors[$moveAuthorIndex];
				if ($moveAuthorDir == 'u') {
					$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
					$authors[$moveAuthorIndex - 1] = $tmpAuthor;
				} else {
					$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
					$authors[$moveAuthorIndex + 1] = $tmpAuthor;
				}
			}
			$metadataForm->setData('authors', $authors);
		}
		
		if (isset($editData)) {
			$metadataForm->display();
			return false;
			
		} else {
			$metadataForm->execute();

			// Add log entry
			$user = &Request::getUser();
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($article->getArticleId(), ARTICLE_LOG_METADATA_UPDATE, ARTICLE_LOG_TYPE_DEFAULT, 0, 'log.editor.metadataModified', Array('editorName' => $user->getFullName()));

			return true;
		}
	}
	
	/**
	 * Download file.
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadFile($articleId, $fileId, $revision = null) {
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($articleId);
		return $articleFileManager->downloadFile($fileId, $revision);
	}
	
	/**
	 * View file.
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function viewFile($articleId, $fileId, $revision = null) {
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($articleId);
		return $articleFileManager->viewFile($fileId, $revision);
	}
	
	/**
	 *
	 * @param $type string the type of instructions (copy, layout, or proof).
	 */
	function instructions($type, $allowed = array('copy', 'layout', 'proof')) {
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		
		if (!in_array($type, $allowed)) {
			return false;
		}
		
		switch ($type) {
			case 'copy':
				$title = 'submission.copyedit.instructions';
				$instructions = $journal->getSetting('copyeditInstructions');
				break;
			case 'layout':
				$title = 'submission.layout.instructions';
				$instructions = $journal->getSetting('layoutInstructions');
				break;
			case 'proof':
				$title = 'submission.proofread.instructions';
				$instructions = $journal->getSetting('proofInstructions');
				break;
			default:
				return false;
		}
		
		$templateMgr->assign('pageTitle', $title);
		$templateMgr->assign('instructions', $instructions);
		$templateMgr->display('submission/instructions.tpl');
		
		return true;
	}
	
	/**
	 * Edit comment.
	 * @param $commentId int
	 */
	function editComment($article, $comment) {
		import("submission.form.comment.EditCommentForm");
		
		$commentForm = new EditCommentForm($article, $comment);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Save comment.
	 * @param $commentId int
	 */
	function saveComment($article, &$comment, $emailComment) {
		import("submission.form.comment.EditCommentForm");
		
		$commentForm = new EditCommentForm($article, $comment);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
			if ($emailComment) {
				$commentForm->email($commentForm->emailHelper());
			}
			
		} else {
			$commentForm->display();
		}
	}
	
	/**
	 * Email comment.
	 * @param $commentId int
	 */
	function emailComment($commentId) {
		$user = &Request::getUser();
	
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		$article = &$articleDao->getArticle($comment->getArticleId);
		
		// Just making sure that the person emailing these comments is the author
		if ($comment->getAuthorId() == $user->getUserId()) {
			import('mail.ArticleMailTemplate');
			$email = &new ArticleMailTemplate($comment->getArticleId(), 'SUBMISSION_COMMENT');
			$email->setFrom($user->getEmail(), $user->getFullName());
			
			// Email to various recipients, depending on comment type.
			$paramArray = array(
				'name' => $copyeditor->getFullName(),
				'commentName' => $user->getFullName(),
				'articleTitle' => $article->getArticleTitle(),
			);
		}
	}
	
	/**
	 * Delete comment.
	 * @param $commentId int
	 */
	function deleteComment($commentId) {
		$user = &Request::getUser();
	
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		if ($comment->getAuthorId() == $user->getUserId()) {
			$articleCommentDao->deleteArticleComment($comment);
		}
	}
}

?>
