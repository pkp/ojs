<?php

/**
 * @defgroup submission_common
 */

/**
 * @file classes/submission/common/Action.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Action
 * @ingroup submission_common
 *
 * @brief Application-specific submission actions.
 */

// Bring in the decision constants.
import('classes.workflow.EditorDecisionActionsManager');

/* These constants are used as search fields for the various submission lists */
define('SUBMISSION_FIELD_AUTHOR', 1);
define('SUBMISSION_FIELD_EDITOR', 2);
define('SUBMISSION_FIELD_TITLE', 3);
define('SUBMISSION_FIELD_REVIEWER', 4);
define('SUBMISSION_FIELD_COPYEDITOR', 5);
define('SUBMISSION_FIELD_LAYOUTEDITOR', 6);
define('SUBMISSION_FIELD_PROOFREADER', 7);
define('SUBMISSION_FIELD_ID', 8);

define('SUBMISSION_FIELD_DATE_SUBMITTED', 4);
define('SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE', 5);
define('SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE', 6);
define('SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE', 7);

import('lib.pkp.classes.submission.action.PKPAction');

class Action extends PKPAction {
	/**
	 * Constructor.
	 */
	function Action() {
		parent::PKPAction();
	}

	//
	// Actions.
	//
	/**
	 * View metadata of an article.
	 * @param $article object
	 */
	function viewMetadata($article, $journal) {
		if (!HookRegistry::call('Action::viewMetadata', array(&$article, &$journal))) {
			import('classes.submission.form.MetadataForm');
			$metadataForm = new MetadataForm($article, $journal);
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
	 * @param $request PKPRequest
	 */
	function saveMetadata($article, $request) {
		$router = $request->getRouter();
		if (!HookRegistry::call('Action::saveMetadata', array(&$article))) {
			import('classes.submission.form.MetadataForm');
			$journal = $request->getJournal();
			$metadataForm = new MetadataForm($article, $journal);
			$metadataForm->readInputData();

			if (isset($editData)) {
				$metadataForm->display();
				return false;

			} else {
				if (!$metadataForm->validate()) {
					return $metadataForm->display();
				}
				$metadataForm->execute($request);

				// Send a notification to associated users
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $article->getAssociatedUserIds();
				foreach ($notificationUsers as $userRole) {
					$notificationManager->createNotification(
						$request, $userRole['id'], NOTIFICATION_TYPE_METADATA_MODIFIED,
						$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
					);
				}

				// Add log entry
				$user = $request->getUser();
				import('lib.pkp.classes.log.SubmissionLog');
				import('classes.log.SubmissionEventLogEntry'); // constants
				SubmissionLog::logEvent($request, $article, SUBMISSION_LOG_METADATA_UPDATE, 'log.editor.metadataModified', array('editorName' => $user->getFullName()));

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
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($articleId);
		return $articleFileManager->downloadFile($fileId, $revision);
	}

	/**
	 * View file.
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function viewFile($articleId, $fileId, $revision = null) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($articleId);
		return $articleFileManager->downloadFile($fileId, $revision, true);
	}

	/**
	 * Display submission management instructions.
	 * @param $type string the type of instructions (copy, layout, or proof).
	 */
	function instructions($request, $type, $allowed = array('copy', 'layout', 'proof', 'referenceLinking')) {
		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);

		if (!HookRegistry::call('Action::instructions', array(&$type, &$allowed))) {
			if (!in_array($type, $allowed)) {
				return false;
			}

			AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
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
			import('classes.submission.form.comment.EditCommentForm');

			$commentForm = new EditCommentForm($article, $comment);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Save comment.
	 * @param $commentId int
	 */
	function saveComment($article, &$comment, $emailComment, $request) {
		if (!HookRegistry::call('Action::saveComment', array(&$article, &$comment, &$emailComment))) {
			import('classes.submission.form.comment.EditCommentForm');

			$commentForm = new EditCommentForm($article, $comment);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $article->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$notificationManager->createNotification(
						$request, $userRole['id'], NOTIFICATION_TYPE_SUBMISSION_COMMENT,
						$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
					);
				}

				if ($emailComment) {
					$commentForm->email($commentForm->emailHelper(), $request);
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
		if ($user == null) $user = Request::getUser();

		$submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');
		$comment = $submissionCommentDao->getArticleCommentById($commentId);

		if ($comment->getAuthorId() == $user->getId()) {
			if (!HookRegistry::call('Action::deleteComment', array(&$comment))) {
				$submissionCommentDao->deleteObject($comment);
			}
		}
	}
}

?>
