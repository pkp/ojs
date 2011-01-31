<?php

/**
 * @defgroup submission_proofreader_ProofreaderAction
 */
 
/**
 * @file classes/submission/proofreader/ProofreaderAction.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProofreaderAction
 * @ingroup submission_proofreader_ProofreaderAction
 *
 * @brief ProofreaderAction class.
 */

// $Id$

import('classes.submission.common.Action');

class ProofreaderAction extends Action {

	/**
	 * Select a proofreader for submission
	 */
	function selectProofreader($userId, $article) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$proofSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $article->getId());

		if (!HookRegistry::call('ProofreaderAction::selectProofreader', array(&$userId, &$article))) {
			$proofSignoff->setUserId($userId);
			$signoffDao->updateObject($proofSignoff);

			// Add log entry
			$user =& Request::getUser();
			$userDao =& DAORegistry::getDAO('UserDAO');
			$proofreader =& $userDao->getUser($userId);
			if (!isset($proofreader)) return;
			import('classes.article.log.ArticleLog');
			import('classes.article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($article->getId(), ARTICLE_LOG_PROOFREAD_ASSIGN, ARTICLE_LOG_TYPE_PROOFREAD, $user->getId(), 'log.proofread.assign', Array('assignerName' => $user->getFullName(), 'proofreaderName' => $proofreader->getFullName(), 'articleId' => $article->getId()));
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
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& Request::getJournal();
		$user =& Request::getUser();
		$ccs = array();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($sectionEditorSubmission, $mailType);

		switch($mailType) {
			case 'PROOFREAD_AUTHOR_REQUEST':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_AUTHOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$signoffType = 'SIGNOFF_PROOFREADING_AUTHOR';
				$setDateField = 'setDateNotified';
				$nullifyDateFields = array('setDateUnderway', 'setDateCompleted', 'setDateAcknowledged');
				$setUserId = $sectionEditorSubmission->getUserId();
				$receiver =& $userDao->getUser($setUserId);
				$setUserId = $receiver;
				if (!isset($receiver)) return true;
				$receiverName = $receiver->getFullName();
				$receiverAddress = $receiver->getEmail();
				$email->ccAssignedEditingSectionEditors($sectionEditorSubmission->getArticleId());
				$addParamArray = array(
					'authorName' => $receiver->getFullName(),
					'authorUsername' => $receiver->getUsername(),
					'authorPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, 'author', 'submissionEditing', $articleId)
				);
				break;

			case 'PROOFREAD_AUTHOR_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_AUTHOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$signoffType = 'SIGNOFF_PROOFREADING_AUTHOR';
				$setDateField = 'setDateAcknowledged';
				$receiver =& $userDao->getUser($sectionEditorSubmission->getUserId());
				if (!isset($receiver)) return true;
				$receiverName = $receiver->getFullName();
				$receiverAddress = $receiver->getEmail();
				$email->ccAssignedEditingSectionEditors($sectionEditorSubmission->getArticleId());
				$addParamArray = array(
					'authorName' => $receiver->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				break;

			case 'PROOFREAD_AUTHOR_COMPLETE':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_AUTHOR_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$signoffType = 'SIGNOFF_PROOFREADING_AUTHOR';
				$setDateField = 'setDateCompleted';
				$getDateField = 'getDateCompleted';

				$editAssignments =& $sectionEditorSubmission->getEditAssignments();
				$nextSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);

				if ($nextSignoff->getUserId() != 0) {
					$setNextDateField = 'setDateNotified';
					$proofreader =& $userDao->getUser($nextSignoff->getUserId());

					$receiverName = $proofreader->getFullName();
					$receiverAddress = $proofreader->getEmail();

					$editorAdded = false;
					foreach ($editAssignments as $editAssignment) {
						if ($editAssignment->getIsEditor() || $editAssignment->getCanEdit()) {
							$ccs[$editAssignment->getEditorEmail()] = $editAssignment->getEditorFullName();
							$editorAdded = true;
						}
					}
					if (!$editorAdded) $ccs[$journal->getSetting('contactEmail')] = $journal->getSetting('contactName');
				} else {
					$editorAdded = false;
					$assignmentIndex = 0;
					foreach ($editAssignments as $editAssignment) {
						if ($editAssignment->getIsEditor() || $editAssignment->getCanEdit()) {
							if ($assignmentIndex++ == 0) {
								$receiverName = $editAssignment->getEditorFullName();
								$receiverAddress = $editAssignment->getEditorEmail();
							} else {
								$ccs[$editAssignment->getEditorEmail()] = $editAssignment->getEditorFullName();
							}
							$editorAdded = true;
						}
					}
					if (!$editorAdded) {
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
				$signoffType = 'SIGNOFF_PROOFREADING_PROOFREADER';
				$setDateField = 'setDateNotified';
				$nullifyDateFields = array('setDateUnderway', 'setDateCompleted', 'setDateAcknowledged');
				
				$receiver = $sectionEditorSubmission->getUserBySignoffType($signoffType);
				if (!isset($receiver)) return true;
				$receiverName = $receiver->getFullName();
				$receiverAddress = $receiver->getEmail();
				$email->ccAssignedEditingSectionEditors($sectionEditorSubmission->getArticleId());

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
				$signoffType = 'SIGNOFF_PROOFREADING_PROOFREADER';
				$setDateField = 'setDateAcknowledged';
			
				$receiver = $sectionEditorSubmission->getUserBySignoffType($signoffType);
				if (!isset($receiver)) return true;
				$receiverName = $receiver->getFullName();
				$receiverAddress = $receiver->getEmail();
				$email->ccAssignedEditingSectionEditors($sectionEditorSubmission->getArticleId());

				$addParamArray = array(
					'proofreaderName' => $receiverName,
					'editorialContactSignature' => $user->getContactSignature()
				);
				break;

			case 'PROOFREAD_COMPLETE':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_PROOFREADER_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$signoffType = 'SIGNOFF_PROOFREADING_PROOFREADER';
				$setDateField = 'setDateCompleted';
				$getDateField = 'getDateCompleted';
				
				$setNextDateField = 'setDateNotified';
				$nextSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);

				$editAssignments =& $sectionEditorSubmission->getEditAssignments();

				$receiver = null;

				$editorAdded = false;
				foreach ($editAssignments as $editAssignment) {
					if ($editAssignment->getIsEditor() || $editAssignment->getCanEdit()) {
						if ($receiver === null) {
							$receiver =& $userDao->getUser($editAssignment->getEditorId());
						} else {
							$ccs[$editAssignment->getEditorEmail()] = $editAssignment->getEditorFullName();
						}
						$editorAdded = true;
					}
				}
				if (isset($receiver)) {
					$receiverName = $receiver->getFullName();
					$receiverAddress = $receiver->getEmail();
				} else {
					$receiverAddress = $journal->getSetting('contactEmail');
					$receiverName =  $journal->getSetting('contactName');
				}
				if (!$editorAdded) {
					$ccs[$journal->getSetting('contactEmail')] = $journal->getSetting('contactName');
				}

				$addParamArray = array(
					'editorialContactName' => $receiverName,
					'proofreaderName' => $user->getFullName()
				);
				break;

			case 'PROOFREAD_LAYOUT_REQUEST':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_LAYOUTEDITOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$signoffType = 'SIGNOFF_PROOFREADING_LAYOUT';
				$setDateField = 'setDateNotified';
				$nullifyDateFields = array('setDateUnderway', 'setDateCompleted', 'setDateAcknowledged');

				$receiver = $sectionEditorSubmission->getUserBySignoffType($signoffType);
				if (!isset($receiver)) return true;
				$receiverName = $receiver->getFullName();
				$receiverAddress = $receiver->getEmail();
				$email->ccAssignedEditingSectionEditors($sectionEditorSubmission->getArticleId());

				$addParamArray = array(
					'layoutEditorName' => $receiverName,
					'layoutEditorUsername' => $receiver->getUsername(),
					'layoutEditorPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, 'proofreader', 'submission', $articleId)
				);

				if (!$actionPath) {
					// Reset underway/complete/thank dates
					$signoffReset = $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $articleId);
					$signoffReset->setDateUnderway(null);
					$signoffReset->setDateCompleted(null);
					$signoffReset->setDateAcknowledged(null);
				}
				break;

			case 'PROOFREAD_LAYOUT_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_LAYOUTEDITOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$signoffType = 'SIGNOFF_PROOFREADING_LAYOUT';
				$setDateField = 'setDateAcknowledged';

				$receiver = $sectionEditorSubmission->getUserBySignoffType($signoffType);
				if (!isset($receiver)) return true;
				$receiverName = $receiver->getFullName();
				$receiverAddress = $receiver->getEmail();
				$email->ccAssignedEditingSectionEditors($sectionEditorSubmission->getArticleId());

				$addParamArray = array(
					'layoutEditorName' => $receiverName,
					'editorialContactSignature' => $user->getContactSignature() 	
				);
				break;

			case 'PROOFREAD_LAYOUT_COMPLETE':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_LAYOUTEDITOR_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$signoffType = 'SIGNOFF_PROOFREADING_LAYOUT';
				$setDateField = 'setDateCompleted';
				$getDateField = 'getDateCompleted';

				$editAssignments =& $sectionEditorSubmission->getEditAssignments();
				$assignmentIndex = 0;
				$editorAdded = false;
				foreach ($editAssignments as $editAssignment) {
					if ($editAssignment->getIsEditor() || $editAssignment->getCanEdit()) {
						if ($assignmentIndex++ == 0) {
							$receiverName = $editAssignment->getEditorFullName();
							$receiverAddress = $editAssignment->getEditorEmail();
						} else {
							$ccs[$editAssignment->getEditorEmail()] = $editAssignment->getEditorFullName();
						}
						$editorAdded = true;
					}
				}
				if (!$editorAdded) {
					$receiverAddress = $journal->getSetting('contactEmail');
					$receiverName =  $journal->getSetting('contactName');
				}

				$addParamArray = array(
					'editorialContactName' => $receiverName,
					'layoutEditorName' => $user->getFullName()
				);
				break;

			default:
				return true;	
		}

		$signoff = $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $articleId);

		if (isset($getDateField)) {
			$date = $signoff->$getDateField();		
			if (isset($date)) {
				Request::redirect(null, null, 'submission', $articleId);
			}
		}

		if ($email->isEnabled() && ($actionPath || $email->hasErrors())) {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($receiverAddress, $receiverName);
				if (isset($ccs)) foreach ($ccs as $address => $name) {
					$email->addCc($address, $name);
				}

				$paramArray = array();

				if (isset($addParamArray)) {
					$paramArray += $addParamArray;
				}
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($actionPath, array('articleId' => $articleId));
			return false;
		} else {
			HookRegistry::call('ProofreaderAction::proofreadEmail', array(&$email, $mailType));
			if ($email->isEnabled()) {
				$email->setAssoc($eventType, $assocType, $articleId);
				$email->send();
			}

			$signoff->$setDateField(Core::getCurrentDate());
			if (isset($setNextDateField)) {
				$nextSignoff->$setNextDateField(Core::getCurrentDate());
			}
			if (isset($nullifyDateFields)) foreach ($nullifyDateFields as $fieldSetter) {
				$signoff->$fieldSetter(null);
			}
			
			$signoffDao->updateObject($signoff);
			if(isset($nextSignoff)) $signoffDao->updateObject($nextSignoff);
		
			return true;
		}

	}

	/**
	 * Set date for author/proofreader/LE proofreading underway
	 * @param $articleId int
	 * @param $signoffType int
	 */
	function proofreadingUnderway(&$submission, $signoffType) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff = $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $submission->getArticleId());

		if (!$signoff->getDateUnderway() && $signoff->getDateNotified() && !HookRegistry::call('ProofreaderAction::proofreadingUnderway', array(&$submission, &$signoffType))) {
			$dateUnderway = Core::getCurrentDate();
			$signoff->setDateUnderway($dateUnderway);
			$signoffDao->updateObject($signoff);
		}
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
			import('classes.submission.form.comment.ProofreadCommentForm');

			$commentForm = new ProofreadCommentForm($article, ROLE_ID_PROOFREADER);
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
			import('classes.submission.form.comment.ProofreadCommentForm');

			$commentForm = new ProofreadCommentForm($article, ROLE_ID_PROOFREADER);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('lib.pkp.classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $article->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, $userRole['role'], 'submissionEditing', $article->getId(), null, 'proofread');
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.proofreadComment',
						$article->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_PROOFREAD_COMMENT
					);
				}
				
				if ($emailComment) {
					$commentForm->email();
				}

			} else {
				$commentForm->display();
				return false;
			}
			return true;
		}
	}

	/**
	 * View layout comments.
	 * @param $article object
	 */
	function viewLayoutComments($article) {
		if (!HookRegistry::call('ProofreaderAction::viewLayoutComments', array(&$article))) {
			import('classes.submission.form.comment.LayoutCommentForm');

			$commentForm = new LayoutCommentForm($article, ROLE_ID_PROOFREADER);
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
			import('classes.submission.form.comment.LayoutCommentForm');

			$commentForm = new LayoutCommentForm($article, ROLE_ID_PROOFREADER);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();
								
				// Send a notification to associated users
				import('lib.pkp.classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $article->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, $userRole['role'], 'submissionEditing', $article->getId(), null, 'layout');
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.layoutComment',
						$article->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_LAYOUT_COMMENT
					);
				}
				
				if ($emailComment) {
					$commentForm->email();
				}

			} else {
				$commentForm->display();
				return false;
			}
			return true;
		}
	}

}

?>
