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
			'dateDecided' => date('Y-m-d H:i:s')
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
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

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
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			$sectionEditorSubmission->removeReviewAssignment($reviewId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}		
	}
	
	/**
	 * Notifies a reviewer about a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function notifyReviewer($articleId, $reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = &new ArticleMailTemplate($articleId, 'ARTICLE_REVIEW_REQ');
		
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
			$email->setAssoc(ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
			$email->send();
	
			$reviewAssignment->setDateNotified(date('Y-m-d H:i:s'));
			
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);				
		}
	}
	
	/**
	 * Initiates a review.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function initiateReview($articleId, $reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			// Only initiate if the review has not already been
			// initiated.
			if ($reviewAssignment->getDateInitiated() == null) {
				$reviewAssignment->setDateInitiated(date('Y-m-d H:i:s'));

				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
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

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);	
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			// Only reinitiate if the review had been previously initiated
			// then cancelled.
			if ($reviewAssignment->getDateInitiated() != null && $reviewAssignment->getCancelled()) {
				$reviewAssignment->setDateInitiated(date('Y-m-d H:i:s'));
				$reviewAssignment->setCancelled(0);

				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
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
				$reviewAssignment->setDateInitiated(date('Y-m-d H:i:s'));

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
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			// Only cancel the review if it is currently not cancelled but has previously
			// been initiated.
			if ($reviewAssignment->getDateInitiated() != null && !$reviewAssignment->getCancelled()) {
				$reviewAssignment->setCancelled(1);
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
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
	 * Rates a reviewer for timeliness and quality of a review.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $timeliness int
	 * @param $quality int
	 */
	function rateReviewer($articleId, $reviewId, $timeliness = null, $quality = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
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
		}
	}
	
	/**
	 * Makes a reviewer's annotated version of an article available to the author.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $viewable boolean
	 */
	function makeReviewerFileViewable($articleId, $reviewId, $viewable = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			// Only make the annotated version of the article available
			// if one has actually been uploaded.
			if ($reviewAssignment->getReviewerFileId() != null) {
				$reviewAssignment->setReviewerFileViewable($viewable);
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);				
			}
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
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
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
		}
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	 }
	 
	/**
	 * Sets the reviewer recommendation for a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $recommendation int
	 */
	 function setReviewerRecommendation($articleId, $reviewId, $recommendation) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if ($reviewAssignment->getArticleId() == $articleId) {
			$reviewAssignment->setRecommendation($recommendation);
		
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);	
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
	}
	 
	/**
	 * Assigns a copyeditor to a submission.
	 * @param $articleId int
	 * @param $copyeditorId int
	 */
	function addCopyeditor($articleId, $copyeditorId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		// Check to see if the requested copyeditor is not already
		// assigned to copyedit this article.
		$assigned = $sectionEditorSubmissionDao->copyeditorExists($articleId, $reviewerId);
		
		// Only add the copyeditor if he has not already
		// been assigned to review this article.
		if (!$assigned) {
			$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
			$sectionEditorSubmission->setCopyeditorId($copyeditorId);
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);	
		}
	}
	
	/**
	 * Notifies a copyeditor about a copyedit assignment.
	 * @param $articleId int
	 */
	function notifyCopyeditor($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_REQ');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
			
		$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				
		//
		// FIXME: Assign correct values!
		//
		$paramArray = array(
			'reviewerName' => $copyeditor->getFullName(),
			'journalName' => "Hansen",
			'journalUrl' => "Hansen",
			'articleTitle' => $sectionEditorSubmission->getTitle(),
			'sectionName' => $sectionEditorSubmission->getSectionTitle(),
			'reviewerUsername' => "http://www.roryscoolsite.com",
			'reviewerPassword' => "Hansen",
			'principalContactName' => "Hansen"	
		);
		$email->assignParams($paramArray);
		$email->setAssoc(ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getCopyedId());
		$email->send();
		
		$sectionEditorSubmission->setCopyeditorDateNotified(Core::getCurrentDate());
			
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Thanks a copyeditor about a copyedit assignment.
	 * @param $articleId int
	 */
	function thankCopyeditor($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('sectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_ACK');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
			
		$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
			
		//
		// FIXME: Assign correct values!
		//	
		$paramArray = array(
			'reviewerName' => $copyeditor->getFullName(),
			'journalName' => "Hansen",
			'journalUrl' => "Hansen",
			'articleTitle' => $sectionEditorSubmission->getTitle(),
			'sectionName' => $sectionEditorSubmission->getSectionTitle(),
			'reviewerUsername' => "http://www.roryscoolsite.com",
			'reviewerPassword' => "Hansen",
			'principalContactName' => "Hansen"	
		);
		$email->assignParams($paramArray);
		$email->setAssoc(ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getCopyedId());
		$email->send();
		
		$sectionEditorSubmission->setCopyeditorDateAcknowledged(Core::getCurrentDate());

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Notifies the author that the copyedit is complete.
	 * @param $articleId int
	 */
	function notifyAuthorCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_REVIEW_AUTHOR');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		// Only notify the author if the initial copyedit has been completed.
		if ($sectionEditorSubmission->getCopyeditorDateCompleted() != null) {
			$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
			$email->addRecipient($author->getEmail(), $author->getFullName());
			
			//
			// FIXME: Assign correct values!
			//
			$paramArray = array(
				'reviewerName' => $author->getFullName(),
				'journalName' => "Hansen",
				'journalUrl' => "Hansen",
				'articleTitle' => $sectionEditorSubmission->getTitle(),
				'sectionName' => $sectionEditorSubmission->getSectionTitle(),
				'reviewerUsername' => "http://www.roryscoolsite.com",
				'reviewerPassword' => "Hansen",
				'principalContactName' => "Hansen"	
			);
			$email->assignParams($paramArray);
			$email->setAssoc(ARTICLE_EMAIL_TYPE_AUTHOR, $sectionEditorSubmission->getUserId());
			$email->send();
			
			$sectionEditorSubmission->setCopyeditorDateAuthorNotified(Core::getCurrentDate());
				
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}
	
	/**
	 * Thanks an author for completing editor / author review.
	 * @param $articleId int
	 */
	function thankAuthorCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('sectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_REVIEW_AUTHOR_COMP');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		// Only thank the author if the editor / author review has been completed.
		if ($sectionEditorSubmission->getCopyeditorDateAuthorCompleted() != null) {
			$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
			$email->addRecipient($author->getEmail(), $author->getFullName());
				
			//
			// FIXME: Assign correct values!
			//
			$paramArray = array(
				'reviewerName' => $author->getFullName(),
				'journalName' => "Hansen",
				'journalUrl' => "Hansen",
				'articleTitle' => $sectionEditorSubmission->getTitle(),
				'sectionName' => $sectionEditorSubmission->getSectionTitle(),
				'reviewerUsername' => "http://www.roryscoolsite.com",
				'reviewerPassword' => "Hansen",
				'principalContactName' => "Hansen"	
			);
			$email->assignParams($paramArray);
			$email->setAssoc(ARTICLE_EMAIL_TYPE_AUTHOR, $sectionEditorSubmission->getUserId());
			$email->send();
			
			$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged(Core::getCurrentDate());
	
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}
	
	/**
	 * Initiate final copyedit.
	 * @param $articleId int
	 */
	function initiateFinalCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('sectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_FINAL_REVIEW');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		// Only initiate the final copyedit if the editor / author review has been completed.
		if ($sectionEditorSubmission->getCopyeditorDateAuthorCompleted() != null) {
			$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
			$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				
			//
			// FIXME: Assign correct values!
			//
			$paramArray = array(
				'reviewerName' => $copyeditor->getFullName(),
				'journalName' => "Hansen",
				'journalUrl' => "Hansen",
				'articleTitle' => $sectionEditorSubmission->getTitle(),
				'sectionName' => $sectionEditorSubmission->getSectionTitle(),
				'reviewerUsername' => "http://www.roryscoolsite.com",
				'reviewerPassword' => "Hansen",
				'principalContactName' => "Hansen"	
			);
			$email->assignParams($paramArray);
			$email->setAssoc(ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getCopyedId());
			$email->send();
			
			$sectionEditorSubmission->setCopyeditorDateFinalNotified(Core::getCurrentDate());
	
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
		}
	}
	
	/**
	 * Thank copyeditor for completing final copyedit.
	 * @param $articleId int
	 */
	function thankFinalCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('sectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_FINAL_REVIEW_ACK');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		// Only thank the copyeditor if the final copyedit has been completed.
		if ($sectionEditorSubmission->getCopyeditorDateFinalCompleted() != null) {
			$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
			$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				
			//
			// FIXME: Assign correct values!
			//			
			$paramArray = array(
				'reviewerName' => $copyeditor->getFullName(),
				'journalName' => "Hansen",
				'journalUrl' => "Hansen",
				'articleTitle' => $sectionEditorSubmission->getTitle(),
				'sectionName' => $sectionEditorSubmission->getSectionTitle(),
				'reviewerUsername' => "http://www.roryscoolsite.com",
				'reviewerPassword' => "Hansen",
				'principalContactName' => "Hansen"	
			);
			$email->assignParams($paramArray);
			$email->setAssoc(ARTICLE_EMAIL_TYPE_COPYEDIT, $sectionEditorSubmission->getCopyedId());
			$email->send();
			
			$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged(Core::getCurrentDate());
			$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
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
	 * Upload the post-review version of an article.
	 * @param $articleId int
	 */
	function uploadPostReviewArticle($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($sectionEditorSubmission->getPostReviewFileId() != null) {
				$fileId = $articleFileManager->uploadEditorFile($fileName, $sectionEditorSubmission->getPostReviewFileId());
			} else {
				$fileId = $articleFileManager->uploadEditorFile($fileName);
			}
		}
		
		$sectionEditorSubmission->setPostReviewFileId($fileId);

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
}

?>
