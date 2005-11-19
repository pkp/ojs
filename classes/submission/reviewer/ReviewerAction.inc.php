<?php

/**
 * ReviewerAction.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * ReviewerAction class.
 *
 * $Id$
 */

import('submission.common.Action');

class ReviewerAction extends Action {

	/**
	 * Constructor.
	 */
	function ReviewerAction() {

	}

	/**
	 * Actions.
	 */
	 
	/**
	 * Records whether or not the reviewer accepts the review assignment.
	 * @param $user object
	 * @param $reviewerSubmission object
	 * @param $decline boolean
	 * @param $send boolean
	 */
	function confirmReview($reviewerSubmission, $decline, $send) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$reviewId = $reviewerSubmission->getReviewId();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;
		
		// Only confirm the review for the reviewer if 
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			import('mail.ArticleMailTemplate');
			$email = &new ArticleMailTemplate($reviewerSubmission, $decline?'REVIEW_DECLINE':'REVIEW_CONFIRM');
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('ReviewerAction::confirmReview', array(&$reviewerSubmission, &$email, $decline));
				if ($email->isEnabled()) {
					$email->setAssoc($decline?ARTICLE_EMAIL_REVIEW_DECLINE:ARTICLE_EMAIL_REVIEW_CONFIRM, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
					$email->send();
				}

				$reviewAssignment->setDeclined($decline);
				$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

				// Add log
				import('article.log.ArticleLog');
				import('article.log.ArticleEventLogEntry');

				$entry = &new ArticleEventLogEntry();
				$entry->setArticleId($reviewAssignment->getArticleId());
				$entry->setUserId($reviewer->getUserId());
				$entry->setDateLogged(Core::getCurrentDate());
				$entry->setEventType($decline?ARTICLE_LOG_REVIEW_DECLINE:ARTICLE_LOG_REVIEW_ACCEPT);
				$entry->setLogMessage($decline?'log.review.reviewDeclined':'log.review.reviewAccepted', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getArticleId(), 'round' => $reviewAssignment->getRound()));
				$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
				$entry->setAssocId($reviewAssignment->getReviewId());
				
				ArticleLog::logEventEntry($reviewAssignment->getArticleId(), $entry);

