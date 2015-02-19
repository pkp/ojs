<?php

/**
 * @file pages/reviewer/SubmissionReviewHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionReviewHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for submission tracking.
 */

import('pages.reviewer.ReviewerHandler');

class SubmissionReviewHandler extends ReviewerHandler {
	/**
	 * Constructor
	 */
	function SubmissionReviewHandler() {
		parent::ReviewerHandler();
	}

	/**
	 * Display the submission review page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submission($args, $request) {
		$journal =& $request->getJournal();
		$reviewId = (int) array_shift($args);

		$this->validate($request, $reviewId);
		$user =& $this->user;
		$submission =& $this->submission;

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getById($reviewId);

		$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');

		if ($submission->getDateConfirmed() == null) {
			$confirmedStatus = 0;
		} else {
			$confirmedStatus = 1;
		}

		$this->setupTemplate(true, $reviewAssignment->getSubmissionId(), $reviewId);

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignment', $reviewAssignment);
		$templateMgr->assign('confirmedStatus', $confirmedStatus);
		$templateMgr->assign('declined', $submission->getDeclined());
		$templateMgr->assign('reviewFormResponseExists', $reviewFormResponseDao->reviewFormResponseExists($reviewId));
		$templateMgr->assign_by_ref('reviewFile', $reviewAssignment->getReviewFile());
		$templateMgr->assign_by_ref('reviewerFile', $submission->getReviewerFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign_by_ref('reviewGuidelines', $journal->getLocalizedSetting('reviewGuidelines'));

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		$templateMgr->assign('helpTopicId', 'editorial.reviewersRole.review');
		$templateMgr->display('reviewer/submission.tpl');
	}

	/**
	 * Confirm whether the review has been accepted or not.
	 * @param $args array optional
	 * @param $request PKPRequest
	 */
	function confirmReview($args, $request) {
		$reviewId = (int) $request->getUserVar('reviewId');
		$declineReview = $request->getUserVar('declineReview');

		$reviewerSubmissionDao =& DAORegistry::getDAO('ReviewerSubmissionDAO');

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;

		$this->setupTemplate();

		$decline = isset($declineReview) ? 1 : 0;

		if (!$reviewerSubmission->getCancelled()) {
			if (ReviewerAction::confirmReview($reviewerSubmission, $decline, $request->getUserVar('send'), $request)) {
				$request->redirect(null, null, 'submission', $reviewId);
			}
		} else {
			$request->redirect(null, null, 'submission', $reviewId);
		}
	}

	/**
	 * Save the competing interests statement, if allowed.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveCompetingInterests($args, &$request) {
		$reviewId = (int) $request->getUserVar('reviewId');
		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;

		if ($reviewerSubmission->getDateConfirmed() && !$reviewerSubmission->getDeclined() && !$reviewerSubmission->getCancelled() && !$reviewerSubmission->getRecommendation()) {
			$reviewerSubmissionDao =& DAORegistry::getDAO('ReviewerSubmissionDAO');
			$reviewerSubmission->setCompetingInterests($request->getUserVar('competingInterests'));
			$reviewerSubmissionDao->updateReviewerSubmission($reviewerSubmission);
		}

		$request->redirect(null, 'reviewer', 'submission', array($reviewId));
	}

	/**
	 * Record the reviewer recommendation.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function recordRecommendation($args, $request) {
		$reviewId = (int) $request->getUserVar('reviewId');
		$recommendation = (int) $request->getUserVar('recommendation');

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;

		$this->setupTemplate(true);

		if (!$reviewerSubmission->getCancelled()) {
			if (ReviewerAction::recordRecommendation($reviewerSubmission, $recommendation, $request->getUserVar('send'), $request)) {
				$request->redirect(null, null, 'submission', $reviewId);
			}
		} else {
			$request->redirect(null, null, 'submission', $reviewId);
		}
	}

	/**
	 * View the submission metadata
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewMetadata($args, $request) {
		$reviewId = (int) array_shift($args);
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;

		$this->setupTemplate(true, $articleId, $reviewId);

		ReviewerAction::viewMetadata($reviewerSubmission, $journal);
	}

	/**
	 * Upload the reviewer's annotated version of an article.
	 * @param $args array
	 * @param $request object
	 */
	function uploadReviewerVersion($args, $request) {
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->validate($request, $reviewId);
		$this->setupTemplate(true);

		ReviewerAction::uploadReviewerVersion($reviewId, $this->submission, $request);
		$request->redirect(null, null, 'submission', $reviewId);
	}

	/**
	 * Delete one of the reviewer's annotated versions of an article.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteReviewerVersion($args, $request) {
		$reviewId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);
		if (!$revision) $revision = null;

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;

		if (!$reviewerSubmission->getCancelled()) ReviewerAction::deleteReviewerVersion($reviewId, $fileId, $revision);
		$request->redirect(null, null, 'submission', $reviewId);
	}

	//
	// Misc
	//

	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 * @param $request PKPRequest
	 */
	function downloadFile($args, $request) {
		$reviewId = (int) array_shift($args);
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);
		if (!$revision) $revision = null;

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;

		if (!ReviewerAction::downloadReviewerFile($reviewId, $reviewerSubmission, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $reviewId);
		}
	}

	//
	// Review Form
	//

	/**
	 * Edit or preview review form response.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editReviewFormResponse($args, $request) {
		$reviewId = (int) array_shift($args);

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;
		$this->setupTemplate(true, $reviewerSubmission->getId(), $reviewId);

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewFormId = $reviewAssignment->getReviewFormId();
		if ($reviewFormId != null) {
			ReviewerAction::editReviewFormResponse($reviewId, $reviewFormId);
		}
	}

	/**
	 * Save review form response
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveReviewFormResponse($args, $request) {
		$reviewId = (int) array_shift($args);
		$reviewFormId = (int) array_shift($args);

		$this->validate($request, $reviewId);
		$this->setupTemplate(true);

		if (ReviewerAction::saveReviewFormResponse($reviewId, $reviewFormId, $request)) {
			$request->redirect(null, null, 'submission', $reviewId);
		}
	}
}

?>
