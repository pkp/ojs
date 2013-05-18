<?php

/**
 * @file pages/reviewer/ReviewerHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer functions.
 */

import('lib.pkp.pages.reviewer.PKPReviewerHandler');

class ReviewerHandler extends PKPReviewerHandler {
	/**
	 * Constructor
	 */
	function ReviewerHandler() {
		parent::PKPReviewerHandler();
		$this->addRoleAssignment(
			ROLE_ID_REVIEWER, array(
				'submission', 'step', 'saveStep',
				'showDeclineReview', 'saveDeclineReview', 'downloadFile'
			)
		);
	}

	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}
}

?>
