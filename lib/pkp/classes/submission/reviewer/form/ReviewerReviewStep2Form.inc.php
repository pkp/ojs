<?php

/**
 * @file classes/submission/reviewer/form/ReviewerReviewStep2Form.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewStep2Form
 * @ingroup submission_reviewer_form
 *
 * @brief Form for Step 2 of a review.
 */

import('lib.pkp.classes.submission.reviewer.form.ReviewerReviewForm');

class ReviewerReviewStep2Form extends ReviewerReviewForm {
	/**
	 * Constructor.
	 * @param $reviewerSubmission ReviewerSubmission
	 */
	function __construct($request, $reviewerSubmission, $reviewAssignment) {
		parent::__construct($request, $reviewerSubmission, $reviewAssignment, 2);
	}


	//
	// Implement protected template methods from Form
	//
	/**
	 * @see Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$context = $this->request->getContext();

		$reviewAssignment = $this->getReviewAssignment();
		$reviewerGuidelines = $context->getLocalizedSetting($reviewAssignment->getStageId()==WORKFLOW_STAGE_ID_INTERNAL_REVIEW?'internalReviewGuidelines':'reviewGuidelines');
		if (empty($reviewerGuidelines)) {
			$reviewerGuidelines = __('reviewer.submission.noGuidelines');
		}
		$templateMgr->assign('reviewerGuidelines', $reviewerGuidelines);

		return parent::fetch($request);
	}


	/**
	 * @see Form::execute()
	 */
	function execute() {
		// Set review to next step.
		$this->updateReviewStepAndSaveSubmission($this->getReviewerSubmission());
	}

}

?>
