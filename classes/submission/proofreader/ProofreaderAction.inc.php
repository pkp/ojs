<?php

/**
 * ProofreaderAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.proofreader.ProofreaderAction
 *
 * ProofreaderAction class.
 *
 * $Id$
 */

class ProofreaderAction extends Action {

	/**
	 * Select a proofreader for submission
	 */
	function selectProofreader($userId, $articleId) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setProofreaderId($userId);
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		// Add log entry
		$user = &Request::getUser();
		$userDao = &DAORegistry::getDAO('UserDAO');
		$proofreader = &$userDao->getUser($userId);
		ArticleLog::logEvent($articleId, ARTICLE_LOG_PROOFREAD_ASSIGN, ARTICLE_LOG_TYPE_PROOFREAD, $user->getUserId(), 'log.proofread.assign', Array('assignerName' => $user->getFullName(), 'proofreaderName' => $proofreader->getFullName(), 'articleId' => $articleId));
	}

	/**
	 * Queue the submission for scheduling
	 */
	function queueForScheduling($articleId) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		$article->setStatus(STATUS_SCHEDULED);
		$article->stampStatusModified();
		$articleDao->updateArticle($article);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateSchedulingQueue(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		// Add log entry
		$user = &Request::getUser();
		ArticleLog::logEvent($articleId, ARTICLE_LOG_PROOFREAD_COMPLETE, ARTICLE_LOG_TYPE_PROOFREAD, $user->getUserId(), 'log.proofread.complete', Array('proofreaderName' => $user->getFullName(), 'articleId' => $articleId));
	}

	/**
	 * Proofread Emails
	 * @param $articleId int
	 * @param $mailType defined string - type of proofread mail being sent
	 * @param $actionPath string - form action
	 */
	function proofreadEmail($articleId, $mailType, $actionPath = '') {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$useProofreaders = $journal->getSetting('useProofreaders');

		switch($mailType) {
			case 'PROOFREAD_AUTHOR_REQ':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_AUTHOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateAuthorNotified';
				$nullifyDateFields = array('setDateAuthorUnderway', 'setDateAuthorCompleted', 'setDateAuthorAcknowledged');
				$receiver = &$userDao->getUser($sectionEditorSubmission->getUserId());
				$addParamArray = array(
					'authorName' => $receiver->getFullName(),
					'authorUsername' => $receiver->getUsername(),
					'authorPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_AUTHOR_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_AUTHOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateAuthorAcknowledged';
				$receiver = &$userDao->getUser($sectionEditorSubmission->getUserId());
				$addParamArray = array(
					'authorName' => $receiver->getFullName(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_AUTHOR_COMP':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_AUTHOR_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateAuthorCompleted';
				$editor = $sectionEditorSubmission->getEditor();
				$ccReceiver = &$userDao->getUser($editor->getEditorId());
				$receiver = &$userDao->getUser($proofAssignment->getProofreaderId());
				if (!isset($receiver) || $receiver == null) {
					$receiver = $ccReceiver;
					$ccReceiver = null;
				} else {
					$setNextDateField = 'setDateProofreaderNotified';
				}

				$addParamArray = array(
					'editorialContactName' => $receiver->getFullName(),
					'authorName' => $user->getFullName()
				);
				break;
			
			case 'PROOFREAD_REQ':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_PROOFREADER;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateProofreaderNotified';
				$nullifyDateFields = array('setDateProofreaderUnderway', 'setDateProofreaderCompleted', 'setDateProofreaderAcknowledged');
				$receiver = &$userDao->getUser($proofAssignment->getProofreaderId());
				$addParamArray = array(
					'proofreaderName' => $receiver->getFullName(),
					'proofreaderUsername' => $receiver->getUsername(),
					'proofreaderPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_PROOFREADER;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateProofreaderAcknowledged';
				$receiver = &$userDao->getUser($proofAssignment->getProofreaderId());
				$addParamArray = array(
					'proofreaderName' => $receiver->getFullName(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_COMP':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_PROOFREADER_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateProofreaderCompleted';
				$setNextDateField = 'setDateLayoutEditorNotified';
				$editor = $sectionEditorSubmission->getEditor();
				$ccReceiver = &$userDao->getUser($editor->getEditorId());
				$layoutAssignment = $sectionEditorSubmission->getLayoutAssignment();
				$receiver = &$userDao->getUser($layoutAssignment->getEditorId());
				$addParamArray = array(
					'editorialContactName' => $receiver->getFullName(),
					'proofreaderName' => $user->getFullName()
				);
				break;

			case 'PROOFREAD_LAYOUTEDITOR_REQ':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_LAYOUTEDITOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateLayoutEditorNotified';
				$nullifyDateFields = array('setDateLayoutEditorUnderway', 'setDateLayoutEditorCompleted', 'setDateLayoutEditorAcknowledged');
				$layoutAssignment = $sectionEditorSubmission->getLayoutAssignment();
				$receiver = &$userDao->getUser($layoutAssignment->getEditorId());
				$addParamArray = array(
					'layoutEditorName' => $receiver->getFullName(),
					'layoutEditorUsername' => $receiver->getUsername(),
					'layoutEditorPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				
				if (!$actionPath) {
					// Reset underway/complete/thank dates
					$proofAssignment->setDateLayoutEditorUnderway(null);
					$proofAssignment->setDateLayoutEditorCompleted(null);
					$proofAssignment->setDateLayoutEditorAcknowledged(null);
				}
				break;

			case 'PROOFREAD_LAYOUTEDITOR_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_LAYOUTEDITOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateLayoutEditorAcknowledged';
				$layoutAssignment = $sectionEditorSubmission->getLayoutAssignment();
				$receiver = &$userDao->getUser($layoutAssignment->getEditorId());
				$addParamArray = array(
					'layoutEditorName' => $receiver->getFullName(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_LAYOUTEDITOR_COMP':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_LAYOUTEDITOR_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateLayoutEditorCompleted';
				$editor = $sectionEditorSubmission->getEditor();
				$receiver = &$userDao->getUser($editor->getEditorId());
				$addParamArray = array(
					'editorialContactName' => $receiver->getFullName(),
					'layoutEditorName' => $user->getFullName()
				);
				break;

			default:
				return;	
		}

		$email = &new ArticleMailTemplate($articleId, $mailType);
		$email->setFrom($user->getEmail(), $user->getFullName());

		if ($actionPath ||  $email->hasErrors()) {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($receiver->getEmail(), $receiver->getFullName());
				if (isset($ccReceiver)) {
					$email->addCc($ccReceiver->getEmail(), $ccReceiver->getFullName());
				}

				$paramArray = array(
					'articleTitle' => $sectionEditorSubmission->getArticleTitle()
				);
				if (isset($addParamArray)) {
					$paramArray += $addParamArray;
				}
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . $actionPath, array('articleId' => $articleId));
		} else {
			$email->setAssoc($eventType, $assocType, $articleId);
			$email->send();

			$proofAssignment->$setDateField(Core::getCurrentDate());
			if (isset($setNextDateField)) {
				$proofAssignment->$setNextDateField(Core::getCurrentDate());
			}
			if (isset($nullifyDateFields)) foreach ($nullifyDateFields as $fieldSetter) {
				$proofAssignment->$fieldSetter(null);
			}

			$proofAssignmentDao->updateProofAssignment($proofAssignment);
		}
	}

	/**
	 * Set date for author proofreading underway
	 * @param $articleId int
	 */
	function authorProofreadingUnderway($articleId) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);

		if (!$proofAssignment->getDateAuthorUnderway() && $proofAssignment->getDateAuthorNotified()) {
			$proofAssignment->setDateAuthorUnderway(Core::getCurrentDate());
		}

		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}

	/**
	 * Set date for proofreader proofreading underway
	 * @param $articleId int
	 */
	function proofreaderProofreadingUnderway($articleId) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);

		if (!$proofAssignment->getDateProofreaderUnderway() && $proofAssignment->getDateProofreaderNotified()) {
			$proofAssignment->setDateProofreaderUnderway(Core::getCurrentDate());
		}

		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}

	/**
	 * Set date for layout editor proofreading underway
	 * @param $articleId int
	 */
	function layoutEditorProofreadingUnderway($articleId) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);

		if (!$proofAssignment->getDateLayoutEditorUnderway() && $proofAssignment->getDateLayoutEditorNotified()) {
			$proofAssignment->setDateLayoutEditorUnderway(Core::getCurrentDate());
		}

		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}
	
	//
	// Misc
	//
	
	/**
	 * Download a file a proofreader has access to.
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadProofreaderFile($articleId, $fileId, $revision = null) {
		$submissionDao = &DAORegistry::getDAO('ProofreaderSubmissionDAO');		
		$submission = &$submissionDao->getSubmission($articleId);

		$canDownload = false;
		
		// Proofreaders have access to:
		// 1) All supplementary files.
		// 2) All galley files.

		// Check supplementary files
		foreach ($submission->getSuppFiles() as $suppFile) {
			if ($suppFile->getFileId() == $fileId) {
				$canDownload = true;
			}
		}
		
		// Check galley files
		foreach ($submission->getGalleys() as $galleyFile) {
			if ($galleyFile->getFileId() == $fileId) {
				$canDownload = true;
			}
		}
		
		if ($canDownload) {
			return Action::downloadFile($articleId, $fileId, $revision);
		} else {
			return false;
		}
	}
	
	/**
	 * View proofread comments.
	 * @param $articleId int
	 */
	function viewProofreadComments($articleId) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = new ProofreadCommentForm($articleId, ROLE_ID_PROOFREADER);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post proofread comment.
	 * @param $articleId int
	 * @param $emailComment boolean
	 */
	function postProofreadComment($articleId, $emailComment) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = new ProofreadCommentForm($articleId, ROLE_ID_PROOFREADER);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
			if ($emailComment) {
				$commentForm->email();
			}
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}
	
	/**
	 * View layout comments.
	 * @param $articleId int
	 */
	function viewLayoutComments($articleId) {
		import("submission.form.comment.LayoutCommentForm");
		
		$commentForm = new LayoutCommentForm($articleId, ROLE_ID_PROOFREADER);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post layout comment.
	 * @param $articleId int
	 * @param $emailComment boolean
	 */
	function postLayoutComment($articleId, $emailComment) {
		import("submission.form.comment.LayoutCommentForm");
		
		$commentForm = new LayoutCommentForm($articleId, ROLE_ID_PROOFREADER);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
			if ($emailComment) {
				$commentForm->email();
			}
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}
	
}

?>
