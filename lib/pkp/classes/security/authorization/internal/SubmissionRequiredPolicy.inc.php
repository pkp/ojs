<?php
/**
 * @file classes/security/authorization/internal/SubmissionRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid submission.
 */

import('lib.pkp.classes.security.authorization.DataObjectRequiredPolicy');

class SubmissionRequiredPolicy extends DataObjectRequiredPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $submissionParameterName string the request parameter we expect
	 *  the submission id in.
	 */
	function __construct($request, &$args, $submissionParameterName = 'submissionId', $operations = null) {
		parent::__construct($request, $args, $submissionParameterName, 'user.authorization.invalidSubmission', $operations);

		$callOnDeny = array($request->getDispatcher(), 'handle404', array());
		$this->setAdvice(
			AUTHORIZATION_ADVICE_CALL_ON_DENY,
			$callOnDeny
		);
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see DataObjectRequiredPolicy::dataObjectEffect()
	 */
	function dataObjectEffect() {
		// Get the submission id.
		$submissionId = $this->getDataObjectId();
		if ($submissionId === false) return AUTHORIZATION_DENY;

		// Validate the submission id.
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($submissionId);
		if (!is_a($submission, 'Submission')) return AUTHORIZATION_DENY;

		// Validate that this submission belongs to the current context.
		$context = $this->_request->getContext();
		if ($context->getId() !== $submission->getContextId()) return AUTHORIZATION_DENY;

		// Save the submission to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_SUBMISSION, $submission);
		return AUTHORIZATION_PERMIT;
	}
}

?>
