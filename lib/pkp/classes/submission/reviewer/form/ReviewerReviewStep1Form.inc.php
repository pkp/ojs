<?php

/**
 * @file classes/submission/reviewer/form/ReviewerReviewStep1Form.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewStep1Form
 * @ingroup submission_reviewer_form
 *
 * @brief Form for Step 1 of a review.
 */

import('lib.pkp.classes.submission.reviewer.form.ReviewerReviewForm');

class ReviewerReviewStep1Form extends ReviewerReviewForm {
	/**
	 * Constructor.
	 * @param $request PKPRequest
	 * @param $reviewerSubmission ReviewerSubmission
	 */
	function __construct($request, $reviewerSubmission, $reviewAssignment) {
		parent::__construct($request, $reviewerSubmission, $reviewAssignment, 1);
	}


	//
	// Implement protected template methods from Form
	//
	/**
	 * @see Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		// Add submission parameters.
		$submission = $this->getReviewerSubmission();
		$templateMgr->assign('completedSteps', $submission->getStatus());
		$templateMgr->assign('competingInterestsText', $submission->getCompetingInterests());

		// Add review assignment.
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getById($submission->getReviewId());
		$templateMgr->assign('reviewAssignment', $reviewAssignment);

		// Add reviewer request text.
		$templateMgr->assign('reviewerRequest', __('reviewer.step1.requestBoilerplate'));

		//
		// Assign the link actions
		//
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		import('lib.pkp.classes.linkAction.request.ConfirmationModal');

		// "View metadata" action.
		import('lib.pkp.controllers.modals.submissionMetadata.linkAction.ReviewerViewMetadataLinkAction');
		$viewMetadataLinkAction = new ReviewerViewMetadataLinkAction($request, $reviewAssignment->getSubmissionId(), $reviewAssignment->getId());
		$templateMgr->assign('viewMetadataAction', $viewMetadataLinkAction);

		// include the confirmation modal for competing interests if the context has them.
		if ($context->getLocalizedSetting('competingInterests') != '') {
			import('lib.pkp.controllers.confirmationModal.linkAction.ViewCompetingInterestGuidelinesLinkAction');
			$competingInterestsAction = new ViewCompetingInterestGuidelinesLinkAction($request);
			$templateMgr->assign('competingInterestsAction', $competingInterestsAction);
		}
		// Instantiate the view review guidelines confirmation modal.
		$aboutDueDateAction = new LinkAction('viewReviewGuidelines',
			new ConfirmationModal(
				__('reviewer.aboutDueDates.text'),
				__('reviewer.aboutDueDates'),
				'modal_information', null, '',
				false
			),
			__('reviewer.aboutDueDates')
		);

		$templateMgr->assign('aboutDueDatesAction', $aboutDueDateAction);

		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$declineReviewLinkAction = new LinkAction('declineReview',
			new AjaxModal(
				$request->url(null, null, 'showDeclineReview', $reviewAssignment->getSubmissionId()),
				__('reviewer.submission.declineReview')
			)
		);
		$templateMgr->assign('declineReviewAction', $declineReviewLinkAction);

		return parent::fetch($request);
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('competingInterestOption', 'competingInterestsText'));
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$reviewerSubmission = $this->getReviewerSubmission();

		// Set competing interests.
		if ($this->getData('competingInterestOption') == 'hasCompetingInterests') {
			$reviewerSubmission->setCompetingInterests($this->request->getUserVar('competingInterestsText'));
		} else {
			$reviewerSubmission->setCompetingInterests(null);
		}

		// Set review to next step.
		$this->updateReviewStepAndSaveSubmission($reviewerSubmission);

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getById($reviewerSubmission->getReviewId());
		// if the reviewer has not previously confirmed the review, then
		// Set that the reviewer has accepted the review.
		if (!$reviewAssignment->getDateConfirmed()) {
			$reviewerAction = new ReviewerAction();
			$reviewerAction->confirmReview($this->request, $reviewAssignment, $reviewerSubmission, false);
		}
	}
}

?>
