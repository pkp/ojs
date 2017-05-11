<?php

/**
 * @file pages/reviewer/PKPReviewerHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPReviewerHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer functions.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');
import('lib.pkp.classes.submission.reviewer.ReviewerAction');

class PKPReviewerHandler extends Handler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Display the submission review page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submission($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO'); /* @var $reviewerSubmissionDao ReviewerSubmissionDAO */
		$reviewerSubmission = $reviewerSubmissionDao->getReviewerSubmission($reviewAssignment->getId());
		assert(is_a($reviewerSubmission, 'ReviewerSubmission'));

		$this->setupTemplate($request);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('submission', $reviewerSubmission);
		$reviewStep = max($reviewerSubmission->getStep(), 1);
		$userStep = (int) $request->getUserVar('step');
		$step = (int) (!empty($userStep) ? $userStep: $reviewStep);
		if ($step > $reviewStep) $step = $reviewStep; // Reviewer can't go past incomplete steps
		if ($step < 1 || $step > 4) fatalError('Invalid step!');
		$templateMgr->assign('reviewStep', $reviewStep);
		$templateMgr->assign('selected', $step - 1);

		$templateMgr->display('reviewer/review/reviewStepHeader.tpl');
	}

	/**
	 * Display a step tab contents in the submission review page.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function step($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		$reviewId = (int) $reviewAssignment->getId();
		assert(!empty($reviewId));

		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO'); /* @var $reviewerSubmissionDao ReviewerSubmissionDAO */
		$reviewerSubmission = $reviewerSubmissionDao->getReviewerSubmission($reviewAssignment->getId());
		assert(is_a($reviewerSubmission, 'ReviewerSubmission'));

		$this->setupTemplate($request);

		$reviewStep = max($reviewerSubmission->getStep(), 1); // Get the current saved step from the DB
		$userStep = (int) $request->getUserVar('step');
		$step = (int) (!empty($userStep) ? $userStep: $reviewStep);
		if ($step > $reviewStep) $step = $reviewStep; // Reviewer can't go past incomplete steps
		if ($step < 1 || $step > 4) fatalError('Invalid step!');

		if ($step < 4) {
			$formClass = "ReviewerReviewStep{$step}Form";
			import("lib.pkp.classes.submission.reviewer.form.$formClass");

			$reviewerForm = new $formClass($request, $reviewerSubmission, $reviewAssignment);

			if ($reviewerForm->isLocaleResubmit()) {
				$reviewerForm->readInputData();
			} else {
				$reviewerForm->initData();
			}
			return new JSONMessage(true, $reviewerForm->fetch($request));
		} else {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign('submission', $reviewerSubmission);
			$templateMgr->assign('step', 4);
			return $templateMgr->fetchJson('reviewer/review/reviewCompleted.tpl');
		}
	}

	/**
	 * Save a review step.
	 * @param $args array first parameter is the step being saved
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function saveStep($args, $request) {
		$step = (int)$request->getUserVar('step');
		if ($step<1 || $step>3) fatalError('Invalid step!');

		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		if ($reviewAssignment->getDateCompleted()) fatalError('Review already completed!');

		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission = $reviewerSubmissionDao->getReviewerSubmission($reviewAssignment->getId());
		assert(is_a($reviewerSubmission, 'ReviewerSubmission'));

		$formClass = "ReviewerReviewStep{$step}Form";
		import("lib.pkp.classes.submission.reviewer.form.$formClass");

		$reviewerForm = new $formClass($request, $reviewerSubmission, $reviewAssignment);
		$reviewerForm->readInputData();

		if ($reviewerForm->validate()) {
			$reviewerForm->execute($request);
			$json = new JSONMessage(true);
			$json->setEvent('setStep', $step+1);
			return $json;
		} else {
			return new JSONMessage(true, $reviewerForm->fetch($request));
		}
	}

	/**
	 * Show a form for the reviewer to enter regrets into.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function showDeclineReview($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */

		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission = $reviewerSubmissionDao->getReviewerSubmission($reviewAssignment->getId());
		assert(is_a($reviewerSubmission, 'ReviewerSubmission'));

		$this->setupTemplate($request);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('submissionId', $reviewerSubmission->getId());

		// Provide the email body to the template
		$reviewerAction = new ReviewerAction();
		$email = $reviewerAction->getResponseEmail($reviewerSubmission, $reviewAssignment, $request, 1);
		$templateMgr->assign('declineMessageBody', $email->getBody());

		return $templateMgr->fetchJson('reviewer/review/modal/regretMessage.tpl');
	}

	/**
	 * Save the reviewer regrets form and decline the review.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveDeclineReview($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		if ($reviewAssignment->getDateCompleted()) fatalError('Review already completed!');

		$reviewId = (int) $reviewAssignment->getId();
		$declineReviewMessage = $request->getUserVar('declineReviewMessage');

		// Decline the review
		$reviewerAction = new ReviewerAction();
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$reviewerAction->confirmReview($request, $reviewAssignment, $submission, 1, $declineReviewMessage);

		$dispatcher = $request->getDispatcher();
		return $request->redirectUrlJson($dispatcher->url($request, ROUTE_PAGE, null, 'index'));
	}

	/**
	 * Setup common template variables.
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_APP_SUBMISSION,
			LOCALE_COMPONENT_APP_COMMON,
			LOCALE_COMPONENT_PKP_GRID,
			LOCALE_COMPONENT_PKP_REVIEWER
		);
	}


	//
	// Private helper methods
	//
	function _retrieveStep() {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */
		$reviewId = (int) $reviewAssignment->getId();
		assert(!empty($reviewId));
		return $reviewId;
	}
}

?>
