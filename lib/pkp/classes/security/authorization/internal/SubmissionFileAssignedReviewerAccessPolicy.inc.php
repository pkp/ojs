<?php
/**
 * @file classes/security/authorization/internal/SubmissionFileAssignedReviewerAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileAssignedReviewerAccessPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Submission file policy to check if the current user is an assigned
 * 	reviewer of the file.
 *
 */

import('lib.pkp.classes.security.authorization.internal.SubmissionFileBaseAccessPolicy');

class SubmissionFileAssignedReviewerAccessPolicy extends SubmissionFileBaseAccessPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function __construct($request, $fileIdAndRevision = null) {
		parent::__construct($request, $fileIdAndRevision);
	}


	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		$request = $this->getRequest();

		// Get the user
		$user = $request->getUser();
		if (!is_a($user, 'PKPUser')) return AUTHORIZATION_DENY;

		// Get the submission file
		$submissionFile = $this->getSubmissionFile($request);
		if (!is_a($submissionFile, 'SubmissionFile')) return AUTHORIZATION_DENY;

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignments = $reviewAssignmentDao->getByUserId($user->getId());
		$reviewFilesDao = DAORegistry::getDAO('ReviewFilesDAO');
		foreach ($reviewAssignments as $reviewAssignment) {
			if (!$reviewAssignment->getDateConfirmed()) continue;

			if (
				$submissionFile->getSubmissionId() == $reviewAssignment->getSubmissionId() &&
				$submissionFile->getFileStage() == SUBMISSION_FILE_REVIEW_FILE &&
				$reviewFilesDao->check($reviewAssignment->getId(), $submissionFile->getFileId())
			) {
				return AUTHORIZATION_PERMIT;
			}
		}

		// If a pass condition wasn't found above, deny access.
		return AUTHORIZATION_DENY;
	}
}

?>
