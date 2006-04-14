<?php

/**
 * EditorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * EditorAction class.
 *
 * $Id$
 */

import('submission.sectionEditor.SectionEditorAction');

class EditorAction extends SectionEditorAction {

	/**
	 * Constructor.
	 */
	function EditorAction() {

	}

	/**
	 * Actions.
	 */
	 
	/**
	 * Assigns a section editor to a submission.
	 * @param $articleId int
	 * @return boolean true iff ready for redirect
	 */
	function assignEditor($articleId, $sectionEditorId, $send = false) {
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$user = &Request::getUser();
		$journal = &Request::getJournal();

		$editorSubmission = &$editorSubmissionDao->getEditorSubmission($articleId);
		$sectionEditor = &$userDao->getUser($sectionEditorId);
		if (!isset($sectionEditor)) return true;

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($editorSubmission, 'EDITOR_ASSIGN');

		if ($user->getUserId() === $sectionEditorId || !$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('EditorAction::assignEditor', array(&$editorSubmission, &$sectionEditor, &$email));
			if ($email->isEnabled() && $user->getUserId() !== $sectionEditorId) {
				$email->setAssoc(ARTICLE_EMAIL_EDITOR_ASSIGN, ARTICLE_EMAIL_TYPE_EDITOR, $sectionEditor->getUserId());
				$email->send();
			}

			$editAssignment = &new EditAssignment();
			$editAssignment->setArticleId($articleId);
			$editAssignment->setCanEdit(1);
			$editAssignment->setCanReview(1);
		
			// Make the selected editor the new editor
			$editAssignment->setEditorId($sectionEditorId);
			$editAssignment->setDateNotified(Core::getCurrentDate());
			$editAssignment->setDateUnderway(null);
		
			$editAssignments =& $editorSubmission->getEditAssignments();
			array_push($editAssignments, $editAssignment);
			$editorSubmission->setEditAssignments($editAssignments);
		
			$editorSubmissionDao->updateEditorSubmission($editorSubmission);
		
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($articleId, ARTICLE_LOG_EDITOR_ASSIGN, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorId, 'log.editor.editorAssigned', array('editorName' => $sectionEditor->getFullName(), 'articleId' => $articleId));
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($sectionEditor->getEmail(), $sectionEditor->getFullName());
				$paramArray = array(
					'editorialContactName' => $sectionEditor->getFullName(),
					'editorUsername' => $sectionEditor->getUsername(),
					'editorPassword' => $sectionEditor->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, 'sectionEditor', 'submissionReview', $articleId),
					'submissionEditingUrl' => Request::url(null, 'sectionEditor', 'submissionReview', $articleId)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'assignEditor', 'send'), array('articleId' => $articleId, 'editorId' => $sectionEditorId));
			return false;
		}
	}

	/**
	 * Rush a new submission into the Scheduling queue.
	 * @param $article object
	 */
	function expediteSubmission($article) {
		$user =& Request::getUser();

		import('submission.editor.EditorAction');
		import('submission.sectionEditor.SectionEditorAction');
		import('submission.proofreader.ProofreaderAction');

		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($article->getArticleId());

		$submissionFile = $sectionEditorSubmission->getSubmissionFile();

		// Add a long entry before doing anything.
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($article->getArticleId(), ARTICLE_LOG_EDITOR_EXPEDITE, ARTICLE_LOG_TYPE_EDITOR, $user->getUserId(), 'log.editor.submissionExpedited', array('editorName' => $user->getFullName(), 'articleId' => $article->getArticleId()));

		// 1. Ensure that an editor is assigned.
		$editAssignments =& $sectionEditorSubmission->getEditAssignments();
		if (empty($editAssignments)) {
			// No editors are currently assigned; assign self.
			EditorAction::assignEditor($article->getArticleId(), $user->getUserId());
		}

		// 2. Accept the submission and send to copyediting.
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($article->getArticleId());
		if (!$sectionEditorSubmission->getCopyeditFile()) {
			SectionEditorAction::recordDecision($sectionEditorSubmission, SUBMISSION_EDITOR_DECISION_ACCEPT);
			$editorFile = $sectionEditorSubmission->getEditorFile();
			SectionEditorAction::setCopyeditFile($sectionEditorSubmission, $editorFile->getFileId(), $editorFile->getRevision());
		}

		// 3. Add a galley.
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($article->getArticleId());
		$galleys =& $sectionEditorSubmission->getGalleys();
		if (empty($galleys)) {
			// No galley present -- use copyediting file.
			import('file.ArticleFileManager');
			$copyeditFile =& $sectionEditorSubmission->getCopyeditFile();
			$fileType = $copyeditFile->getFileType();
			$articleFileManager =& new ArticleFileManager($article->getArticleId());
			$fileId = $articleFileManager->copyPublicFile($copyeditFile->getFilePath(), $fileType);

			if (strstr($fileType, 'html')) {
				$galley =& new ArticleHTMLGalley();
			} else {
				$galley =& new ArticleGalley();
			}
			$galley->setArticleId($article->getArticleId());
			$galley->setFileId($fileId);

			if ($galley->isHTMLGalley()) {
				$galley->setLabel('HTML');
			} else {
				if (strstr($fileType, 'pdf')) {
					$galley->setLabel('PDF');
				} else if (strstr($fileType, 'postscript')) {
					$galley->setLabel('Postscript');
				} else if (strstr($fileType, 'xml')) {
					$galley->setLabel('XML');
				} else {
					$galley->setLabel(Locale::translate('common.untitled'));
				}
			}

			$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
			$galleyDao->insertGalley($galley);
		}

		// 4. Send to scheduling
		ProofreaderAction::queueForScheduling($sectionEditorSubmission);
	}
}

?>
