<?php
/**
 * @file classes/security/authorization/internal/SubmissionFileMatchesSubmissionPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileMatchesSubmissionPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Submission file policy to check if the file belongs to the submission
 *
 * NB: This policy expects a previously authorized submission in the
 * authorization context.
 */

import('lib.pkp.classes.security.authorization.internal.SubmissionFileBaseAccessPolicy');

class SubmissionFileMatchesSubmissionPolicy extends SubmissionFileBaseAccessPolicy {
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
		// Get the submission file
		$request = $this->getRequest();
		$submissionFile = $this->getSubmissionFile($request);
		if (!is_a($submissionFile, 'SubmissionFile')) return AUTHORIZATION_DENY;

		// Get the submission
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		if (!is_a($submission, 'Submission')) return AUTHORIZATION_DENY;

		// Check if the submission file belongs to the submission.
		if ($submissionFile->getSubmissionId() == $submission->getId()) {
			// We add this submission file to the context submission files array.
			$submissionFilesArray = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILES);
			if (is_null($submissionFilesArray)) {
				$submissionFilesArray = array();
			}
			array_push($submissionFilesArray, $submissionFile);
			$this->addAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILES, $submissionFilesArray);

			// Save the submission to the authorization context.
			$this->addAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILE, $submissionFile);
			return AUTHORIZATION_PERMIT;
		} else {
			return AUTHORIZATION_DENY;
		}
	}
}

?>
