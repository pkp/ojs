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
	 * @param $articleId int
	 * @param $designate boolean
	 */
	function designateReviewVersion($articleId, $designate = false) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
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
	 * @param $articleId int
	 * @param $sectionId int
	 */
	function changeSection($articleId, $sectionId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$sectionEditorSubmission->setSectionId($sectionId);
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	 
	/**
	 * Records an editor's submission decision.
	 * @param $articleId int
	 * @param $decision int
	 */
	function recordDecision($articleId, $decision) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
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
	 * @param $articleId int
	 * @param $reviewerId int
	 */
	function addReviewer($articleId, $reviewerId, $round = null) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewer = &$userDao->getUser($reviewerId);

		// Check to see if the requested reviewer is not already
		// assigned to review this article.
		if ($round == null) {
			$round = $sectionEditorSubmission->getCurrentRound();
		}
		
		$assigned = $sectionEditorSubmissionDao->reviewerExists($articleId, $reviewerId, $round);
				
		// Only add the reviewer if he has not already
		// been assigned to review this article.
		if (!$assigned) {
			$reviewAssignment = new ReviewAssignment();
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setDateAssigned(Core::getCurrentDate());
			$reviewAssignment->setRound($round);
			
			$sectionEditorSubmission->AddReviewAssignment($reviewAssignment);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		
			$reviewAssignment = $reviewAssignmentDao->getReviewAssignment($articleId, $reviewerId, $round);

			$journal = &Request::getJournal();
			$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$settings = &$settingsDao->getJournalSettings($journal->getJournalId());
			if (isset($settings['numWeeksPerReview'])) SectionEditorAction::setDueDate($articleId, $reviewAssignment->getReviewId(), null, $settings['numWeeksPerReview']);
			
			// Add log
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_ASSIGN, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $round));
		}
	}
	
	/**
	 * Clears a review assignment from a submission.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function clearReview($articleId, $reviewId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if (isset($reviewAssignment) && $reviewAssignment->getArticleId() == $articleId) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			$sectionEditorSubmission->removeReviewAssignment($reviewId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
			
			// Add log
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_CLEAR, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCleared', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
		}		
	}

	/**
	 * Notifies a reviewer about a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function notifyReviewer($articleId, $reviewId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		$isEmailBasedReview = $journal->getSetting('mailSubmissionsToReviewers')==1?true:false;

		$email = &new ArticleMailTemplate($sectionEditorSubmission, $isEmailBasedReview?'REVIEW_REQUEST_ATTACHED':'REVIEW_REQUEST', null, $isEmailBasedReview);
		$email->setFrom($user->getEmail(), $user->getFullName());

		if ($reviewAssignment->getArticleId() == $articleId && $reviewAssignment->getReviewFileId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			
			if ($send && !$email->hasErrors()) {
				$email->setAssoc(ARTICLE_EMAIL_REVIEW_NOTIFY_REVIEWER, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
				$email->send();
				
				$reviewAssignment->setDateNotified(Core::getCurrentDate());
				$reviewAssignment->setCancelled(0);
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			} else {
				if (!Request::getUserVar('continued')) {
					$weekLaterDate = date("Y-m-d", strtotime("+1 week"));
				
					if ($reviewAssignment->getDateDue() != null) {
						$reviewDueDate = date("Y-m-d", strtotime($reviewAssignment->getDateDue()));
					} else {
						$reviewDueDate = date("Y-m-d", strtotime("+2 week"));
					}
				
					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'weekLaterDate' => $weekLaterDate,
						'reviewDueDate' => $reviewDueDate,
						'reviewerUsername' => $reviewer->getUsername(),
						'reviewerPassword' => $reviewer->getPassword(),
						'editorialContactSignature' => $user->getContactSignature($journal) 	
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
				$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyReviewer', array('reviewId' => $reviewId, 'articleId' => $articleId));
			}
		}
	}
	
	/**
	 * Cancels a review.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function cancelReview($articleId, $reviewId, $send = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		if ($reviewAssignment->getArticleId() == $articleId) {
			// Only cancel the review if it is currently not cancelled but has previously
			// been initiated, and has not been completed.
			if ($reviewAssignment->getDateNotified() != null && !$reviewAssignment->getCancelled() && $reviewAssignment->getDateCompleted() == null) {
				$email = &new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_CANCEL');
				$email->setFrom($user->getEmail(), $user->getFullName());

				if ($send && !$email->hasErrors()) {
					$email->setAssoc(ARTICLE_EMAIL_REVIEW_CANCEL, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
					$email->send();

					$reviewAssignment->setCancelled(1);
					$reviewAssignment->stampModified();
				
					$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

					// Add log
					ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_CANCEL, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCancelled', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
				} else {
					if (!Request::getUserVar('continued')) {
						$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

						$paramArray = array(
							'reviewerName' => $reviewer->getFullName(),
							'reviewerUsername' => $reviewer->getUsername(),
							'reviewerPassword' => $reviewer->getPassword(),
							'editorialContactSignature' => $user->getContactSignature($journal)
						);
						$email->assignParams($paramArray);
					}
					$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/cancelReview/send', array('reviewId' => $reviewId, 'articleId' => $articleId));
				}
			}				
		}
	}
	
	/**
	 * Reminds a reviewer about a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function remindReviewer($articleId, $reviewId, $send = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
			
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_REMIND');
		$email->setFrom($user->getEmail(), $user->getFullName());

		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_REVIEW_REMIND, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
			
			$email->send();

			$reviewAssignment->setDateReminded(Core::getCurrentDate());
			$reviewAssignment->setReminderWasAutomatic(0);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
		} elseif ($reviewAssignment->getArticleId() == $articleId) {
		
			if (!Request::getUserVar('continued')) {
				$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

				
				//
				// FIXME: Assign correct values!
				//
				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'reviewerUsername' => $reviewer->getUsername(),
					'reviewerPassword' => $reviewer->getPassword(),
					'reviewDueDate' => $reviewAssignment->getDateDue(),
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
	
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/remindReviewer/send', array('reviewerId' => $reviewer->getUserId(), 'articleId' => $articleId, 'reviewId' => $reviewId));
		}
	}
	
	/**
	 * Thanks a reviewer for completing a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function thankReviewer($articleId, $reviewId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'REVIEW_ACK');
		$email->setFrom($user->getEmail(), $user->getFullName());

		if ($reviewAssignment->getArticleId() == $articleId) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			
			if ($send && !$email->hasErrors()) {
				$email->setAssoc(ARTICLE_EMAIL_REVIEW_THANK_REVIEWER, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
				$email->send();
				
				$reviewAssignment->setDateAcknowledged(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			} else {
				if (!Request::getUserVar('continued')) {
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'editorialContactSignature' => $user->getContactSignature($journal)
					);
					$email->assignParams($paramArray);
				}
				$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankReviewer/send', array('reviewId' => $reviewId, 'articleId' => $articleId));
			}
		}
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
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			if ($dueDate != null) {
				$dueDateParts = explode("-", $dueDate);
				$today = getDate();
				
				// Ensure that the specified due date is today or after today's date.
				if ($dueDateParts[0] >= $today['year'] && ($dueDateParts[1] > $today['mon'] || ($dueDateParts[1] == $today['mon'] && $dueDateParts[2] >= $today['mday']))) {
					$reviewAssignment->setDateDue(date("Y-m-d H:i:s", mktime(0, 0, 0, $dueDateParts[1], $dueDateParts[2], $dueDateParts[0])));
				}
				else {
					$today = getDate();
					$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
					$reviewAssignment->setDateDue(date("Y-m-d H:i:s", $todayTimestamp));
				}
			} else {
				$today = getDate();
				$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
				
				// Add the equivilant of $numWeeks weeks, measured in seconds, to $todaysTimestamp.
				$newDueDateTimestamp = $todayTimestamp + ($numWeeks * 7 * 24 * 60 * 60);

				$reviewAssignment->setDateDue(date("Y-m-d H:i:s", $newDueDateTimestamp));
			}
		
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			
			// Add log
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_SET_DUE_DATE, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewDueDateSet', array('reviewerName' => $reviewer->getFullName(), 'dueDate' => date("Y-m-d", strtotime($reviewAssignment->getDateDue())), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
		}
	 }
	 
	/**
	 * Notifies an author about the editor review.
	 * @param $articleId int
	 * FIXME: Still need to add Reviewer Comments
	 */
	function notifyAuthor($articleId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'EDITOR_REVIEW');

		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_EDITOR_NOTIFY_AUTHOR, ARTICLE_EMAIL_TYPE_EDITOR, $articleId);
			$email->send();
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'authorUsername' => $author->getUsername(),
					'authorPassword' => $author->getPassword(),
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyAuthor/send', array('articleId' => $articleId));
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
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			$reviewAssignment->setRecommendation($recommendation);
			$reviewAssignment->setDateCompleted(Core::getCurrentDate());
			$reviewAssignment->stampModified();
		
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			
			// Add log
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_RECOMMENDATION, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewRecommendationSet', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
		}
	 }
	 
	/**
	 * Set the file to use as the default copyedit file.
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function setCopyeditFile($articleId, $fileId, $revision) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		// Copy the file from the editor decision file folder to the copyedit file folder
		$newFileId = $articleFileManager->copyToCopyeditFile($fileId, $revision);
			
		$sectionEditorSubmission->setCopyeditFileId($newFileId);
		$sectionEditorSubmission->setCopyeditorInitialRevision(1);

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		
		// Add log
		ArticleLog::logEvent($articleId, ARTICLE_LOG_COPYEDIT_SET_FILE, ARTICLE_LOG_TYPE_COPYEDIT, $sectionEditorSubmission->getCopyeditFileId(), 'log.copyedit.copyeditFileSet');
	}
	
	/**
	 * Resubmit the file for review.
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function resubmitFile($articleId, $fileId, $revision) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

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
				SectionEditorAction::addReviewer($sectionEditorSubmission->getArticleId(), $reviewAssignment->getReviewerId(), $sectionEditorSubmission->getCurrentRound());
			}
		}
		
		
		// Add log
		ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_RESUBMIT, ARTICLE_LOG_TYPE_EDITOR, $user->getUserId(), 'log.review.resubmitted', array('articleId' => $articleId));
	}
	 
	/**
	 * Assigns a copyeditor to a submission.
	 * @param $articleId int
	 * @param $copyeditorId int
	 */
	function selectCopyeditor($articleId, $copyeditorId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		// Check to see if the requested copyeditor is not already
		// assigned to copyedit this article.
		$assigned = $sectionEditorSubmissionDao->copyeditorExists($articleId, $copyeditorId);
		
		// Only add the copyeditor if he has not already
		// been assigned to review this article.
		if (!$assigned) {
			$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
			$sectionEditorSubmission->setCopyeditorId($copyeditorId);
		
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);	
			
			$copyeditor = &$userDao->getUser($copyeditorId);
		
			// Add log
			ArticleLog::logEvent($articleId, ARTICLE_LOG_COPYEDIT_ASSIGN, ARTICLE_LOG_TYPE_COPYEDIT, $copyeditorId, 'log.copyedit.copyeditorAssigned', array('copyeditorName' => $copyeditor->getFullName(), 'articleId' => $articleId));
		}
	}
	
	/**
	 * Notifies a copyeditor about a copyedit assignment.
	 * @param $articleId int
	 */
	function notifyCopyeditor($articleId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_REQUEST');
		$email->setFrom($user->getEmail(), $user->getFullName());

		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		
		if ($send && $sectionEditorSubmission->getInitialCopyeditFile() && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_COPYEDITOR, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
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
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyCopyeditor/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Initiates the initial copyedit stage when the editor does the copyediting.
	 * @param $articleId int
	 */
	function initiateCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		// Only allow copyediting to be initiated if a copyedit file exists.
		if ($sectionEditorSubmission->getInitialCopyeditFile()) {
			$sectionEditorSubmission->setCopyeditorDateNotified(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}
	
	/**
	 * Thanks a copyeditor about a copyedit assignment.
	 * @param $articleId int
	 */
	function thankCopyeditor($articleId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_ACK');
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$sectionEditorSubmission->setCopyeditorDateAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankCopyeditor/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Notifies the author that the copyedit is complete.
	 * @param $articleId int
	 */
	function notifyAuthorCopyedit($articleId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_AUTHOR_REQUEST');
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
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
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyAuthorCopyedit/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Thanks an author for completing editor / author review.
	 * @param $articleId int
	 */
	function thankAuthorCopyedit($articleId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_AUTHOR_ACK');
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankAuthorCopyedit/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Notify copyeditor about final copyedit.
	 * @param $articleId int
	 * @param $send boolean
	 */
	function notifyFinalCopyedit($articleId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_FINAL_REQUEST');
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
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
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyFinalCopyedit/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Thank copyeditor for completing final copyedit.
	 * @param $articleId int
	 */
	function thankFinalCopyedit($articleId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$email = &new ArticleMailTemplate($sectionEditorSubmission, 'COPYEDIT_FINAL_ACK');
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				$paramArray = array(
					'copyeditorName' => $copyeditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankFinalCopyedit/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Upload the review version of an article.
	 * @param $articleId int
	 */
	function uploadReviewVersion($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($sectionEditorSubmission->getReviewFileId() != null) {
				$reviewFileId = $articleFileManager->uploadReviewFile($fileName, $sectionEditorSubmission->getReviewFileId());
			} else {
				$reviewFileId = $articleFileManager->uploadReviewFile($fileName);
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
	 * @param $articleId int
	 */
	function uploadEditorVersion($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user = &Request::getUser();
		
		$sectionEditorSubmission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
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
			ArticleLog::logEvent($articleId, ARTICLE_LOG_EDITOR_FILE, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorSubmission->getEditorFileId(), 'log.editor.editorFile');
		}
	}
	
	/**
	 * Upload the copyedit version of an article.
	 * @param $articleId int
	 * @param $copyeditStage string
	 */
	function uploadCopyeditVersion($articleId, $copyeditStage) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

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
	 * @param $articleId int
	 */
	function completeCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		// This is only allowed if copyeditors are disabled.
		if ($journal->getSetting('useCopyeditors')) return;

		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$sectionEditorSubmission->setCopyeditorDateCompleted(Core::getCurrentDate());
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		// Add log entry
		ArticleLog::logEvent($articleId, ARTICLE_LOG_COPYEDIT_INITIAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.initialEditComplete', Array('copyEditorName' => $user->getFullName(), 'articleId' => $articleId));
	}
	
	/**
	 * Section editor completes final copyedit (copyeditors disabled).
	 * @param $articleId int
	 */
	function completeFinalCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		// This is only allowed if copyeditors are disabled.
		if ($journal->getSetting('useCopyeditors')) return;

		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$sectionEditorSubmission->setCopyeditorDateFinalCompleted(Core::getCurrentDate());
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);

		if ($copyEdFile = $sectionEditorSubmission->getFinalCopyeditFile()) {
			// Set initial layout version to final copyedit version
			$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment = &$layoutDao->getLayoutAssignmentByArticleId($articleId);

			if (isset($layoutAssignment) && !$layoutAssignment->getLayoutFileId()) {
				import('file.ArticleFileManager');
				$articleFileManager = new ArticleFileManager($articleId);
				if ($layoutFileId = $articleFileManager->copyToLayoutFile($copyEdFile->getFileId(), $copyEdFile->getRevision())) {
					$layoutAssignment->setLayoutFileId($layoutFileId);
					$layoutDao->updateLayoutAssignment($layoutAssignment);
				}
			}
		}
		// Add log entry
		ArticleLog::logEvent($articleId, ARTICLE_LOG_COPYEDIT_FINAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.finalEditComplete', Array('copyEditorName' => $user->getFullName(), 'articleId' => $articleId));
	}
	
	/**
	 * Archive a submission.
	 * @param $articleId int
	 */
	function archiveSubmission($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$sectionEditorSubmission->setStatus(STATUS_ARCHIVED);
		$sectionEditorSubmission->stampStatusModified();
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		
		// Add log
		ArticleLog::logEvent($articleId, ARTICLE_LOG_EDITOR_ARCHIVE, ARTICLE_LOG_TYPE_EDITOR, $articleId, 'log.editor.archived', array('articleId' => $articleId));
	}
	
	/**
	 * Restores a submission to the queue.
	 * @param $articleId int
	 */
	function restoreToQueue($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$sectionEditorSubmission->setStatus(STATUS_QUEUED);
		$sectionEditorSubmission->stampStatusModified();
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	
		// Add log
		ArticleLog::logEvent($articleId, ARTICLE_LOG_EDITOR_RESTORE, ARTICLE_LOG_TYPE_EDITOR, $articleId, 'log.editor.restored', array('articleId' => $articleId));
	}
	
	/**
	 * Changes the section.
	 * @param $sectionId int
	 * @param $articleId int
	 */
	function updateSection($articleId, $sectionId) {
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $submissionDao->getSectionEditorSubmission($articleId);
		$submission->setSectionId($sectionId); // FIXME validate this ID?
		$submissionDao->updateSectionEditorSubmission($submission);
	}
	
	//
	// Layout Editing
	//
	
	/**
	 * Upload the layout version of an article.
	 * @param $articleId int
	 */
	function uploadLayoutVersion($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$submission = $submissionDao->getSectionEditorSubmission($articleId);
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
	 * @param $articleId int
	 * @param $editorId int user ID of the new layout editor
	 */
	function assignLayoutEditor($articleId, $editorId) {
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$submission = &$submissionDao->getSectionEditorSubmission($articleId);
		$layoutAssignment = &$submission->getLayoutAssignment();
		
		if ($layoutAssignment->getEditorId()) {
			ArticleLog::logEvent($articleId, ARTICLE_LOG_LAYOUT_UNASSIGN, ARTICLE_LOG_TYPE_LAYOUT, $layoutAssignment->getLayoutId(), 'log.layout.layoutEditorUnassigned', array('editorName' => $layoutAssignment->getEditorFullName(), 'articleId' => $articleId));
		}
		
		$layoutAssignment->setEditorId($editorId);
		$layoutAssignment->setDateNotified(null);
		$layoutAssignment->setDateUnderway(null);
		$layoutAssignment->setDateCompleted(null);
		$layoutAssignment->setDateAcknowledged(null);
		
		$submissionDao->updateSectionEditorSubmission($submission);
		$submission = &$submissionDao->getSectionEditorSubmission($articleId);

		ArticleLog::logEvent($articleId, ARTICLE_LOG_LAYOUT_ASSIGN, ARTICLE_LOG_TYPE_LAYOUT, $layoutAssignment->getLayoutId(), 'log.layout.layoutEditorAssigned', array('editorName' => $layoutAssignment->getEditorFullName(), 'articleId' => $articleId));
	}
	
	/**
	 * Notifies the current layout editor about an assignment.
	 * @param $articleId int
	 * @param $send boolean
	 */
	function notifyLayoutEditor($articleId, $send = false) {
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$submission = &$submissionDao->getSectionEditorSubmission($articleId);
		$email = &new ArticleMailTemplate($submission, 'LAYOUT_REQUEST');
		$email->setFrom($user->getEmail(), $user->getFullName());
		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_LAYOUT_NOTIFY_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
			$email->send();
			
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
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyLayoutEditor/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Sends acknowledgement email to the current layout editor.
	 * @param $articleId int
	 * @param $send boolean
	 */
	function thankLayoutEditor($articleId, $send = false) {
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$submission = &$submissionDao->getSectionEditorSubmission($articleId);
		$email = &new ArticleMailTemplate($submission, 'LAYOUT_ACK');
		$email->setFrom($user->getEmail(), $user->getFullName());

		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_LAYOUT_THANK_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
			$email->send();
			
			$layoutAssignment->setDateAcknowledged(Core::getCurrentDate());
			$submissionDao->updateSectionEditorSubmission($submission);
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($layoutEditor->getEmail(), $layoutEditor->getFullName());
				$paramArray = array(
					'layoutEditorName' => $layoutEditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/thankLayoutEditor/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Change the sequence order of a galley.
	 * @param $articleId int
	 * @param $galleyId int
	 * @param $direction char u = up, d = down
	 */
	function orderGalley($articleId, $galleyId, $direction) {
		LayoutEditorAction::orderGalley($articleId, $galleyId, $direction);
	}
	
	/**
	 * Delete a galley.
	 * @param $articleId int
	 * @param $galleyId int
	 */
	function deleteGalley($articleId, $galleyId) {
		LayoutEditorAction::deleteGalley($articleId, $galleyId);
	}
	
	/**
	 * Change the sequence order of a supplementary file.
	 * @param $articleId int
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($articleId, $suppFileId, $direction) {
		LayoutEditorAction::orderSuppFile($articleId, $suppFileId, $direction);
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $articleId int
	 * @param $suppFileId int
	 */
	function deleteSuppFile($articleId, $suppFileId) {
		LayoutEditorAction::deleteSuppFile($articleId, $suppFileId);
	}
	
	/**
	 * Delete a file from an article.
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deleteArticleFile($articleId, $fileId, $revision) {
		import('file.ArticleFileManager');
		
		$submissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = &$submissionDao->getSectionEditorSubmission($articleId);
		$file = $submission->getEditorFile();
		
		if (isset($file) && $file->getFileId() == $fileId) {
			$articleFileManager = &new ArticleFileManager($articleId);
			$articleFileManager->deleteFile($fileId, $revision);
		}
	}

	/**
	 * Add Submission Note
	 * @param $articleId int
	 */
	function addSubmissionNote($articleId) {
		import("file.ArticleFileManager");

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
		import("file.ArticleFileManager");

		$articleNote = new ArticleNote();
		$articleNote->setArticleId($articleId);
		$articleNote->setNoteId(Request::getUserVar('noteId'));
		$articleNote->setFileId(Request::getUserVar('fileId'));

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');

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
		import("file.ArticleFileManager");

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
		import("file.ArticleFileManager");

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
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function viewPeerReviewComments($articleId, $reviewId) {
		import("submission.form.comment.PeerReviewCommentForm");
		
		$commentForm = new PeerReviewCommentForm($articleId, $reviewId, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post reviewer comments.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $emailComment boolean
	 */
	function postPeerReviewComment($articleId, $reviewId, $emailComment) {
		import("submission.form.comment.PeerReviewCommentForm");
		
		$commentForm = new PeerReviewCommentForm($articleId, $reviewId, ROLE_ID_EDITOR);
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
	 * @param $articleId int
	 */
	function viewEditorDecisionComments($articleId) {
		import("submission.form.comment.EditorDecisionCommentForm");
		
		$commentForm = new EditorDecisionCommentForm($articleId, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post editor decision comment.
	 * @param $articleId int
	 * @param $emailComment boolean
	 */
	function postEditorDecisionComment($articleId, $emailComment) {
		import("submission.form.comment.EditorDecisionCommentForm");
		
		$commentForm = new EditorDecisionCommentForm($articleId, ROLE_ID_EDITOR);
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
	 * @param $articleId int
	 * @param $send boolean
	 */
	function blindCcReviewsToReviewers($articleId, $send = false) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$commentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		
		$article = &$articleDao->getArticle($articleId);
		$comments = &$commentDao->getArticleComments($articleId, COMMENT_TYPE_EDITOR_DECISION);
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByArticleId($articleId);
		
		$commentsText = "";
		foreach ($comments as $comment) {
			$commentsText .= $comment->getComments() . "\n\n";
		}
		
		$user = &Request::getUser();
		$email = &new ArticleMailTemplate($article, 'SUBMISSION_DECISION_REVIEWERS');
		$email->setFrom($user->getEmail(), $user->getFullName());

		if ($send && !$email->hasErrors()) {
			$email->send();

		} else {
			if (!Request::getUserVar('continued')) {
				foreach ($reviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getDateCompleted() != null) {
						$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
						
						$email->addBcc($reviewer->getEmail(), $reviewer->getFullName());
					}
				}

				$paramArray = array(
					'comments' => $commentsText,
					'editorialContactSignature' => $user->getContactSignature($journal)
				);
				$email->assignParams($paramArray);
			}
			
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/blindCcReviewsToReviewers/send', array('articleId' => $articleId), 'submission/comment/commentEmail.tpl');
		}
	}
	
	/**
	 * View copyedit comments.
	 * @param $articleId int
	 */
	function viewCopyeditComments($articleId) {
		import("submission.form.comment.CopyeditCommentForm");
		
		$commentForm = new CopyeditCommentForm($articleId, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post copyedit comment.
	 * @param $articleId int
	 * @param $emailComment boolean
	 */
	function postCopyeditComment($articleId, $emailComment) {
		import("submission.form.comment.CopyeditCommentForm");
		
		$commentForm = new CopyeditCommentForm($articleId, ROLE_ID_EDITOR);
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
		
		$commentForm = new LayoutCommentForm($articleId, ROLE_ID_EDITOR);
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
		
		$commentForm = new LayoutCommentForm($articleId, ROLE_ID_EDITOR);
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
	 * @param $articleId int
	 */
	function viewProofreadComments($articleId) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = new ProofreadCommentForm($articleId, ROLE_ID_EDITOR);
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
		
		$commentForm = new ProofreadCommentForm($articleId, ROLE_ID_EDITOR);
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
	 * @param $articleId int
	 */
	function importPeerReviews($articleId) {
		import("submission.form.comment.EditorDecisionCommentForm");
		
		$commentForm = new EditorDecisionCommentForm($articleId, ROLE_ID_EDITOR);
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
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
		// Only confirm the review for the reviewer if 
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			$reviewAssignment->setDeclined(0);
			$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
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
