<?php

/**
 * SectionEditorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * SectionEditorAction class.
 *
 * $Id$
 */

class SectionEditorAction extends Action{

	/**
	 * Constructor.
	 */
	function SectionEditorAction() {

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
			$reviewFileId = $articleFileManager->originalToReviewFile($submissionFile->getFileId());
			$editorFileId = $articleFileManager->reviewToEditorFile($reviewFileId);
			
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
			
			// Add log
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_ASSIGN, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $round));
		}
	}
	
	/**
	 * Removes a review assignment from a submission.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function removeReview($articleId, $reviewId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			$sectionEditorSubmission->removeReviewAssignment($reviewId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
			
			// Add log
			ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_ASSIGN, ARTICLE_LOG_REVIEW_ASSIGN, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerUnassigned', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
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
		
		$email = &new ArticleMailTemplate($articleId, 'ARTICLE_REVIEW_REQ');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			
			if ($send) {
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
				$email->setFrom($user->getFullName(), $user->getEmail());
				$email->setSubject(Request::getUserVar('subject'));
				$email->setBody(Request::getUserVar('body'));
				$email->setAssoc(ARTICLE_EMAIL_REVIEW_NOTIFY_REVIEWER, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
				$email->send();
				
				$reviewAssignment->setDateNotified(Core::getCurrentDate());
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			} else {
				$weekLaterDate = date("Y-m-d", strtotime("+1 week"));
				
				if ($reviewAssignment->getDateDue() != null) {
					$reviewDueDate = date("Y-m-d", strtotime($reviewAssignment->getDateDue()));
				} else {
					$reviewDueDate = date("Y-m-d", strtotime("+2 week"));
				}
				
				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'journalName' => $journal->getSetting('journalTitle'),
					'journalUrl' => Request::getIndexUrl() . '/' . Request::getRequestedJournalPath(),
					'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
					'articleAbstract' => $sectionEditorSubmission->getArticleAbstract(),
					'weekLaterDate' => $weekLaterDate,
					'reviewDueDate' => $reviewDueDate,
					'reviewerUsername' => $reviewer->getUsername(),
					'reviewerPassword' => $reviewer->getPassword(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation() 	
				);
				$email->assignParams($paramArray);
				$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/notifyReviewer/send', array('reviewId' => $reviewId, 'articleId' => $articleId));
			}
		}
	}
	
	/**
	 * Initiates a review.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function initiateReview($articleId, $reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			// Only initiate if the review has not already been
			// initiated.
			if ($reviewAssignment->getDateInitiated() == null) {
				$reviewAssignment->setDateInitiated(Core::getCurrentDate());

				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				
				// Add log
				ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_INITIATE, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewInitiated', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
			}				
		}
	}
	
	/**
	 * Reinitiates a review.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function reinitiateReview($articleId, $reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());	
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			// Only reinitiate if the review had been previously initiated
			// then cancelled.
			if ($reviewAssignment->getDateInitiated() != null && $reviewAssignment->getCancelled()) {
				$reviewAssignment->setDateInitiated(Core::getCurrentDate());
				$reviewAssignment->setCancelled(0);

				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				
				// Add log
				ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_REINITIATE, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewReinitiated', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
			}				
		}
	}
	
	/**
	 * Initiates all reviews.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function initiateAllReviews($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByArticleId($articleId, $sectionEditorSubmission->getCurrentRound());

		foreach ($reviewAssignments as $reviewAssignment) {
			// Only initiate if the review has not already been
			// initiated.
			if ($reviewAssignment->getDateInitiated() == null) {
				$reviewAssignment->setDateInitiated(Core::getCurrentDate());

				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			}				
		}
	}
	
	/**
	 * Cancels a review.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function cancelReview($articleId, $reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			// Only cancel the review if it is currently not cancelled but has previously
			// been initiated.
			if ($reviewAssignment->getDateInitiated() != null && !$reviewAssignment->getCancelled()) {
				$reviewAssignment->setCancelled(1);
				
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				
				// Add log
				ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_CANCEL, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCancelled', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $articleId, 'round' => $reviewAssignment->getRound()));
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
		$email = &new ArticleMailTemplate($articleId, 'ARTICLE_REVIEW_REQ');
		
		if ($send) {
			$reviewer = &$userDao->getUser(Request::getUserVar('reviewerId'));
			
			$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
			
			$email->send();
		
		} else {
			$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
			if ($reviewAssignment->getArticleId() == $articleId) {
				$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());		
				
				//
				// FIXME: Assign correct values!
				//
				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'journalName' => "Hansen",
					'journalUrl' => "Hansen",
					'articleTitle' => $sectionEditorSubmission->getTitle(),
					'sectionName' => $sectionEditorSubmission->getSectionTitle(),
					'reviewerUsername' => "http://www.roryscoolsite.com",
					'reviewerPassword' => "Hansen",
					'principalContactName' => "Hansen"	
				);
				$email->assignParams($paramArray);
				$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/remindReviewer/send', array('reviewerId' => $reviewer->getUserId(), 'articleId' => $articleId));
	
			}
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
		
		$email = &new ArticleMailTemplate($articleId, 'ARTICLE_REVIEW_ACK');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			
			if ($send) {
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
				$email->setFrom($user->getFullName(), $user->getEmail());
				$email->setSubject(Request::getUserVar('subject'));
				$email->setBody(Request::getUserVar('body'));
				$email->setAssoc(ARTICLE_EMAIL_REVIEW_THANK_REVIEWER, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
				$email->send();
				
				$reviewAssignment->setDateAcknowledged(Core::getCurrentDate());
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			} else {
				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation() 	
				);
				$email->assignParams($paramArray);
				$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/thankReviewer/send', array('reviewId' => $reviewId, 'articleId' => $articleId));
			}
		}
	}
	
	/**
	 * Rates a reviewer for timeliness and quality of a review.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $timeliness int
	 * @param $quality int
	 */
	function rateReviewer($articleId, $reviewId, $timeliness = null, $quality = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			// Ensure that the values for timeliness and quality
			// are between 1 and 5.
			if ($timeliness != null && ($timeliness >= 1 && $timeliness <= 5)) {
				$reviewAssignment->setTimeliness($timeliness);
			}
			if ($quality != null && ($quality >= 1 && $quality <= 5)) {
				$reviewAssignment->setQuality($quality);
			}
			
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
			} else {
				$today = getDate();
				$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
				
				// Add the equivilant of $numWeeks weeks, measured in seconds, to $todaysTimestamp.
				$newDueDateTimestamp = $todayTimestamp + ($numWeeks * 7 * 24 * 60 * 60);

				$reviewAssignment->setDateDue(date("Y-m-d H:i:s", $newDueDateTimestamp));
			}
		
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
		
		$email = &new ArticleMailTemplate($articleId, 'EDITOR_REVIEW');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		
		if ($send) {
			$email->addRecipient($author->getEmail(), $author->getFullName());
			$email->setFrom($user->getFullName(), $user->getEmail());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_EDITOR_NOTIFY_AUTHOR, ARTICLE_EMAIL_TYPE_EDITOR, $articleId);
			$email->send();
			
		} else {
			$paramArray = array(
				'authorName' => $author->getFullName(),
				'journalName' => $journal->getSetting('journalTitle'),
				'journalUrl' => Request::getIndexUrl() . '/' . Request::getRequestedJournalPath(),
				'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
				'articleAbstract' => $sectionEditorSubmission->getArticleAbstract(),
				'authorUsername' => $author->getUsername(),
				'authorPassword' => $author->getPassword(),
				'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation() 	
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/notifyAuthor/send', array('articleId' => $articleId));
		}
	}
	 
	/**
	 * Sets the reviewer recommendation for a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $recommendation int
	 */
	 function setReviewerRecommendation($articleId, $reviewId, $recommendation) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			$reviewAssignment->setRecommendation($recommendation);
		
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
		
		if ($sectionEditorSubmission->getEditorFileId() == $fileId) {
			// Then the selected file is an "Editor" file.
			$newFileId = $articleFileManager->editorToCopyeditFile($fileId, $revision);
		} else {
			// Otherwise the selected file is an "Author" file.
			$newFileId = $articleFileManager->authorToCopyeditFile($fileId, $revision);
		}
			
		$sectionEditorSubmission->setCopyeditFileId($newFileId);

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
	
		if ($sectionEditorSubmission->getEditorFileId() == $fileId) {
			// Then the selected file is an "Editor" file.
			$newFileId = $articleFileManager->editorToReviewFile($fileId, $revision, $sectionEditorSubmission->getReviewFileId());
		} else {
			// Otherwise the selected file is an "Author" file.
			$newFileId = $articleFileManager->authorToReviewFile($fileId, $revision, $sectionEditorSubmission->getReviewFileId());
		}
		
		
		// Increment the round
		$currentRound = $sectionEditorSubmission->getCurrentRound();
		$sectionEditorSubmission->setCurrentRound($currentRound + 1);
		
		// The review revision is the highest revision for the review file.
		$reviewRevision = $articleFileDao->getRevisionNumber($newFileId);
		$sectionEditorSubmission->setReviewRevision($reviewRevision);
		
		// Now, reassign all reviewers that submitted a review for this new round of reviews.
		$previousRound = $sectionEditorSubmission->getCurrentRound() - 1;
		foreach ($sectionEditorSubmission->getReviewAssignments($previousRound) as $reviewAssignments) {
			if ($reviewAssignment->getRecommendation() != null) {
				// Then this reviewer submitted a review.
				SectionEditorAction::addReviewer($sectionEditorSubmission->getArticleId(), $reviewAssignment->getReviewerId(), $sectionEditorSubmission->getCurrentRound());
			}
		}
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		
		// Add log
		ArticleLog::logEvent($articleId, ARTICLE_LOG_REVIEW_RESUBMIT, 0, 0, 'log.review.resubmitted', array('articleId' => $articleId));
	}
	 
	/**
	 * Assigns a copyeditor to a submission.
	 * @param $articleId int
	 * @param $copyeditorId int
	 */
	function addCopyeditor($articleId, $copyeditorId) {
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
			ArticleLog::logEvent($articleId, ARTICLE_LOG_COPYEDIT_ASSIGN, ARTICLE_LOG_TYPE_COPYEDIT, $copyeditorId, $copyeditorId, 'log.copyedit.copyeditorAssigned', array('copyeditorName' => $copyeditor->getFullName(), 'articleId' => $articleId));
		}
	}
	
	/**
	 * Replaces a copyeditor to a submission.
	 * @param $articleId int
	 * @param $copyeditorId int
	 */
	function replaceCopyeditor($articleId, $copyeditorId) {
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
		
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_REQ');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		
		if ($send) {
			$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
			$email->setFrom($user->getFullName(), $user->getEmail());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_COPYEDITOR, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$sectionEditorSubmission->setCopyeditorDateNotified(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			$paramArray = array(
				'copyeditorName' => $copyeditor->getFullName(),
				'journalName' => $journal->getSetting('journalTitle'),
				'journalUrl' => Request::getIndexUrl() . '/' . Request::getRequestedJournalPath(),
				'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
				'copyeditorUsername' => $copyeditor->getUsername(),
				'copyeditorPassword' => $copyeditor->getPassword(),
				'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation() 	
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/notifyCopyeditor/send', array('articleId' => $articleId));
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
		
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_ACK');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		
		if ($send) {
			$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
			$email->setFrom($user->getFullName(), $user->getEmail());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$sectionEditorSubmission->setCopyeditorDateAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			$paramArray = array(
				'copyeditorName' => $copyeditor->getFullName(),
				'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
				'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation() 	
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/thankCopyeditor/send', array('articleId' => $articleId));
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
		
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_REVIEW_AUTHOR');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		
		if ($send) {
			$email->addRecipient($author->getEmail(), $author->getFullName());
			$email->setFrom($user->getFullName(), $user->getEmail());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$sectionEditorSubmission->setCopyeditorDateAuthorNotified(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			$paramArray = array(
				'authorName' => $author->getFullName(),
				'journalName' => $journal->getSetting('journalTitle'),
				'journalUrl' => Request::getIndexUrl() . '/' . Request::getRequestedJournalPath(),
				'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
				'authorUsername' => $author->getUsername(),
				'authorPassword' => $author->getPassword(),
				'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation() 	
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/notifyAuthorCopyedit/send', array('articleId' => $articleId));
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
		
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_AUTHOR_ACK');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
		
		if ($send) {
			$email->addRecipient($author->getEmail(), $author->getFullName());
			$email->setFrom($user->getFullName(), $user->getEmail());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			$paramArray = array(
				'authorName' => $author->getFullName(),
				'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
				'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation() 	
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/thankAuthorCopyedit/send', array('articleId' => $articleId));
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
		
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_FINAL_REVIEW');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		
		if ($send) {
			$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
			$email->setFrom($user->getFullName(), $user->getEmail());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$sectionEditorSubmission->setCopyeditorDateFinalNotified(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			$paramArray = array(
				'copyeditorName' => $copyeditor->getFullName(),
				'journalName' => $journal->getSetting('journalTitle'),
				'journalUrl' => Request::getIndexUrl() . '/' . Request::getRequestedJournalPath(),
				'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
				'copyeditorUsername' => $copyeditor->getUsername(),
				'copyeditorPassword' => $copyeditor->getPassword(),
				'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation() 	
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/notifyFinalCopyedit/send', array('articleId' => $articleId));
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
		
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_FINAL_REVIEW_ACK');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
		
		if ($send) {
			$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
			$email->setFrom($user->getFullName(), $user->getEmail());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_ACKNOWLEDGE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		} else {
			$paramArray = array(
				'copyeditorName' => $copyeditor->getFullName(),
				'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
				'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation() 	
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/sectionEditor/thankFinalCopyedit/send', array('articleId' => $articleId));
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
				$reviewFileId = $articleFileManager->uploadEditorFile($fileName, $sectionEditorSubmission->getReviewFileId());
			} else {
				$reviewFileId = $articleFileManager->uploadEditorFile($fileName);
			}
			$editorFileId = $articleFileManager->reviewToEditorFile($reviewFileId, $sectionEditorSubmission->getReviewRevision(), $sectionEditorSubmission->getEditorFileId());
		}
		
		$sectionEditorSubmission->setReviewFileId($reviewFileId);
		$sectionEditorSubmission->setEditorFileId($editorFileId);

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}

	/**
	 * Select the revisions of the copyedit file to use in Initial, Editor/Author, and Final
	 * Copyedit stages.
	 * @param $articleId int
	 * @param $initialRevision int
	 * @param $editorAuthorRevision int
	 * @param $finalRevision int
	 */
	function selectCopyeditRevisions($articleId, $initialRevision, $editorAuthorRevision, $finalRevision) {	
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
	
		$sectionEditorSubmission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
	
		if ($sectionEditorSubmission->getCopyeditorId() != null && $sectionEditorSubmission->getCopyeditorDateCompleted() == null) {
			$sectionEditorSubmission->setCopyeditorInitialRevision($initialRevision);
		} elseif ($sectionEditorSubmission->getCopyeditorDateCompleted() != null && $sectionEditorSubmission->getCopyeditorDateAuthorCompleted() == null) {
			$sectionEditorSubmission->setCopyeditorEditorAuthorRevision($editorAuthorRevision);
		} else {
			$sectionEditorSubmission->setCopyeditorFinalRevision($finalRevision);
		}
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Select the revision to use as the default for the recopyedit.
	 * @param $articleId int
	 * @param $recopyeditRevision int
	 */
	function selectRecopyeditRevision($articleId, $recopyeditRevision) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);	
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
	
		$sectionEditorSubmission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
	
		if ($sectionEditorSubmission->getCopyeditorId() != null && $sectionEditorSubmission->getCopyeditorDateFinalCompleted() != null) {
			// Mark the current copyedit assignment as "replaced".
			$sectionEditorSubmission->setCopyeditorReplaced(1);
			
			// Create the new copyedit assignment and populate.
			$newCopyeditAssignment = &new CopyeditorSubmission();
			$newCopyeditAssignment->setArticleId($sectionEditorSubmission->getArticleId());
			$newCopyeditAssignment->setCopyeditorId($sectionEditorSubmission->getCopyeditorId());
			
			// Take the selected copyedit revision and duplicate it, for use as the default copyedit revision
			// for the next round of copyediting.
			$articleFileManager->duplicateCopyeditFile($sectionEditorSubmission->getCopyeditFileId(), $recopyeditRevision);
			
			$copyeditRevision = $articleFileDao->getRevisionNumber($sectionEditorSubmission->getCopyeditFileId());
			$newCopyeditAssignment->setCopyeditRevision($copyeditRevision);
			
			$sectionEditorSubmission->setNewCopyeditAssignment($newCopyeditAssignment);
		}
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
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
				$fileId = $articleFileManager->uploadEditorFile($fileName, $sectionEditorSubmission->getEditorFileId());
			} else {
				$fileId = $articleFileManager->uploadEditorFile($fileName);
			}
		}
		
		$sectionEditorSubmission->setEditorFileId($fileId);

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		
		// Add log
		ArticleLog::logEvent($articleId, ARTICLE_LOG_EDITOR_FILE, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorSubmission->getEditorFileId(), 'log.editor.editorFile');
	}
	
	/**
	 * Upload the copyedit version of an article.
	 * @param $articleId int
	 */
	function uploadCopyeditVersion($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($sectionEditorSubmission->getCopyeditFileId() != null) {
				$copyeditFileId = $articleFileManager->uploadEditorFile($fileName, $sectionEditorSubmission->getCopyeditFileId());
			} else {
				$copyeditFileId = $articleFileManager->uploadEditorFile($fileName);
			}
		}
		
		$sectionEditorSubmission->setCopyeditFileId($copyeditFileId);

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Archive a submission.
	 * @param $articleId int
	 */
	function archiveSubmission($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$sectionEditorSubmission->setStatus(0);
		
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
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$sectionEditorSubmission->setStatus(1);
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	
		// Add log
		ArticleLog::logEvent($articleId, ARTICLE_LOG_EDITOR_RESTORE, ARTICLE_LOG_TYPE_EDITOR, 'log.editor.restored', array('articleId' => $articleId));
	}
}

?>
