<?php

/**
 * @file controllers/grid/users/reviewer/ReviewerGridHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerGridHandler
 * @ingroup controllers_grid_users_reviewer
 *
 * @brief Handle reviewer grid requests.
 */

import('lib.pkp.classes.controllers.grid.users.reviewer.PKPReviewerGridHandler');

class ReviewerGridHandler extends PKPReviewerGridHandler {
	/**
	 * Constructor
	 */
	function ReviewerGridHandler() {
		parent::PKPReviewerGridHandler();
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = $request->getUserVar('stageId'); // This is being validated in WorkflowStageAccessPolicy

		// Not all actions need a stageId. Some work off the reviewAssignment which has the type and round.
		$this->_stageId = (int)$stageId;

		// Get the stage access policy
		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$ompWorkflowStageAccessPolicy = new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId);

		// Add policy to ensure there is a review round id.
		import('lib.pkp.classes.security.authorization.internal.ReviewRoundRequiredPolicy');
		$ompWorkflowStageAccessPolicy->addPolicy(new ReviewRoundRequiredPolicy($request, $args, 'reviewRoundId', $this->_getReviewRoundOps()));

		// Add policy to ensure there is a review assignment for certain operations.
		import('lib.pkp.classes.security.authorization.internal.ReviewAssignmentRequiredPolicy');
		$ompWorkflowStageAccessPolicy->addPolicy(new ReviewAssignmentRequiredPolicy($request, $args, 'reviewAssignmentId', $this->_getReviewAssignmentOps()));
		$this->addPolicy($ompWorkflowStageAccessPolicy);

		return parent::authorize($request, $args, $roleAssignments);
	}
}

?>
