<?php

/**
 * @defgroup submission
 */
 
/**
 * @file classes/submission/common/Action.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Action
 * @ingroup submission
 *
 * @brief Action class.
 */

// $Id$


/* These constants correspond to editing decision "decision codes". */
define('SUBMISSION_EDITOR_DECISION_ACCEPT', 1);
define('SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS', 2);
define('SUBMISSION_EDITOR_DECISION_RESUBMIT', 3);
define('SUBMISSION_EDITOR_DECISION_DECLINE', 4);

/* These constants are used as search fields for the various submission lists */
define('SUBMISSION_FIELD_AUTHOR', 1);
define('SUBMISSION_FIELD_EDITOR', 2);
define('SUBMISSION_FIELD_TITLE', 3);
define('SUBMISSION_FIELD_REVIEWER', 4);
define('SUBMISSION_FIELD_COPYEDITOR', 5);
define('SUBMISSION_FIELD_LAYOUTEDITOR', 6);
define('SUBMISSION_FIELD_PROOFREADER', 7);

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
		if (!HookRegistry::call('Action::viewMetadata', array(&$article, &$roleId))) {
			import("submission.form.MetadataForm");
			$metadataForm = &new MetadataForm($article, $roleId);
			if ($metadataForm->getCanEdit() && $metadataForm->isLocaleResubmit()) {
				$metadataForm->readInputData();
			} else {
				$metadataForm->initData();
			}
			$metadataForm->display();
		}
	}

	/**
	 * Save metadata.
	 * @param $article object
	 */
	function saveMetadata($article) {
		if (!HookRegistry::call('Action::saveMetadata', array(&$article))) {
			import("submission.form.MetadataForm");
			$metadataForm = &new MetadataForm($article);
			$metadataForm->readInputData();

			if (!$metadataForm->validate()) {
				return $metadataForm->display();
			}

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
					$primaryContact = $metadataForm->getData('primaryContact');
					if ($moveAuthorDir == 'u') {
						$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
						$authors[$moveAuthorIndex - 1] = $tmpAuthor;
						if ($primaryContact == $moveAuthorIndex) {
							$metadataForm->setData('primaryContact', $moveAuthorIndex - 1);
						} else if ($primaryContact == ($moveAuthorIndex - 1)) {
							$metadataForm->setData('primaryContact', $moveAuthorIndex);
						}
					} else {
						$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
						$authors[$moveAuthorIndex + 1] = $tmpAuthor;
						if ($primaryContact == $moveAuthorIndex) {
							$metadataForm->setData('primaryContact', $moveAuthorIndex + 1);
						} else if ($primaryContact == ($moveAuthorIndex + 1)) {
							$metadataForm->setData('primaryContact', $moveAuthorIndex);
						}
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
	function instructions($type, $allowed = array('copy', 'layout', 'proof', 'referenceLinking')) {
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();

		if (!HookRegistry::call('Action::instructions', array(&$type, &$allowed))) {
			if (!in_array($type, $allowed)) {
				return false;
			}

			switch ($type) {
				case 'copy':
					$title = 'submission.copyedit.instructions';
					$instructions = $journal->getLocalizedSetting('copyeditInstructions');
					break;
				case 'layout':
					$title = 'submission.layout.instructions';
					$instructions = $journal->getLocalizedSetting('layoutInstructions');
					break;
				case 'proof':
					$title = 'submission.proofread.instructions';
					$instructions = $journal->getLocalizedSetting('proofInstructions');
					break;
				case 'referenceLinking':
					if (!$journal->getSetting('provideRefLinkInstructions')) return false;
					$title = 'submission.layout.referenceLinking';
					$instructions = $journal->getLocalizedSetting('refLinkInstructions');
					break;
				default:
					return false;
			}
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
		if (!HookRegistry::call('Action::editComment', array(&$article, &$comment))) {
			import("submission.form.comment.EditCommentForm");

			$commentForm = &new EditCommentForm($article, $comment);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Save comment.
	 * @param $commentId int
	 */
	function saveComment($article, &$comment, $emailComment) {
		if (!HookRegistry::call('Action::saveComment', array(&$article, &$comment, &$emailComment))) {
			import("submission.form.comment.EditCommentForm");

			$commentForm = &new EditCommentForm($article, $comment);
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
	}

	/**
	 * Delete comment.
	 * @param $commentId int
	 * @param $user object The user who owns the comment, or null to default to Request::getUser
	 */
	function deleteComment($commentId, $user = null) {
		if ($user == null) $user = &Request::getUser();

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);

		if ($comment->getAuthorId() == $user->getUserId()) {
			if (!HookRegistry::call('Action::deleteComment', array(&$comment))) {
				$articleCommentDao->deleteArticleComment($comment);
			}
		}
	}
}

?>