				return true;
			} else {
				if (!Request::getUserVar('continued')) {
					$editAssignment = &$reviewerSubmission->getEditor();
					if ($editAssignment && $editAssignment->getEditorId() != null) {
						$email->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
						$editorialContactName = $editAssignment->getEditorFullName();
					} else {
						$journal = &Request::getJournal();
						$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
						$editorialContactName = $journal->getSetting('contactName');
					}

					// Must explicitly set sender because we may be here on an access
					// key, in which case the user is not technically logged in
					$email->setFrom($reviewer->getEmail(), $reviewer->getFullName());
					$email->assignParams(array(
						'editorialContactName' => $editorialContactName,
						'reviewerName' => $reviewer->getFullName(),
						'reviewDueDate' => date('Y-m-d', strtotime($reviewAssignment->getDateDue()))
					));
				}
				$paramArray = array('reviewId' => $reviewId);
				if ($decline) $paramArray['declineReview'] = 1;
				$email->displayEditForm(Request::getPageUrl() . '/reviewer/confirmReview', $paramArray);
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Records the reviewer's submission recommendation.
	 * @param $reviewId int
	 * @param $recommendation int
	 * @param $send boolean
	 */
	function recordRecommendation(&$reviewerSubmission, $recommendation, $send) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		if (SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT > $recommendation || SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS < $recommendation) return true;
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewerSubmission->getReviewId());
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;
	
		// Only record the reviewers recommendation if
		// no recommendation has previously been submitted.
		if ($reviewAssignment->getRecommendation() == null) {
			import('mail.ArticleMailTemplate');
			$email = &new ArticleMailTemplate($reviewerSubmission, 'REVIEW_COMPLETE');
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('ReviewerAction::recordRecommendation', array(&$reviewerSubmission, &$email, $recommendation));
				if ($email->isEnabled()) {
					$email->setAssoc(ARTICLE_EMAIL_REVIEW_COMPLETE, ARTICLE_EMAIL_TYPE_REVIEW, $reviewerSubmission->getReviewId());
					$email->send();
				}

				$reviewAssignment->setRecommendation($recommendation);
				$reviewAssignment->setDateCompleted(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
		
				// Add log
				import('article.log.ArticleLog');
				import('article.log.ArticleEventLogEntry');

				$entry = &new ArticleEventLogEntry();
				$entry->setArticleId($reviewAssignment->getArticleId());
				$entry->setUserId($reviewer->getUserId());
				$entry->setDateLogged(Core::getCurrentDate());
				$entry->setEventType(ARTICLE_LOG_REVIEW_RECOMMENDATION);
				$entry->setLogMessage('log.review.reviewRecommendationSet', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getArticleId(), 'round' => $reviewAssignment->getRound()));
				$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
				$entry->setAssocId($reviewAssignment->getReviewId());
				
				ArticleLog::logEventEntry($reviewAssignment->getArticleId(), $entry);
			} else {
				if (!Request::getUserVar('continued')) {
					$editAssignment = &$reviewerSubmission->getEditor();
					if ($editAssignment && $editAssignment->getEditorId() != null) {
						$email->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
						$editorialContactName = $editAssignment->getEditorFullName();
					} else {
						$journal = &Request::getJournal();
						$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
						$editorialContactName = $journal->getSetting('contactName');
					}

					$reviewerRecommendationOptions = &ReviewAssignment::getReviewerRecommendationOptions();

					// Must explicitly set sender because we may be here on an access
					// key, in which case the user is not technically logged in
					$email->setFrom($reviewer->getEmail(), $reviewer->getFullName());

					$email->assignParams(array(
						'editorialContactName' => $editorialContactName,
						'reviewerName' => $reviewer->getFullName(),
						'articleTitle' => strip_tags($reviewerSubmission->getArticleTitle()),
						'recommendation' => Locale::translate($reviewerRecommendationOptions[$recommendation])
					));
				}
			
				$email->displayEditForm(Request::getPageUrl() . '/reviewer/recordRecommendation',
					array('reviewId' => $reviewerSubmission->getReviewId(), 'recommendation' => $recommendation)
				);
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Upload the annotated version of an article.
	 * @param $reviewId int
	 */
	function uploadReviewerVersion($reviewId) {
		import("file.ArticleFileManager");
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		$articleFileManager = &new ArticleFileManager($reviewAssignment->getArticleId());
		
		// Only upload the file if the reviewer has yet to submit a recommendation
		if ($reviewAssignment->getRecommendation() == null && !$reviewAssignment->getCancelled()) {
			$fileName = 'upload';
			if ($articleFileManager->uploadedFileExists($fileName)) {
				HookRegistry::call('ReviewerAction::uploadReviewFile', array(&$reviewAssignment));
				if ($reviewAssignment->getReviewerFileId() != null) {
					$fileId = $articleFileManager->uploadReviewFile($fileName, $reviewAssignment->getReviewerFileId());
				} else {
					$fileId = $articleFileManager->uploadReviewFile($fileName);
				}
			}
		}
		
		if (isset($fileId) && $fileId != 0) {
			$reviewAssignment->setReviewerFileId($fileId);
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
	
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');

			$userDao =& DAORegistry::getDAO('UserDAO');
			$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());

			$entry = &new ArticleEventLogEntry();
			$entry->setArticleId($reviewAssignment->getArticleId());
			$entry->setUserId($reviewer->getUserId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(ARTICLE_LOG_REVIEW_FILE);
			$entry->setLogMessage('log.review.reviewerFile');
			$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getReviewId());
			
			ArticleLog::logEventEntry($reviewAssignment->getArticleId(), $entry);
		}
	}

	/**
	* Delete an annotated version of an article.
	* @param $reviewId int
	* @param $fileId int
	* @param $revision int If null, then all revisions are deleted.
	*/
        function deleteReviewerVersion($reviewId, $fileId, $revision = null) {
		import("file.ArticleFileManager");
		
		$articleId = Request::getUserVar('articleId');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		if (!HookRegistry::call('ReviewerAction::deleteReviewerVersion', array(&$reviewAssignment, &$fileId, &$revision))) {
			$articleFileManager = &new ArticleFileManager($reviewAssignment->getArticleId());
			$articleFileManager->deleteFile($fileId, $revision);
		}
        }
	
	/**
	 * View reviewer comments.
	 * @param $user object Current user
	 * @param $article object
	 * @param $reviewId int
	 */
	function viewPeerReviewComments(&$user, &$article, $reviewId) {
		if (!HookRegistry::call('ReviewerAction::viewPeerReviewComments', array(&$user, &$article, &$reviewId))) {
			import("submission.form.comment.PeerReviewCommentForm");
		
			$commentForm = &new PeerReviewCommentForm($article, $reviewId, ROLE_ID_REVIEWER);
			$commentForm->setUser($user);
			$commentForm->initData();
			$commentForm->setData('reviewId', $reviewId);
			$commentForm->display();
		}
	}
	
	/**
	 * Post reviewer comments.
	 * @param $user object Current user
	 * @param $article object
	 * @param $reviewId int
	 * @param $emailComment boolean
	 */
	function postPeerReviewComment(&$user, &$article, $reviewId, $emailComment) {
		if (!HookRegistry::call('ReviewerAction::postPeerReviewComment', array(&$user, &$article, &$reviewId, &$emailComment))) {
			import("submission.form.comment.PeerReviewCommentForm");
		
			$commentForm = &new PeerReviewCommentForm($article, $reviewId, ROLE_ID_REVIEWER);
			$commentForm->setUser($user);
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
	
	//
	// Misc
	//
	
	/**
	 * Download a file a reviewer has access to.
	 * @param $reviewId int
	 * @param $article object
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadReviewerFile($reviewId, $article, $fileId, $revision = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$journal = &Request::getJournal();

		$canDownload = false;
		
		// Reviewers have access to:
		// 1) The current revision of the file to be reviewed.
		// 2) Any file that he uploads.
		// 3) Any supplementary file that is visible to reviewers.
		if ((!$reviewAssignment->getDateConfirmed() || $reviewAssignment->getDeclined()) && $journal->getSetting('restrictReviewerFileAccess')) {
			// Restrict files until review is accepted
		} else if ($reviewAssignment->getReviewFileId() == $fileId) {
			if ($revision != null) {
				$canDownload = ($reviewAssignment->getReviewRevision() == $revision);
			}
		} else if ($reviewAssignment->getReviewerFileId() == $fileId) {
			$canDownload = true;
		} else {
			foreach ($reviewAssignment->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId && $suppFile->getShowReviewers()) {
					$canDownload = true;
				}
			}
		}
		
		$result = false;
		if (!HookRegistry::call('ReviewerAction::downloadReviewerFile', array(&$article, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($article->getArticleId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}

	/**
	 * Edit comment.
	 * @param $commentId int
	 */
	function editComment ($article, $comment, $reviewId) {
		if (!HookRegistry::call('ReviewerAction::editComment', array(&$article, &$comment, &$reviewId))) {
			import ("submission.form.comment.EditCommentForm");

			$commentForm =& new EditCommentForm ($article, $comment);
			$commentForm->initData();
			$commentForm->setData('reviewId', $reviewId);
			$commentForm->display(array('reviewId' => $reviewId));
		}
	}
}

?>
