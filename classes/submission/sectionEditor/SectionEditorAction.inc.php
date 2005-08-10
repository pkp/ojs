<?php

/**
 * SectionEditorAction.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * SectionEditorAction class.
 *
 * $Id$
 */

import('submission.common.Action');

class SectionEditorAction extends Action {

	/**
	 * Constructor.
	 */
	function SectionEditorAction() {
		parent::Action();
	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Designates the original file the review version.
	 * @param $sectionEditorSubmission object
	 * @param $designate boolean
	 */
	function designateReviewVersion($sectionEditorSubmission, $designate = false) {
		import('file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		if ($designate) {
			$submissionFile = $sectionEditorSubmission->getSubmissionFile();
			$reviewFileId = $articleFileManager->copyToReviewFile($submissionFile->getFileId());
			
			// $editorFileId may or may not be null after assignment
			$editorFileId = $sectionEditorSubmission->getEditorFileId() != null ? $sectionEditorSubmission->getEditorFileId() : null;
			
			// $editorFileId definitely will not be null after assignment
			$editorFileId = $articleFileManager->copyToEditorFile($reviewFileId, null, $editorFileId);
			
			$sectionEditorSubmission->setReviewFileId($reviewFileId);
			$sectionEditorSubmission->setReviewRevision(1);
			$sectionEditorSubmission->setEditorFileId($editorFileId);
			
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}
	 
	/**
	 * Changes the section an article belongs in.
	 * @param $sectionEditorSubmission int
	 * @param $sectionId int
	 */
	function changeSection($sectionEditorSubmission, $sectionId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission->setSectionId($sectionId);
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	 
	/**
	 * Records an editor's submission decision.
	 * @param $sectionEditorSubmission object
	 * @param $decision int
	 */
	function recordDecision($sectionEditorSubmission, $decision) {
		if ($sectionEditorSubmission->getEditor() == null) return;

		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user = &Request::getUser();
		$editorDecision = array(
			'editDecisionId' => null,
			'editorId' => $user->getUserId(),
			'decision' => $decision,
			'dateDecided' => date(Core::getCurrentDate())
		);

		if ($decision == SUBMISSION_EDITOR_DECISION_DECLINE) {
			$sectionEditorSubmission->setStatus(STATUS_DECLINED);
			$sectionEditorSubmission->stampStatusModified();
		} else {
			$sectionEditorSubmission->setStatus(STATUS_QUEUED);		
			$sectionEditorSubmission->stampStatusModified();
		}
		
		$sectionEditorSubmission->addDecision($editorDecision, $sectionEditorSubmission->getCurrentRound());
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Assigns a reviewer to a submission.
	 * @param $sectionEditorSubmission object
	 * @param $reviewerId int
	 */
	function addReviewer($sectionEditorSubmission, $reviewerId, $round = null) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewer = &$userDao->getUser($reviewerId);

		// Check to see if the requested reviewer is not already
		// assigned to review this article.
		if ($round == null) {
			$round = $sectionEditorSubmission->getCurrentRound();
		}
		
		$assigned = $sectionEditorSubmissionDao->reviewerExists($sectionEditorSubmission->getArticleId(), $reviewerId, $round);
				
		// Only add the reviewer if he has not already
		// been assigned to review this article.
		if (!$assigned && isset($reviewer)) {
			$reviewAssignment = new ReviewAssignment();
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setDateAssigned(Core::getCurrentDate());
			$reviewAssignment->setRound($round);
			
			$sectionEditorSubmission->AddReviewAssignment($reviewAssignment);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		
			$reviewAssignment = $reviewAssignmentDao->getReviewAssignment($sectionEditorSubmission->getArticleId(), $reviewerId, $round);

			$journal = &Request::getJournal();
			$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$settings = &$settingsDao->getJournalSettings($journal->getJournalId());
			if (isset($settings['numWeeksPerReview'])) SectionEditorAction::setDueDate($sectionEditorSubmission->getArticleId(), $reviewAssignment->getReviewId(), null, $settings['numWeeksPerReview']);
			
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_REVIEW_ASSIGN, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId(), 'round' => $round));
		}
	}
	
	/**
	 * Clears a review assignment from a submission.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 */
	function clearReview($sectionEditorSubmission, $reviewId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if (isset($reviewAssignment) && $reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return false;
			$sectionEditorSubmission->removeReviewAssignment($reviewId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
			
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_REVIEW_CLEAR, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCleared', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId(), 'round' => $reviewAssignment->getRound()));
		}		
	}

	/**
	 * Notifies a reviewer about a review assignment.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function notifyReviewer($sectionEditorSubmission, $reviewId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		$isEmailBasedReview = $journal->getSetting('mailSubmissionsToReviewers')==1?true:false;

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, $isEmailBasedReview?'REVIEW_REQUEST_ATTACHED':'REVIEW_REQUEST', null, $isEmailBasedReview);

		if ($reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId() && $reviewAssignment->getReviewFileId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;
			
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				if ($email->isEnabled()) {
					$email->setAssoc(ARTICLE_EMAIL_REVIEW_NOTIFY_REVIEWER, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
					$email->send();
				}
				
				$reviewAssignment->setDateNotified(Core::getCurrentDate());
				$reviewAssignment->setCancelled(0);
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				return true;
			} else {
				if (!Request::getUserVar('continued')) {
					$weekLaterDate = date('Y-m-d', strtotime('+1 week'));
				
					if ($reviewAssignment->getDateDue() != null) {
						$reviewDueDate = date('Y-m-d', strtotime($reviewAssignment->getDateDue()));
					} else {
						$reviewDueDate = date('Y-m-d', strtotime('+2 week'));
					}
				
					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'weekLaterDate' => $weekLaterDate,
						'reviewDueDate' => $reviewDueDate,
						'reviewerUsername' => $reviewer->getUsername(),
						'reviewerPassword' => $reviewer->getPassword(),
						'editorialContactSignature' => $user->getContactSignature(),
						'reviewGuidelines' => $journal->getSetting('reviewGuidelines'),
						'submissionReviewUrl' => Request::getPageUrl() . '/reviewer/submission/' . $reviewId,
						'passwordResetUrl' => sprintf('%s/login/resetPassword/%s?confirm=%s', Request::getPageUrl(), $reviewer->getUsername(), Validation::generatePasswordResetHash($reviewer->getUserId()))
					);
					$email->assignParams($paramArray);
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
					if ($isEmailBasedReview) {
						// An email-based review process was selected. Attach
						// the current review version.
						import('file.TemporaryFileManager');
						$temporaryFileManager = new TemporaryFileManager();
						$reviewVersion = $sectionEditorSubmission->getReviewFile();
						if ($reviewVersion) {
							$temporaryFile = $temporaryFileManager->articleToTemporaryFile($reviewVersion, $user->getUserId());
							$email->addPersistAttachment($temporaryFile);
						}
					}
				}
				$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyReviewer', array('reviewId' => $reviewId, 'articleId' => $sectionEditorSubmission->getArticleId()));
				return false;
			}
		}
		return true;
	}

	/**
	 * Notifies all un-notified reviewer about a review assignment.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function notifyAllReviewers($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$isEmailBasedReview = $journal->getSetting('mailSubmissionsToReviewers')==1?true:false;

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, ($isEmailBasedReview?'REVIEW_REQ_MULTI_ATTACHED':'REVIEW_REQUEST_MULTIPLE'), null, $isEmailBasedReview);

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_REVIEW_NOTIFY_REVIEWER, ARTICLE_EMAIL_TYPE_DEFAULT, 0);
				$email->send();
			}
			
			$reviewAssignments = $reviewAssignmentDao->getReviewAssignmentsByArticleId($sectionEditorSubmission->getArticleId(), $sectionEditorSubmission->getCurrentRound());
			foreach ($reviewAssignments as $reviewAssignment) {
				if (!$reviewAssignment->getCancelled() && $reviewAssignment->getDateNotified()==null && $reviewAssignment->getReviewFileId()) {
					$reviewAssignment->setDateNotified(Core::getCurrentDate());
					$reviewAssignment->setCancelled(0);
					$reviewAssignment->stampModified();
					$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				}
			}
		} else {
			if (!Request::getUserVar('continued')) {
				$weekLaterDate = date('Y-m-d', strtotime('+1 week'));

				$email->addRecipient ($user->getEmail(), $user->getFullName());
				$paramArray = array(
					'weekLaterDate' => $weekLaterDate,
					'editorialContactSignature' => $user->getContactSignature(),
					'reviewGuidelines' => $journal->getSetting('reviewGuidelines')
				);
				$email->assignParams($paramArray);

				$reviewAssignments = $reviewAssignmentDao->getReviewAssignmentsByArticleId($sectionEditorSubmission->getArticleId(), $sectionEditorSubmission->getCurrentRound());
				foreach ($reviewAssignments as $reviewAssignment) {
					if (!$reviewAssignment->getCancelled() && $reviewAssignment->getDateNotified()==null && $reviewAssignment->getReviewFileId()) {
						$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
						if (isset($reviewer)) $email->addBcc($reviewer->getEmail(), $reviewer->getFullName());
					}
				}

				if ($isEmailBasedReview) {
					// An email-based review process was selected. Attach
					// the current review version.
					import('file.TemporaryFileManager');
					$temporaryFileManager = new TemporaryFileManager();
					$reviewVersion = $sectionEditorSubmission->getReviewFile();
					if ($reviewVersion) {
						$temporaryFile = $temporaryFileManager->articleToTemporaryFile($reviewVersion, $user->getUserId());
						$email->addPersistAttachment($temporaryFile);
					}
				}
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyAllReviewers', array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Cancels a review.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function cancelReview($sectionEditorSubmission, $reviewId, $send = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		if ($reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId()) {
			// Only cancel the review if it is currently not cancelled but has previously
			// been initiated, and has not been completed.
			if ($reviewAssignment->getDateNotified() != null && !$reviewAssignment->getCancelled() && $reviewAssignment->getDateCompleted() == null) {
				import('mail.ArticleMailTemplate');
				$email = &new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_CANCEL');

				if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
					if ($email->isEnabled()) {
						$email->setAssoc(ARTICLE_EMAIL_REVIEW_CANCEL, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
						$email->send();
					}

					$reviewAssignment->setCancelled(1);
					$reviewAssignment->stampModified();
				
					$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

					// Add log
					import('article.log.ArticleLog');
					import('article.log.ArticleEventLogEntry');
					ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_REVIEW_CANCEL, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCancelled', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId(), 'round' => $reviewAssignment->getRound()));
				} else {
					if (!Request::getUserVar('continued')) {
						$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

						$paramArray = array(
							'reviewerName' => $reviewer->getFullName(),
							'reviewerUsername' => $reviewer->getUsername(),
							'reviewerPassword' => $reviewer->getPassword(),
							'editorialContactSignature' => $user->getContactSignature()
						);
						$email->assignParams($paramArray);
					}
					$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/cancelReview/send', array('reviewId' => $reviewId, 'articleId' => $sectionEditorSubmission->getArticleId()));
					return false;
				}
			}				
		}
		return true;
	}
	
	/**
	 * Reminds a reviewer about a review assignment.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff no error was encountered
	 */
	function remindReviewer($sectionEditorSubmission, $reviewId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
			
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_REMIND');

		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_REVIEW_REMIND, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
			
			$email->send();

			$reviewAssignment->setDateReminded(Core::getCurrentDate());
			$reviewAssignment->setReminderWasAutomatic(0);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			return true;
		} elseif ($reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
			if (!Request::getUserVar('continued')) {
				if (!isset($reviewer)) return false;
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

				
				//
				// FIXME: Assign correct values!
				//
				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'reviewerUsername' => $reviewer->getUsername(),
					'reviewerPassword' => $reviewer->getPassword(),
					'reviewDueDate' => date('Y-m-d', strtotime($reviewAssignment->getDateDue())),
					'editorialContactSignature' => $user->getContactSignature(),
					'passwordResetUrl' => sprintf('%s/login/resetPassword/%s?confirm=%s', Request::getPageUrl(), $reviewer->getUsername(), Validation::generatePasswordResetHash($reviewer->getUserId())),
					'submissionReviewUrl' => Request::getPageUrl() . '/reviewer/submission/' . $reviewId
				);
				$email->assignParams($paramArray);
	
			}
			$email->displayEditForm(
				Request::getPageUrl() . '/' . Request::getRequestedPage() . '/remindReviewer/send',
				array(
					'reviewerId' => $reviewer->getUserId(),
					'articleId' => $sectionEditorSubmission->getArticleId(),
					'reviewId' => $reviewId
				)
			);
			return false;
		}
		return true;
	}
	
