<?php

/**
 * CopyeditorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * CopyeditorAction class.
 *
 * $Id$
 */

class CopyeditorAction extends Action {

	/**
	 * Constructor.
	 */
	function CopyeditorAction() {

	}
	
	/**
	 * Actions.
	 */
	
	/**
	 * Copyeditor completes initial copyedit.
	 * @param $articleId int
	 */
	function completeCopyedit($articleId, $send = false) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_COMP');
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);
		
		$editAssignment = $copyeditorSubmission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());
		
		if ($send) {
			$email->addRecipient($editor->getEmail(), $editor->getFullName());
			$email->setFrom($user->getFullName(), $user->getEmail());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$copyeditorSubmission->setDateCompleted(Core::getCurrentDate());
			$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
		} else {
			$paramArray = array(
				'editorialContactName' => $editor->getFullName(),
				'journalName' => $journal->getSetting('journalTitle'),
				'articleTitle' => $copyeditorSubmission->getArticleTitle(),
				'copyeditorName' => $user->getFullName()
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/copyeditor/completeCopyedit/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Copyeditor completes final copyedit.
	 * @param $articleId int
	 */
	function completeFinalCopyedit($articleId, $send = false) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_FINAL_REVIEW_COMP');
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);
		
		$editAssignment = $copyeditorSubmission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());
		
		if ($send) {
			$email->addRecipient($editor->getEmail(), $editor->getFullName());
			$email->setFrom($user->getFullName(), $user->getEmail());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$copyeditorSubmission->setDateFinalCompleted(Core::getCurrentDate());
			$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
		} else {
			$paramArray = array(
				'editorialContactName' => $editor->getFullName(),
				'journalName' => $journal->getSetting('journalTitle'),
				'articleTitle' => $copyeditorSubmission->getArticleTitle(),
				'copyeditorName' => $user->getFullName()
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/copyeditor/completeFinalCopyedit/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Set that the copyedit is underway.
	 */
	function copyeditUnderway($articleId) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');		
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);
		
		if ($copyeditorSubmission->getDateNotified() != null && $copyeditorSubmission->getDateUnderway() == null) {
			$copyeditorSubmission->setDateUnderway(Core::getCurrentDate());
		} elseif ($copyeditorSubmission->getDateFinalNotified() != null && $copyeditorSubmission->getDateFinalUnderway() == null) {
			$copyeditorSubmission->setDateFinalUnderway(Core::getCurrentDate());
		}
		
		$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
	}	
	
	/**
	 * Upload the copyeditted version of an article.
	 * @param $articleId int
	 */
	function uploadCopyeditVersion($articleId, $copyeditStage) {
		import("file.ArticleFileManager");
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');		
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);
		
		$articleFileManager = new ArticleFileManager($copyeditorSubmission->getArticleId());
		$user = &Request::getUser();
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($copyeditorSubmission->getCopyeditFileId() != null) {
				$fileId = $articleFileManager->uploadCopyeditorFile($fileName, $copyeditorSubmission->getCopyeditFileId());
			} else {
				$fileId = $articleFileManager->uploadCopyeditFile($fileName);
			}
		}
		
		$copyeditorSubmission->setCopyeditFileId($fileId);
		
		if ($copyeditStage == 'initial') {
			$copyeditorSubmission->setInitialRevision($articleFileDao->getRevisionNumber($fileId));
		} elseif ($copyeditStage == 'final') {
			$copyeditorSubmission->setFinalRevision($articleFileDao->getRevisionNumber($fileId));
		}

		$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);

		// Add log
		$entry = new ArticleEventLogEntry();
		$entry->setArticleId($copyeditorSubmission->getArticleId());
		$entry->setUserId($user->getUserId());
		$entry->setDateLogged(Core::getCurrentDate());
		$entry->setEventType(ARTICLE_LOG_COPYEDIT_COPYEDITOR_FILE);
		$entry->setLogMessage('log.copyedit.copyeditorFile');
		$entry->setAssocType(ARTICLE_LOG_TYPE_COPYEDIT);
		$entry->setAssocId($fileId);
			
		ArticleLog::logEventEntry($copyeditorSubmission->getArticleId(), $entry);
	}
}

?>
