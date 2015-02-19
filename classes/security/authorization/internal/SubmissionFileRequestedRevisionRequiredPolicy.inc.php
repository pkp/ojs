<?php
/**
 * @file classes/security/authorization/internal/SubmissionFileRequestedRevisionRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileRequestedRevisionRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Submission file policy to ensure we have a viewable file that is part of
 * a review round with the requested revision decision.
 *
 */

import('lib.pkp.classes.security.authorization.internal.PKPSubmissionFileRequestedRevisionRequiredPolicy');

class SubmissionFileRequestedRevisionRequiredPolicy extends PKPSubmissionFileRequestedRevisionRequiredPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function SubmissionFileRequestedRevisionRequiredPolicy($request, $fileIdAndRevision = null) {
		parent::PKPSubmissionFileRequestedRevisionRequiredPolicy($request, $fileIdAndRevision);
	}


	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {

		$parentAuthResponse = parent::effect();
		if ($parentAuthResponse == AUTHORIZATION_DENY) {
			return AUTHORIZATION_DENY;
		}

		$request =& $this->getRequest();
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /* @var $reviewRoundDao ReviewRoundDAO */

		// Get the submission file.
		$submissionFile = $this->getSubmissionFile($request);
		$reviewRound = $reviewRoundDao->getBySubmissionFileId($submissionFile->getFileId());

		// Make sure that the last review round editor decision is request revisions.
		$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO'); /* @var $editDecisionDao EditDecisionDAO */
		$reviewRoundDecisions = $editDecisionDao->getEditorDecisions($submissionFile->getSubmissionId(), $reviewRound->getStageId(), $reviewRound->getRound());
		if (empty($reviewRoundDecisions)) return AUTHORIZATION_DENY;
		$lastEditorDecision = array_pop($reviewRoundDecisions);
		if ($lastEditorDecision['decision'] != SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS) return AUTHORIZATION_DENY;

		// Made it through -- permit access.
		return AUTHORIZATION_PERMIT;
	}
}

?>