	/**
	 * Thanks a reviewer for completing a review assignment.
	 * @param $sectionEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function thankReviewer($sectionEditorSubmission, $reviewId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_ACK');

		if ($reviewAssignment->getArticleId() == $sectionEditorSubmission->getArticleId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;
			
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				if ($email->isEnabled()) {
					$email->setAssoc(ARTICLE_EMAIL_REVIEW_THANK_REVIEWER, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
					$email->send();
				}
				
				$reviewAssignment->setDateAcknowledged(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			} else {
				if (!Request::getUserVar('continued')) {
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'editorialContactSignature' => $user->getContactSignature()
					);
					$email->assignParams($paramArray);
				}
				$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankReviewer/send', array('reviewId' => $reviewId, 'articleId' => $sectionEditorSubmission->getArticleId()));
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Rates a reviewer for quality of a review.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $quality int
	 */
	function rateReviewer($articleId, $reviewId, $quality = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			// Ensure that the value for quality
			// is between 1 and 5.
			if ($quality != null && ($quality >= 1 && $quality <= 5)) {
				$reviewAssignment->setQuality($quality);
			}

			$reviewAssignment->setDateRated(Core::getCurrentDate());
			$reviewAssignment->stampModified();

			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_RATE, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerRated', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
		}
	}
	
	/**
	 * Makes a reviewer's annotated version of an article available to the author.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $viewable boolean
	 */
	function makeReviewerFileViewable($articleId, $reviewId, $fileId, $revision, $viewable = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$articleFile = &$articleFileDao->getArticleFile($fileId, $revision);
		
		if ($reviewAssignment->getArticleId() == $articleId && $reviewAssignment->getReviewerFileId() == $fileId) {
			$articleFile->setViewable($viewable);
			$articleFileDao->updateArticleFile($articleFile);				
		}
	}
	
	/**
	 * Sets the due date for a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $dueDate string
	 * @param $numWeeks int
	 */
	 function setDueDate($articleId, $reviewId, $dueDate = null, $numWeeks = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			if ($dueDate != null) {
				$dueDateParts = explode('-', $dueDate);
				$today = getDate();
				
				// Ensure that the specified due date is today or after today's date.
				if ($dueDateParts[0] >= $today['year'] && ($dueDateParts[1] > $today['mon'] || ($dueDateParts[1] == $today['mon'] && $dueDateParts[2] >= $today['mday']))) {
					$reviewAssignment->setDateDue(date('Y-m-d H:i:s', mktime(0, 0, 0, $dueDateParts[1], $dueDateParts[2], $dueDateParts[0])));
				}
				else {
					$today = getDate();
					$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
					$reviewAssignment->setDateDue(date('Y-m-d H:i:s', $todayTimestamp));
				}
			} else {
				$today = getDate();
				$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
				
				// Add the equivilant of $numWeeks weeks, measured in seconds, to $todaysTimestamp.
				$newDueDateTimestamp = $todayTimestamp + ($numWeeks * 7 * 24 * 60 * 60);

				$reviewAssignment->setDateDue(date('Y-m-d H:i:s', $newDueDateTimestamp));
			}
		
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_SET_DUE_DATE, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewDueDateSet', array('reviewerName' => $reviewer->getFullName(), 'dueDate' => date('Y-m-d', strtotime($reviewAssignment->getDateDue())), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
		}
	 }
	 
	/**
	 * Notifies an author about the editor review.
	 * @param $sectionEditorSubmission object
	 * FIXME: Still need to add Reviewer Comments
	 */
	function notifyAuthor($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'EDITOR_REVIEW');

		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		if (!isset($author)) return false;

		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_EDITOR_NOTIFY_AUTHOR, ARTICLE_EMAIL_TYPE_EDITOR, $sectionEditorSubmission->getArticleId());
			$email->send();
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'authorUsername' => $author->getUsername(),
					'authorPassword' => $author->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::getPageUrl() . '/author/submissionEditing/' . $sectionEditorSubmission->getArticleId()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyAuthor', array('articleId' => $sectionEditorSubmission->getArticleId()));
		}
	}
	 
	/**
	 * Sets the reviewer recommendation for a review assignment.
	 * Also concatenates the reviewer and editor comments from Peer Review and adds them to Editor Review.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $recommendation int
	 */
	 function setReviewerRecommendation($articleId, $reviewId, $recommendation, $acceptOption) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId(), true);
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			$reviewAssignment->setRecommendation($recommendation);
			$reviewAssignment->setDateCompleted(Core::getCurrentDate());
			$reviewAssignment->stampModified();
		
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_RECOMMENDATION, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewRecommendationSet', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
		}
	 }
	 
	/**
	 * Set the file to use as the default copyedit file.
	 * @param $sectionEditorSubmission object
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function setCopyeditFile($sectionEditorSubmission, $fileId, $revision) {
		import('file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$user = &Request::getUser();
		
		// Copy the file from the editor decision file folder to the copyedit file folder
		$newFileId = $articleFileManager->copyToCopyeditFile($fileId, $revision);
			
		$sectionEditorSubmission->setCopyeditFileId($newFileId);
		$sectionEditorSubmission->setCopyeditorInitialRevision(1);

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		
		// Add log
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_SET_FILE, ARTICLE_LOG_TYPE_COPYEDIT, $sectionEditorSubmission->getCopyeditFileId(), 'log.copyedit.copyeditFileSet');
	}
	
	/**
	 * Resubmit the file for review.
	 * @param $sectionEditorSubmission object
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function resubmitFile($sectionEditorSubmission, $fileId, $revision) {
		import('file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$user = &Request::getUser();
		
		// Increment the round
		$currentRound = $sectionEditorSubmission->getCurrentRound();
		$sectionEditorSubmission->setCurrentRound($currentRound + 1);
		$sectionEditorSubmission->stampStatusModified();
		
		// Copy the file from the editor decision file folder to the review file folder
		$newFileId = $articleFileManager->copyToReviewFile($fileId, $revision, $sectionEditorSubmission->getReviewFileId());
		$newReviewFile = $articleFileDao->getArticleFile($newFileId);
		$newReviewFile->setRound($sectionEditorSubmission->getCurrentRound());
		$articleFileDao->updateArticleFile($newReviewFile);

		// Copy the file from the editor decision file folder to the next-round editor file
		// $editorFileId may or may not be null after assignment
		$editorFileId = $sectionEditorSubmission->getEditorFileId() != null ? $sectionEditorSubmission->getEditorFileId() : null;

		// $editorFileId definitely will not be null after assignment
		$editorFileId = $articleFileManager->copyToEditorFile($newFileId, null, $editorFileId);
		$newEditorFile = $articleFileDao->getArticleFile($editorFileId);
		$newEditorFile->setRound($sectionEditorSubmission->getCurrentRound());
		$articleFileDao->updateArticleFile($newEditorFile);

		// The review revision is the highest revision for the review file.
		$reviewRevision = $articleFileDao->getRevisionNumber($newFileId);
		$sectionEditorSubmission->setReviewRevision($reviewRevision);
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

		// Now, reassign all reviewers that submitted a review for this new round of reviews.
		$previousRound = $sectionEditorSubmission->getCurrentRound() - 1;
		foreach ($sectionEditorSubmission->getReviewAssignments($previousRound) as $reviewAssignment) {
			if ($reviewAssignment->getRecommendation() != null) {
				// Then this reviewer submitted a review.
				SectionEditorAction::addReviewer($sectionEditorSubmission, $reviewAssignment->getReviewerId(), $sectionEditorSubmission->getCurrentRound());
			}
		}
		
		
		// Add log
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_REVIEW_RESUBMIT, ARTICLE_LOG_TYPE_EDITOR, $user->getUserId(), 'log.review.resubmitted', array('articleId' => $sectionEditorSubmission->getArticleId()));
	}
	 
	/**
	 * Assigns a copyeditor to a submission.
	 * @param $sectionEditorSubmission object
	 * @param $copyeditorId int
	 */
	function selectCopyeditor($sectionEditorSubmission, $copyeditorId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		// Check to see if the requested copyeditor is not already
		// assigned to copyedit this article.
		$assigned = $sectionEditorSubmissionDao->copyeditorExists($sectionEditorSubmission->getArticleId(), $copyeditorId);
		
		// Only add the copyeditor if he has not already
		// been assigned to review this article.
		if (!$assigned) {
			$sectionEditorSubmission->setCopyeditorId($copyeditorId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);	
			
			$copyeditor = &$userDao->getUser($copyeditorId);
		
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_ASSIGN, ARTICLE_LOG_TYPE_COPYEDIT, $copyeditorId, 'log.copyedit.copyeditorAssigned', array('copyeditorName' => $copyeditor->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId()));
		}
	}
	
	/**
	 * Notifies a copyeditor about a copyedit assignment.
	 * @param $sectionEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function notifyCopyeditor($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_REQUEST');

		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		if (!isset($copyeditor)) return true;
		
		if ($sectionEditorSubmission->getInitialCopyeditFile() && (!$email->isEnabled() || ($send && !$email->hasErrors()))) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_COPYEDITOR, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}
				
			$sectionEditorSubmission->setCopyeditorDateNotified(Core::getCurrentDate());
			$sectionEditorSubmission->setCopyeditorDateUnderway(null);
			$sectionEditorSubmission->setCopyeditorDateCompleted(null);
			$sectionEditorSubmission->setCopyeditorDateAcknowledged(null);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'copyeditorUsername' => $copyeditor->getUsername(),
					'copyeditorPassword' => $copyeditor->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionCopyeditingUrl' => Request::getPageUrl() . '/copyeditor/submission/' . $sectionEditorSubmission->getArticleId()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyCopyeditor/send', array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Initiates the initial copyedit stage when the editor does the copyediting.
	 * @param $sectionEditorSubmission object
	 */
	function initiateCopyedit($sectionEditorSubmission) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		// Only allow copyediting to be initiated if a copyedit file exists.
		if ($sectionEditorSubmission->getInitialCopyeditFile()) {
			$sectionEditorSubmission->setCopyeditorDateNotified(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}
	
	/**
	 * Thanks a copyeditor about a copyedit assignment.
	 * @param $sectionEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function thankCopyeditor($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_ACK');
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		if (!isset($copyeditor)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}
				
			$sectionEditorSubmission->setCopyeditorDateAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankCopyeditor/send', array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Notifies the author that the copyedit is complete.
	 * @param $sectionEditorSubmission object
	 * @return true iff ready for redirect
	 */
	function notifyAuthorCopyedit($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_AUTHOR_REQUEST');
		
		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		if (!isset($author)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}
				
			$sectionEditorSubmission->setCopyeditorDateAuthorNotified(Core::getCurrentDate());
			$sectionEditorSubmission->setCopyeditorDateAuthorUnderway(null);
			$sectionEditorSubmission->setCopyeditorDateAuthorCompleted(null);
			$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged(null);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'authorUsername' => $author->getUsername(),
					'authorPassword' => $author->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionCopyeditingUrl' => Request::getPageUrl() . '/author/submission/' . $sectionEditorSubmission->getArticleId()
					
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyAuthorCopyedit/send', array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Thanks an author for completing editor / author review.
	 * @param $sectionEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function thankAuthorCopyedit($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_AUTHOR_ACK');
		
		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		if (!isset($author)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}
				
			$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankAuthorCopyedit/send', array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Notify copyeditor about final copyedit.
	 * @param $sectionEditorSubmission object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function notifyFinalCopyedit($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_FINAL_REQUEST');
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		if (!isset($copyeditor)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}
				
			$sectionEditorSubmission->setCopyeditorDateFinalNotified(Core::getCurrentDate());
			$sectionEditorSubmission->setCopyeditorDateFinalUnderway(null);
			$sectionEditorSubmission->setCopyeditorDateFinalCompleted(null);
			$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged(null);
			
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'copyeditorUsername' => $copyeditor->getUsername(),
					'copyeditorPassword' => $copyeditor->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionCopyeditingUrl' => Request::getPageUrl() . '/copyeditor/submission/' . $sectionEditorSubmission->getArticleId()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyFinalCopyedit/send', array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Thank copyeditor for completing final copyedit.
	 * @param $sectionEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function thankFinalCopyedit($sectionEditorSubmission, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_FINAL_ACK');
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		if (!isset($copyeditor)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getArticleId());
				$email->send();
			}
				
			$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankFinalCopyedit/send', array('articleId' => $sectionEditorSubmission->getArticleId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Upload the review version of an article.
	 * @param $sectionEditorSubmission object
	 */
	function uploadReviewVersion($sectionEditorSubmission) {
		import('file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($sectionEditorSubmission->getReviewFileId() != null) {
				$reviewFileId = $articleFileManager->uploadReviewFile($fileName, $sectionEditorSubmission->getReviewFileId());
				// Increment the review revision.
				$sectionEditorSubmission->setReviewRevision($sectionEditorSubmission->getReviewRevision()+1);
			} else {
				$reviewFileId = $articleFileManager->uploadReviewFile($fileName);
				$sectionEditorSubmission->setReviewRevision(1);
			}
			$editorFileId = $articleFileManager->copyToEditorFile($reviewFileId, $sectionEditorSubmission->getReviewRevision(), $sectionEditorSubmission->getEditorFileId());
		}
		
		if (isset($reviewFileId) && $reviewFileId != 0 && isset($editorFileId) && $editorFileId != 0) {
			$sectionEditorSubmission->setReviewFileId($reviewFileId);
			$sectionEditorSubmission->setEditorFileId($editorFileId);
	
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}

	/**
	 * Upload the post-review version of an article.
	 * @param $sectionEditorSubmission object
	 */
	function uploadEditorVersion($sectionEditorSubmission) {
		import('file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user = &Request::getUser();
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($sectionEditorSubmission->getEditorFileId() != null) {
				$fileId = $articleFileManager->uploadEditorDecisionFile($fileName, $sectionEditorSubmission->getEditorFileId());
			} else {
				$fileId = $articleFileManager->uploadEditorDecisionFile($fileName);
			}
		}
		
		if (isset($fileId) && $fileId != 0) {
			$sectionEditorSubmission->setEditorFileId($fileId);

			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
			
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_EDITOR_FILE, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorSubmission->getEditorFileId(), 'log.editor.editorFile');
		}
	}
	
	/**
	 * Upload the copyedit version of an article.
	 * @param $sectionEditorSubmission object
	 * @param $copyeditStage string
	 */
	function uploadCopyeditVersion($sectionEditorSubmission, $copyeditStage) {
		import('file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getArticleId());
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		// Perform validity checks.
		if ($copyeditStage == 'initial' && $sectionEditorSubmission->getCopyeditorDateCompleted() != null) return;
		if ($copyeditStage == 'final' && ($sectionEditorSubmission->getCopyeditorDateAuthorCompleted() == null || $sectionEditorSubmission->getCopyeditorDateFinalCompleted() != null)) return;
		if ($copyeditStage == 'author' && ($sectionEditorSubmission->getCopyeditorDateCompleted() == null || $sectionEditorSubmission->getCopyeditorDateAuthorCompleted() != null)) return;

		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($sectionEditorSubmission->getCopyeditFileId() != null) {
				$copyeditFileId = $articleFileManager->uploadCopyeditFile($fileName, $sectionEditorSubmission->getCopyeditFileId());
			} else {
				$copyeditFileId = $articleFileManager->uploadCopyeditFile($fileName);
			}
		}
		
		
		if (isset($copyeditFileId) && $copyeditFileId != 0) {
			$sectionEditorSubmission->setCopyeditFileId($copyeditFileId);
	
			if ($copyeditStage == 'initial') {
				$sectionEditorSubmission->setCopyeditorInitialRevision($articleFileDao->getRevisionNumber($copyeditFileId));
			} elseif ($copyeditStage == 'author') {
				$sectionEditorSubmission->setCopyeditorEditorAuthorRevision($articleFileDao->getRevisionNumber($copyeditFileId));
			} elseif ($copyeditStage == 'final') {
				$sectionEditorSubmission->setCopyeditorFinalRevision($articleFileDao->getRevisionNumber($copyeditFileId));
			}
			
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}
	
	/**
	 * Editor completes initial copyedit (copyeditors disabled).
	 * @param $sectionEditorSubmission object
	 */
	function completeCopyedit($sectionEditorSubmission) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		// This is only allowed if copyeditors are disabled.
		if ($journal->getSetting('useCopyeditors')) return;

		$sectionEditorSubmission->setCopyeditorDateCompleted(Core::getCurrentDate());
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		// Add log entry
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_INITIAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.initialEditComplete', Array('copyeditorName' => $user->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId()));
	}
	
	/**
	 * Section editor completes final copyedit (copyeditors disabled).
	 * @param $sectionEditorSubmission object
	 */
	function completeFinalCopyedit($sectionEditorSubmission) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		// This is only allowed if copyeditors are disabled.
		if ($journal->getSetting('useCopyeditors')) return;

		$sectionEditorSubmission->setCopyeditorDateFinalCompleted(Core::getCurrentDate());
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

		if ($copyEdFile = $sectionEditorSubmission->getFinalCopyeditFile()) {
			// Set initial layout version to final copyedit version
			$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment = &$layoutDao->getLayoutAssignmentByArticleId($sectionEditorSubmission->getArticleId());

			if (isset($layoutAssignment) && !$layoutAssignment->getLayoutFileId()) {
				import('file.ArticleFileManager');
				$articleFileManager = new ArticleFileManager($sectionEditorSubmission->getArticleId());
				if ($layoutFileId = $articleFileManager->copyToLayoutFile($copyEdFile->getFileId(), $copyEdFile->getRevision())) {
					$layoutAssignment->setLayoutFileId($layoutFileId);
					$layoutDao->updateLayoutAssignment($layoutAssignment);
				}
			}
		}
		// Add log entry
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_FINAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.finalEditComplete', Array('copyeditorName' => $user->getFullName(), 'articleId' => $sectionEditorSubmission->getArticleId()));
	}
	
	/**
	 * Archive a submission.
	 * @param $sectionEditorSubmission object
	 */
	function archiveSubmission($sectionEditorSubmission) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user = &Request::getUser();
		
		$sectionEditorSubmission->setStatus(STATUS_ARCHIVED);
		$sectionEditorSubmission->stampStatusModified();
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		
		// Add log
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_EDITOR_ARCHIVE, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorSubmission->getArticleId(), 'log.editor.archived', array('articleId' => $sectionEditorSubmission->getArticleId()));
	}
	
	/**
	 * Restores a submission to the queue.
	 * @param $sectionEditorSubmission object
	 */
	function restoreToQueue($sectionEditorSubmission) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission->setStatus(STATUS_QUEUED);
		$sectionEditorSubmission->stampStatusModified();
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	
		// Add log
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($sectionEditorSubmission->getArticleId(), ARTICLE_LOG_EDITOR_RESTORE, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorSubmission->getArticleId(), 'log.editor.restored', array('articleId' => $sectionEditorSubmission->getArticleId()));
	}
	
	/**
	 * Changes the section.
	 * @param $submission object
	 * @param $sectionId int
	 */
	function updateSection($submission, $sectionId) {
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission->setSectionId($sectionId); // FIXME validate this ID?
		$submissionDao->updateSectionEditorSubmission($submission);
	}
	
	//
	// Layout Editing
	//
	
	/**
	 * Upload the layout version of an article.
	 * @param $submission object
	 */
	function uploadLayoutVersion($submission) {
		import('file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($submission->getArticleId());
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$layoutAssignment = &$submission->getLayoutAssignment();
		
		$fileName = 'layoutFile';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			$layoutFileId = $articleFileManager->uploadLayoutFile($fileName, $layoutAssignment->getLayoutFileId());
		
			$layoutAssignment->setLayoutFileId($layoutFileId);
			$submissionDao->updateSectionEditorSubmission($submission);
		}
	}
	
	/**
	 * Assign a layout editor to a submission.
	 * @param $submission object
	 * @param $editorId int user ID of the new layout editor
	 */
	function assignLayoutEditor($submission, $editorId) {
		$layoutAssignment = &$submission->getLayoutAssignment();
		
		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');

		if ($layoutAssignment->getEditorId()) {
			ArticleLog::logEvent($submission->getArticleId(), ARTICLE_LOG_LAYOUT_UNASSIGN, ARTICLE_LOG_TYPE_LAYOUT, $layoutAssignment->getLayoutId(), 'log.layout.layoutEditorUnassigned', array('editorName' => $layoutAssignment->getEditorFullName(), 'articleId' => $submission->getArticleId()));
		}
		
		$layoutAssignment->setEditorId($editorId);
		$layoutAssignment->setDateNotified(null);
		$layoutAssignment->setDateUnderway(null);
		$layoutAssignment->setDateCompleted(null);
		$layoutAssignment->setDateAcknowledged(null);
		
		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutDao->updateLayoutAssignment($layoutAssignment);
		$layoutAssignment = $layoutDao->getLayoutAssignmentById($layoutAssignment->getLayoutId());
		
		ArticleLog::logEvent($submission->getArticleId(), ARTICLE_LOG_LAYOUT_ASSIGN, ARTICLE_LOG_TYPE_LAYOUT, $layoutAssignment->getLayoutId(), 'log.layout.layoutEditorAssigned', array('editorName' => $layoutAssignment->getEditorFullName(), 'articleId' => $submission->getArticleId()));
	}
	
	/**
	 * Notifies the current layout editor about an assignment.
	 * @param $submission object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function notifyLayoutEditor($submission, $send = false) {
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($submission, 'LAYOUT_REQUEST');
		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		if (!isset($layoutEditor)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_LAYOUT_NOTIFY_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
				$email->send();
			}
			
			$layoutAssignment->setDateNotified(Core::getCurrentDate());
			$layoutAssignment->setDateUnderway(null);
			$layoutAssignment->setDateCompleted(null);
			$layoutAssignment->setDateAcknowledged(null);
			$submissionDao->updateSectionEditorSubmission($submission);
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($layoutEditor->getEmail(), $layoutEditor->getFullName());
				$paramArray = array(
					'layoutEditorName' => $layoutEditor->getFullName(),
					'layoutEditorUsername' => $layoutEditor->getUsername(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionLayoutUrl' => Request::getPageUrl() . '/layoutEditor/submission/' . $submission->getArticleId()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyLayoutEditor/send', array('articleId' => $submission->getArticleId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Sends acknowledgement email to the current layout editor.
	 * @param $submission object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function thankLayoutEditor($submission, $send = false) {
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($submission, 'LAYOUT_ACK');

		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		if (!isset($layoutEditor)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_LAYOUT_THANK_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
				$email->send();
			}
			
			$layoutAssignment->setDateAcknowledged(Core::getCurrentDate());
			$submissionDao->updateSectionEditorSubmission($submission);
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($layoutEditor->getEmail(), $layoutEditor->getFullName());
				$paramArray = array(
					'layoutEditorName' => $layoutEditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankLayoutEditor/send', array('articleId' => $submission->getArticleId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Change the sequence order of a galley.
	 * @param $article object
	 * @param $galleyId int
	 * @param $direction char u = up, d = down
	 */
	function orderGalley($article, $galleyId, $direction) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::orderGalley($article, $galleyId, $direction);
	}
	
	/**
	 * Delete a galley.
	 * @param $article object
	 * @param $galleyId int
	 */
	function deleteGalley($article, $galleyId) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::deleteGalley($article, $galleyId);
	}
	
	/**
	 * Change the sequence order of a supplementary file.
	 * @param $article object
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($article, $suppFileId, $direction) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::orderSuppFile($article, $suppFileId, $direction);
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $article object
	 * @param $suppFileId int
	 */
	function deleteSuppFile($article, $suppFileId) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::deleteSuppFile($article, $suppFileId);
	}
	
	/**
	 * Delete a file from an article.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deleteArticleFile($submission, $fileId, $revision) {
		import('file.ArticleFileManager');
		$file = $submission->getEditorFile();
		
		if (isset($file) && $file->getFileId() == $fileId) {
			$articleFileManager = &new ArticleFileManager($submission->getArticleId());
			$articleFileManager->deleteFile($fileId, $revision);
		}
	}

	/**
	 * Add Submission Note
	 * @param $articleId int
	 */
	function addSubmissionNote($articleId) {
		import('file.ArticleFileManager');

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		$user = &Request::getUser();
		
		$articleNote = new ArticleNote();
		$articleNote->setArticleId($articleId);
		$articleNote->setUserId($user->getUserId());
		$articleNote->setDateCreated(Core::getCurrentDate());
		$articleNote->setDateModified(Core::getCurrentDate());
		$articleNote->setTitle(Request::getUserVar('title'));
		$articleNote->setNote(Request::getUserVar('note'));

		$articleFileManager = new ArticleFileManager($articleId);
		if ($articleFileManager->uploadedFileExists('upload')) {
			$fileId = $articleFileManager->uploadSubmissionNoteFile('upload');
		} else {
			$fileId = 0;
		}

		$articleNote->setFileId($fileId);
	
		$articleNoteDao->insertArticleNote($articleNote);
	}

	/**
	 * Remove Submission Note
	 * @param $articleId int
	 */
	function removeSubmissionNote($articleId) {
		import('file.ArticleFileManager');

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');

		$articleNote = new ArticleNote();
		$articleNote->setArticleId($articleId);
		$articleNote->setNoteId(Request::getUserVar('noteId'));
		$articleNote->setFileId(Request::getUserVar('fileId'));

		// if there is an attached file, remove it as well
		if ($articleNote->getFileId()) {
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileManager->deleteFile($articleNote->getFileId());
		}
		
		$articleNoteDao->deleteArticleNoteById($articleNote->getNoteId());
	}
	
	/**
	 * Updates Submission Note
	 * @param $articleId int
	 */
	function updateSubmissionNote($articleId) {
		import('file.ArticleFileManager');

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		$user = &Request::getUser();
		
		$articleNote = new ArticleNote();
		$articleNote->setNoteId(Request::getUserVar('noteId'));
		$articleNote->setArticleId($articleId);
		$articleNote->setUserId($user->getUserId());
		$articleNote->setDateModified(Core::getCurrentDate());
		$articleNote->setTitle(Request::getUserVar('title'));
		$articleNote->setNote(Request::getUserVar('note'));
		$articleNote->setFileId(Request::getUserVar('fileId'));
		
		$articleFileManager = new ArticleFileManager($articleId);
		
		// if there is a new file being uploaded
		if ($articleFileManager->uploadedFileExists('upload')) {
			// Attach the new file to the note, overwriting existing file if necessary
			$fileId = $articleFileManager->uploadSubmissionNoteFile('upload', $articleNote->getFileId(), true);
			$articleNote->setFileId($fileId);
			
		} else {
			if (Request::getUserVar('removeUploadedFile')) {
				$articleFileManager = new ArticleFileManager($articleId);
				$articleFileManager->deleteFile($articleNote->getFileId());
				$articleNote->setFileId(0);
			}
		}
	
		$articleNoteDao->updateArticleNote($articleNote);
	}
	
	/**
	 * Clear All Submission Notes
	 * @param $articleId int
	 */
	function clearAllSubmissionNotes($articleId) {
		import('file.ArticleFileManager');

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');

		$fileIds = $articleNoteDao->getAllArticleNoteFileIds($articleId);

		if (!empty($fileIds)) {
			$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
			$articleFileManager = new ArticleFileManager($articleId);
			
			foreach ($fileIds as $fileId) {
				$articleFileManager->deleteFile($fileId);
			}			
		}
		
		$articleNoteDao->clearAllArticleNotes($articleId);
		
	}
	
	//
	// Comments
	//
	
	/**
	 * View reviewer comments.
	 * @param $article object
	 * @param $reviewId int
	 */
	function viewPeerReviewComments($article, $reviewId) {
		import('submission.form.comment.PeerReviewCommentForm');
		
		$commentForm = new PeerReviewCommentForm($article, $reviewId, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post reviewer comments.
	 * @param $article object
	 * @param $reviewId int
	 * @param $emailComment boolean
	 */
	function postPeerReviewComment($article, $reviewId, $emailComment) {
		import('submission.form.comment.PeerReviewCommentForm');
		
		$commentForm = new PeerReviewCommentForm($article, $reviewId, ROLE_ID_EDITOR);
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
	 * View editor decision comments.
	 * @param $article object
	 */
	function viewEditorDecisionComments($article) {
		import('submission.form.comment.EditorDecisionCommentForm');
		
		$commentForm = new EditorDecisionCommentForm($article, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post editor decision comment.
	 * @param $article int
	 * @param $emailComment boolean
	 */
	function postEditorDecisionComment($article, $emailComment) {
		import('submission.form.comment.EditorDecisionCommentForm');
		
		$commentForm = new EditorDecisionCommentForm($article, ROLE_ID_EDITOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
			if ($emailComment) {
				$commentForm->email();
			}
			
			//if ($commentForm->blindCcReviewers) {
			//	SectionEditorAction::blindCcReviewsToReviewers($commentForm->commentId);
			//}
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}
	
	/**
	 * Blind CC the reviews to reviewers.
	 * @param $article object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function blindCcReviewsToReviewers($article, $send = false) {
		$commentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		
		$comments = &$commentDao->getArticleComments($article->getArticleId(), COMMENT_TYPE_EDITOR_DECISION);
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByArticleId($article->getArticleId());
		
		$commentsText = "";
		foreach ($comments as $comment) {
			$commentsText .= $comment->getComments() . "\n\n";
		}
		
		$user = &Request::getUser();
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($article, 'SUBMISSION_DECISION_REVIEWERS');

		if ($send && !$email->hasErrors()) {
			$email->send();
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				foreach ($reviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
						$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
						
						if (isset($reviewer)) $email->addBcc($reviewer->getEmail(), $reviewer->getFullName());
					}
				}

				$paramArray = array(
					'comments' => $commentsText,
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/blindCcReviewsToReviewers', array('articleId' => $article->getArticleId()), 'submission/comment/commentEmail.tpl');
			return false;
		}
	}
	
	/**
	 * View copyedit comments.
	 * @param $article object
	 */
	function viewCopyeditComments($article) {
		import('submission.form.comment.CopyeditCommentForm');
		
		$commentForm = new CopyeditCommentForm($article, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post copyedit comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postCopyeditComment($article, $emailComment) {
		import('submission.form.comment.CopyeditCommentForm');
		
		$commentForm = new CopyeditCommentForm($article, ROLE_ID_EDITOR);
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
	 * @param $article object
	 */
	function viewLayoutComments($article) {
		import('submission.form.comment.LayoutCommentForm');
		
		$commentForm = new LayoutCommentForm($article, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post layout comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postLayoutComment($article, $emailComment) {
		import('submission.form.comment.LayoutCommentForm');
		
		$commentForm = new LayoutCommentForm($article, ROLE_ID_EDITOR);
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
	 * View proofread comments.
	 * @param $article object
	 */
	function viewProofreadComments($article) {
		import('submission.form.comment.ProofreadCommentForm');
		
		$commentForm = new ProofreadCommentForm($article, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post proofread comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postProofreadComment($article, $emailComment) {
		import('submission.form.comment.ProofreadCommentForm');
		
		$commentForm = new ProofreadCommentForm($article, ROLE_ID_EDITOR);
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
	 * Import Peer Review comments.
	 * @param $article object
	 */
	function importPeerReviews($article) {
		import('submission.form.comment.EditorDecisionCommentForm');
		
		$commentForm = new EditorDecisionCommentForm($article, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->importPeerReviews();
		$commentForm->display();
	}

	/**
	 * Accepts the review assignment on behalf of its reviewer.
	 * @param $articleId int
	 * @param $accept boolean
	 */
	function acceptReviewForReviewer($reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
                $user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId(), true);
		
		// Only confirm the review for the reviewer if 
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			$reviewAssignment->setDeclined(0);
			$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');

			$entry = new ArticleEventLogEntry();
			$entry->setArticleId($reviewAssignment->getArticleId());
			$entry->setUserId($user->getUserId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(ARTICLE_LOG_REVIEW_ACCEPT_BY_PROXY);
			$entry->setLogMessage('log.review.reviewAcceptedByProxy', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getArticleId(), 'round' => $reviewAssignment->getRound(), 'userName' => $user->getFullName()));
			$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getReviewId());

			ArticleLog::logEventEntry($reviewAssignment->getArticleId(), $entry);
		}
	}

	/**
	 * Helper method for building submission breadcrumb
	 * @param $articleId
	 * @param $parentPage name of submission component
	 * @return array
	 */
	function submissionBreadcrumb($articleId, $parentPage, $section) {
		$breadcrumb = array();
		if ($articleId) {
			$breadcrumb[] = array("$section/submission/$articleId", "#$articleId", true);
		}

		if ($parentPage) {
			switch($parentPage) {
				case 'summary':
					$parent = array("$section/submission/$articleId", 'submission.summary');
					break;
				case 'review':
					$parent = array("$section/submissionReview/$articleId", 'submission.review');
					break;
				case 'editing':
					$parent = array("$section/submissionEditing/$articleId", 'submission.editing');
					break;
				case 'history':
					$parent = array("$section/submissionHistory/$articleId", 'submission.history');
					break;
			}
			if ($section != 'editor' && $section != 'sectionEditor') {
				$parent[0] = "$section/submission/$articleId";
			}
			$breadcrumb[] = $parent;
		}
		return $breadcrumb;
	}
	
}

?>
