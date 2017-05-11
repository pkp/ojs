<?php

/**
 * @file controllers/grid/files/review/ReviewerReviewFilesGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewFilesGridDataProvider
 * @ingroup controllers_grid_files_review
 *
 * @brief Provide reviewer access to review file data for review file grids.
 */

import('lib.pkp.controllers.grid.files.review.ReviewGridDataProvider');

class ReviewerReviewFilesGridDataProvider extends ReviewGridDataProvider {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct(SUBMISSION_FILE_REVIEW_FILE);
	}


	//
	// Implement template methods from GridDataProvider
	//
	/**
	 * @see GridDataProvider::getAuthorizationPolicy()
	 * Override the parent class, which defines a Workflow policy, to allow
	 * reviewer access to this grid.
	 */
	function getAuthorizationPolicy($request, $args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$policy = new SubmissionAccessPolicy($request, $args, $roleAssignments);

		$stageId = $request->getUserVar('stageId');
		import('lib.pkp.classes.security.authorization.internal.WorkflowStageRequiredPolicy');
		$policy->addPolicy(new WorkflowStageRequiredPolicy($stageId));

		// Add policy to ensure there is a review round id.
		import('lib.pkp.classes.security.authorization.internal.ReviewRoundRequiredPolicy');
		$policy->addPolicy(new ReviewRoundRequiredPolicy($request, $args));

		// Add policy to ensure there is a review assignment for certain operations.
		import('lib.pkp.classes.security.authorization.internal.ReviewAssignmentRequiredPolicy');
		$policy->addPolicy(new ReviewAssignmentRequiredPolicy($request, $args, 'reviewAssignmentId'));

		return $policy;
	}

	/**
	 * @see ReviewerReviewFilesGridDataProvider
	 * Extend the parent class to filter out review round files that aren't allowed
	 * for this reviewer according to ReviewFilesDAO.
	 */
	function loadData() {
		$submissionFileData = parent::loadData();
		$reviewFilesDao = DAORegistry::getDAO('ReviewFilesDAO');
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		foreach ($submissionFileData as $fileId => $fileData) {
			if (!$reviewFilesDao->check($reviewAssignment->getId(), $fileId)) {
				// Not permitted; remove from list.
				unset($submissionFileData[$fileId]);
			}
		}
		return $submissionFileData;
	}

	/**
	 * @copydoc GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		return array_merge(parent::getRequestArgs(), array(
			'reviewAssignmentId' => $reviewAssignment->getId()
		));
	}
}

?>
