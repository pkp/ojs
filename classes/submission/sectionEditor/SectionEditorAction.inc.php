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

class SectionEditorAction {

	/**
	 * Constructor.
	 */
	function SectionEditorAction() {

	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Records an editor's submission recommendation.
	 * @param $articleId int
	 * @param $recommendation int
	 */
	function recordRecommendation($articleId, $recommendation) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$sectionEditorSubmission->setRecommendation($recommendation);
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Assigns a reviewer to a submission.
	 * @param $articleId int
	 * @param $reviewerId int
	 */
	function addReviewer($articleId, $reviewerId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$reviewAssignment = new ReviewAssignment();
		$reviewAssignment->setReviewerId($reviewerId);
		$reviewAssignment->setDateAssigned(date('Y-m-d H:i:s'));
		$reviewAssignment->setDeclined(0);
		$reviewAssignment->setReplaced(0);
		
		$sectionEditorSubmission->AddReviewAssignment($reviewAssignment);
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);	
	}
	
	/**
	 * Unassigns a reviewer from a submission.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function clearReviewer($articleId, $reviewId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$sectionEditorSubmission->removeReviewAssignment($reviewId);

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Notifies a reviewer about a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function notifyReviewer($articleId, $reviewId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = new MailTemplate('ARTICLE_REVIEW_REQ');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		foreach ($sectionEditorSubmission->getReviewAssignments() as $reviewAssignment) {
			if ($reviewAssignment->getReviewId() == $reviewId) {
				$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
				
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
				$email->send();
		
				$reviewAssignment->setDateNotified(date('Y-m-d H:i:s'));

				$sectionEditorSubmission->updateReviewAssignment($reviewAssignment);				
				break;
			}
		}
			
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Reminds a reviewer about a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function remindReviewer($articleId, $reviewId, $send = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = new MailTemplate('ARTICLE_REVIEW_REQ');
		
		if ($send) {
			$reviewer = &$userDao->getUser(Request::getUserVar('reviewerId'));
			
			$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			
			$email->send();
		
		} else {
			$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
			foreach ($sectionEditorSubmission->getReviewAssignments() as $reviewAssignment) {
				if ($reviewAssignment->getReviewId() == $reviewId) {
					$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());		
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
	}
	
	/**
	 * Sets the due date for a review assignment.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $dueDate string
	 * @param $numWeeks int
	 */
	 function setDueDate($articleId, $reviewId, $dueDate, $numWeeks) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		foreach ($sectionEditorSubmission->getReviewAssignments() as $reviewAssignment) {
			if ($reviewAssignment->getReviewId() == $reviewId) {
				if ($dueDate != null) {
					$dueDateParts = explode("-", $dueDate);
					$today = getDate();
					if ($dueDateParts[0] >= $today['year'] && ($dueDateParts[1] > $today['mon'] || ($dueDateParts[1] == $today['mon'] && $dueDateParts[2] >= $today['mday']))) {
						$reviewAssignment->setDateDue(date("Y-m-d H:i:s", mktime(0, 0, 0, $dueDateParts[1], $dueDateParts[2], $dueDateParts[0])));
					}
				} else {
					$reviewAssignment->setDateDue($numWeeks);
				}
			
				$sectionEditorSubmission->updateReviewAssignment($reviewAssignment);	
				break;
			}
		}
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	 }
}

?>
