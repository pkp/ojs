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
		$reviewAssignment->setReviewFileViewable(0);
		
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
	 * Rates a reviewer for timeliness and quality of a review.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $timeliness int
	 * @param $quality int
	 */
	function rateReviewer($articleId, $reviewId, $timeliness = null, $quality = null) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		foreach ($sectionEditorSubmission->getReviewAssignments() as $reviewAssignment) {
			if ($reviewAssignment->getReviewId() == $reviewId) {
				$reviewAssignment->setTimeliness($timeliness);
				$reviewAssignment->setQuality($quality);
	
				$sectionEditorSubmission->updateReviewAssignment($reviewAssignment);				
				break;
			}
		}

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Makes a reviewer's annotated version of an article available to the author.
	 * @param $articleId int
	 * @param $reviewId int
	 * @param $viewable boolean
	 */
	function makeReviewFileViewable($articleId, $reviewId, $viewable = false) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		foreach ($sectionEditorSubmission->getReviewAssignments() as $reviewAssignment) {
			if ($reviewAssignment->getReviewId() == $reviewId) {
				$reviewAssignment->setReviewFileViewable($viewable);
	
				$sectionEditorSubmission->updateReviewAssignment($reviewAssignment);				
				break;
			}
		}

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
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
	 
	/**
	 * Assigns a copyeditor to a submission.
	 * @param $articleId int
	 * @param $copyeditorId int
	 */
	function addCopyeditor($articleId, $copyeditorId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$sectionEditorSubmission->setCopyeditorId($copyeditorId);
		
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);	
	}
	
	/**
	 * Notifies a copyeditor about a copyedit assignment.
	 * @param $articleId int
	 */
	function notifyCopyeditor($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = new MailTemplate('COPYEDIT_REQ');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
			
		$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				
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
		$email->send();
		
		$sectionEditorSubmission->setCopyeditorDateNotified(date('Y-m-d H:i:s'));
			
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Thanks a copyeditor about a copyedit assignment.
	 * @param $articleId int
	 */
	function thankCopyeditor($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('sectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = new MailTemplate('COPYEDIT_ACK');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
			
		$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				
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
		$email->send();
		
		$sectionEditorSubmission->setCopyeditorDateAcknowledged(date('Y-m-d H:i:s'));

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Notifies the author that the copyedit is complete.
	 * @param $articleId int
	 */
	function notifyAuthorCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = new MailTemplate('COPYEDIT_REVIEW_AUTHOR');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
			
		$email->addRecipient($author->getEmail(), $author->getFullName());
				
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
		$email->send();
		
		$sectionEditorSubmission->setCopyeditorDateAuthorNotified(date('Y-m-d H:i:s'));
			
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Thanks an author for completing editor / author review.
	 * @param $articleId int
	 */
	function thankAuthorCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('sectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = new MailTemplate('COPYEDIT_REVIEW_AUTHOR_COMP');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$author = &$userDao->getUser($sectionEditorSubmission->getUserId());
			
		$email->addRecipient($author->getEmail(), $author->getFullName());
				
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
		$email->send();
		
		$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged(date('Y-m-d H:i:s'));
	
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Initiate final copyedit.
	 * @param $articleId int
	 */
	function initiateFinalCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('sectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = new MailTemplate('COPYEDIT_FINAL_REVIEW');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
			
		$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				
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
		$email->send();
		
		$sectionEditorSubmission->setCopyeditorDateFinalNotified(date('Y-m-d H:i:s'));
	
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
	}
	
	/**
	 * Thanks copyeditor for completing final copyedit.
	 * @param $articleId int
	 */
	function thankFinalCopyedit($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('sectionEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = new MailTemplate('COPYEDIT_FINAL_REVIEW_ACK');
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$copyeditor = &$userDao->getUser($sectionEditorSubmission->getCopyeditorId());
			
		$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
				
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
		$email->send();
		
		$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged(date('Y-m-d H:i:s'));
	
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
