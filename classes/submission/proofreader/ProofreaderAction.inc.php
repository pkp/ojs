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

import('submission.common.Action');

class ProofreaderAction extends Action {

	/**
	 * Select a proofreader for submission
	 */
	function selectProofreader($userId, $article) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($article->getArticleId());

		if (!HookRegistry::call('ProofreaderAction::selectProofreader', array(&$userId, &$article, &$proofAssignment))) {
			$proofAssignment->setProofreaderId($userId);
			$proofAssignmentDao->updateProofAssignment($proofAssignment);

			// Add log entry
			$user = &Request::getUser();
			$userDao = &DAORegistry::getDAO('UserDAO');
			$proofreader = &$userDao->getUser($userId);
			if (!isset($proofreader)) return;
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($article->getArticleId(), ARTICLE_LOG_PROOFREAD_ASSIGN, ARTICLE_LOG_TYPE_PROOFREAD, $user->getUserId(), 'log.proofread.assign', Array('assignerName' => $user->getFullName(), 'proofreaderName' => $proofreader->getFullName(), 'articleId' => $article->getArticleId()));
		}
	}

	/**
	 * Queue the submission for scheduling
	 */
	function queueForScheduling($article) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($article->getArticleId());

		if (!HookRegistry::call('ProofreaderAction::queueForScheduling', array(&$article, &$proofAssignment))) {
			$article->setStatus(STATUS_SCHEDULED);
			$article->stampStatusModified();
			$articleDao->updateArticle($article);

			$proofAssignment->setDateSchedulingQueue(Core::getCurrentDate());
			$proofAssignmentDao->updateProofAssignment($proofAssignment);

			// Add log entry
			$user = &Request::getUser();
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($article->getArticleId(), ARTICLE_LOG_PROOFREAD_COMPLETE, ARTICLE_LOG_TYPE_PROOFREAD, $user->getUserId(), 'log.proofread.complete', Array('proofreaderName' => $user->getFullName(), 'articleId' => $article->getArticleId()));
		}
	}

	/**
	 * Proofread Emails
	 * @param $articleId int
	 * @param $mailType defined string - type of proofread mail being sent
	 * @param $actionPath string - form action
	 * @return true iff ready for a redirect
	 */
	function proofreadEmail($articleId, $mailType, $actionPath = '') {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$useProofreaders = $journal->getSetting('useProofreaders');

		switch($mailType) {
			case 'PROOFREAD_AUTHOR_REQUEST':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_AUTHOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateAuthorNotified';
				$nullifyDateFields = array('setDateAuthorUnderway', 'setDateAuthorCompleted', 'setDateAuthorAcknowledged');
				$receiver = &$userDao->getUser($sectionEditorSubmission->getUserId());
				if (!isset($receiver)) return true;
				$receiverName = $receiver->getFullName();
				$receiverAddress = $receiver->getEmail();
				$addParamArray = array(
					'authorName' => $receiver->getFullName(),
					'authorUsername' => $receiver->getUsername(),
					'authorPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, 'author', 'submission', $articleId)
				);
				break;

			case 'PROOFREAD_AUTHOR_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_AUTHOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateAuthorAcknowledged';
				$receiver = &$userDao->getUser($sectionEditorSubmission->getUserId());
				if (!isset($receiver)) return true;
				$receiverName = $receiver->getFullName();
				$receiverAddress = $receiver->getEmail();
				$addParamArray = array(
					'authorName' => $receiver->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				break;

			case 'PROOFREAD_AUTHOR_COMPLETE':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_AUTHOR_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateAuthorCompleted';
				$getDateField = 'getDateAuthorCompleted';

				$editor = $sectionEditorSubmission->getEditor();

				if ($proofAssignment->getProofreaderId() != 0) {
					$setNextDateField = 'setDateProofreaderNotified';

					$receiverName = $proofAssignment->getProofreaderFullName();
					$receiverAddress = $proofAssignment->getProofreaderEmail();

					if (isset($editor)) {
						$ccReceiverName = $editor->getEditorFullName();
						$ccReceiverAddress = $editor->getEditorEmail();
					} else {
						$ccReceiverAddress = $journal->getSetting('contactEmail');
						$ccReceiverName =  $journal->getSetting('contactName');
					}
				} else {
					if (isset($editor)) {
						$receiverName = $editor->getEditorFullName();
						$receiverAddress = $editor->getEditorEmail();
					} else {
						$receiverAddress = $journal->getSetting('contactEmail');
						$receiverName =  $journal->getSetting('contactName');
					}
				}

				$addParamArray = array(
					'editorialContactName' => $receiverName,
					'authorName' => $user->getFullName()
				);
				break;
			
			case 'PROOFREAD_REQUEST':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_PROOFREADER;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateProofreaderNotified';
				$nullifyDateFields = array('setDateProofreaderUnderway', 'setDateProofreaderCompleted', 'setDateProofreaderAcknowledged');

				$receiver = &$userDao->getUser($proofAssignment->getProofreaderId());
				if (!isset($receiver)) return true;
				$receiverName = $proofAssignment->getProofreaderFullName();
				$receiverAddress = $proofAssignment->getProofreaderEmail();

				$addParamArray = array(
					'proofreaderName' => $receiverName,
					'proofreaderUsername' => $receiver->getUsername(),
					'proofreaderPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, 'proofreader', 'submission', $articleId)
				);
				break;

			case 'PROOFREAD_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_PROOFREADER;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateProofreaderAcknowledged';

				$receiver = &$userDao->getUser($proofAssignment->getProofreaderId());
				if (!isset($receiver)) return true;
				$receiverName = $proofAssignment->getProofreaderFullName();
				$receiverAddress = $proofAssignment->getProofreaderEmail();

				$addParamArray = array(
					'proofreaderName' => $receiverName,
					'editorialContactSignature' => $user->getContactSignature()
				);
				break;

			case 'PROOFREAD_COMPLETE':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_PROOFREADER_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateProofreaderCompleted';
				$getDateField = 'getDateProofreaderCompleted';
				$setNextDateField = 'setDateLayoutEditorNotified';
				$editor =& $sectionEditorSubmission->getEditor();
				$layoutAssignment =& $sectionEditorSubmission->getLayoutAssignment();

				$receiver = &$userDao->getUser($layoutAssignment->getEditorId());
				if (isset($editor)) {
					if (isset($receiver)) {
						$receiverName = $receiver->getFullName();
						$receiverAddress = $receiver->getEmail();
					} else {
						$receiverAddress = $journal->getSetting('contactEmail');
						$receiverName =  $journal->getSetting('contactName');
					}
					$ccReceiverName = $editor->getEditorFullName();
					$ccReceiverAddress = $editor->getEditorEmail();
				} else {
					if (isset($receiver)) {
						$receiverName = $receiver->getFullName();
						$receiverAddress = $receiver->getEmail();
						$ccReceiverAddress = $journal->getSetting('contactEmail');
						$ccReceiverName =  $journal->getSetting('contactName');
					} else {
						$receiverAddress = $journal->getSetting('contactEmail');
						$receiverName =  $journal->getSetting('contactName');
					}
				}

				$addParamArray = array(
					'editorialContactName' => $receiverName,
					'proofreaderName' => $user->getFullName()
				);
				break;

			case 'PROOFREAD_LAYOUT_REQUEST':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_LAYOUTEDITOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateLayoutEditorNotified';
				$nullifyDateFields = array('setDateLayoutEditorUnderway', 'setDateLayoutEditorCompleted', 'setDateLayoutEditorAcknowledged');
				$layoutAssignment =& $sectionEditorSubmission->getLayoutAssignment();

				$receiver = &$userDao->getUser($layoutAssignment->getEditorId());
				if (!isset($receiver)) return true;
				$receiverName = $receiver->getFullName();
				$receiverAddress = $receiver->getEmail();

				$addParamArray = array(
					'layoutEditorName' => $receiverName,
					'layoutEditorUsername' => $receiver->getUsername(),
					'layoutEditorPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, 'proofreader', 'submission', $articleId)
				);
				
				if (!$actionPath) {
					// Reset underway/complete/thank dates
					$proofAssignment->setDateLayoutEditorUnderway(null);
					$proofAssignment->setDateLayoutEditorCompleted(null);
					$proofAssignment->setDateLayoutEditorAcknowledged(null);
				}
				break;

			case 'PROOFREAD_LAYOUT_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_LAYOUTEDITOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateLayoutEditorAcknowledged';
				$layoutAssignment =& $sectionEditorSubmission->getLayoutAssignment();

				$receiverName = $layoutAssignment->getEditorFullName();
				$receiverAddress = $layoutAssignment->getEditorEmail();

				$addParamArray = array(
					'layoutEditorName' => $receiverName,
					'editorialContactSignature' => $user->getContactSignature() 	
				);
				break;

			case 'PROOFREAD_LAYOUT_COMPLETE':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_LAYOUTEDITOR_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateLayoutEditorCompleted';
				$getDateField = 'getDateLayoutEditorCompleted';

				$editor = $sectionEditorSubmission->getEditor();
				$receiverName = $editor->getEditorFullName();
				$receiverAddress = $editor->getEditorEmail();

				$addParamArray = array(
					'editorialContactName' => $receiverName,
					'layoutEditorName' => $user->getFullName()
				);
				break;

			default:
				return true;	
		}

		if (isset($getDateField)) {
			$date = $proofAssignment->$getDateField();		
			if (isset($date)) {
				Request::redirect(null, null, 'submission', $articleId);
			}
		}

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, $mailType);

		if ($email->isEnabled() && ($actionPath || $email->hasErrors())) {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($receiverAddress, $receiverName);
				if (isset($ccReceiver)) {
					$email->addCc($ccReceiverAddress, $ccReceiverName);
				}

				$paramArray = array();

				if (isset($addParamArray)) {
					$paramArray += $addParamArray;
				}
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, $actionPath), array('articleId' => $articleId));
			return false;
		} else {
			HookRegistry::call('ProofreaderAction::proofreaderEmail', array(&$proofAssignment, &$email, $mailType));
			if ($email->isEnabled()) {
				$email->setAssoc($eventType, $assocType, $articleId);
				$email->send();
			}

			$proofAssignment->$setDateField(Core::getCurrentDate());
			if (isset($setNextDateField)) {
				$proofAssignment->$setNextDateField(Core::getCurrentDate());
			}
			if (isset($nullifyDateFields)) foreach ($nullifyDateFields as $fieldSetter) {
				$proofAssignment->$fieldSetter(null);
			}

			$proofAssignmentDao->updateProofAssignment($proofAssignment);
			return true;
		}
	}

	/**
	 * Set date for author proofreading underway
	 * @param $articleId int
	 */
	function authorProofreadingUnderway(&$submission) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($submission->getArticleId());

		if (!$proofAssignment->getDateAuthorUnderway() && $proofAssignment->getDateAuthorNotified() && !HookRegistry::call('ProofreaderAction::authorProofreadingUnderway', array(&$submission, &$proofAssignment))) {
			$dateUnderway = Core::getCurrentDate();
			$proofAssignment->setDateAuthorUnderway($dateUnderway);
			$authorProofAssignment = &$submission->getProofAssignment();
			$authorProofAssignment->setDateAuthorUnderway($dateUnderway);
		}

		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}

	/**
	 * Set date for proofreader proofreading underway
	 * @param $articleId int
	 */
	function proofreaderProofreadingUnderway(&$submission) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($submission->getArticleId());

		if (!$proofAssignment->getDateProofreaderUnderway() && $proofAssignment->getDateProofreaderNotified() && !HookRegistry::call('ProofreaderAction::proofreaderProofreadingUnderway', array(&$submission, &$proofAssignment))) {
			$dateUnderway = Core::getCurrentDate();
			$proofAssignment->setDateProofreaderUnderway($dateUnderway);
			$proofreaderProofAssignment = &$submission->getProofAssignment();
			$proofreaderProofAssignment->setDateProofreaderUnderway($dateUnderway);
		}

		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}

	/**
	 * Set date for layout editor proofreading underway
	 * @param $articleId int
	 */
	function layoutEditorProofreadingUnderway(&$submission) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($submission->getArticleId());

		if (!$proofAssignment->getDateLayoutEditorUnderway() && $proofAssignment->getDateLayoutEditorNotified() && !HookRegistry::call('ProofreaderAction::layoutEditorProofreadingUnderway', array(&$submission, &$proofAssignment))) {
			$dateUnderway = Core::getCurrentDate();
			$proofAssignment->setDateLayoutEditorUnderway($dateUnderway);
			$layoutEditorAssignment = &$submission->getProofAssignment();
			$layoutEditorAssignment->setDateLayoutEditorUnderway($dateUnderway);
		}

		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}
	
	//
	// Misc
	//
	
	/**
	 * Download a file a proofreader has access to.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadProofreaderFile($submission, $fileId, $revision = null) {
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

		$result = false;
		if (!HookRegistry::call('ProofreaderAction::downloadProofreaderFile', array(&$submission, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($submission->getArticleId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}
	
	/**
	 * View proofread comments.
	 * @param $article object
	 */
	function viewProofreadComments($article) {
		if (!HookRegistry::call('ProofreaderAction::viewProofreadComments', array(&$article))) {
			import("submission.form.comment.ProofreadCommentForm");
		
			$commentForm = &new ProofreadCommentForm($article, ROLE_ID_PROOFREADER);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Post proofread comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postProofreadComment($article, $emailComment) {
		if (!HookRegistry::call('ProofreaderAction::postProofreadComment', array(&$article, &$emailComment))) {
			import("submission.form.comment.ProofreadCommentForm");
		
			$commentForm = &new ProofreadCommentForm($article, ROLE_ID_PROOFREADER);
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
	
	/**
	 * View layout comments.
	 * @param $article object
	 */
	function viewLayoutComments($article) {
		if (!HookRegistry::call('ProofreaderAction::viewLayoutComments', array(&$article))) {
			import("submission.form.comment.LayoutCommentForm");
		
			$commentForm = &new LayoutCommentForm($article, ROLE_ID_PROOFREADER);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Post layout comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postLayoutComment($article, $emailComment) {
		if (!HookRegistry::call('ProofreaderAction::postLayoutComment', array(&$article, &$emailComment))) {
			import("submission.form.comment.LayoutCommentForm");

			$commentForm = &new LayoutCommentForm($article, ROLE_ID_PROOFREADER);
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
	
}

?>
