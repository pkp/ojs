<?php
/**
 * @file classes/security/authorization/internal/CopyeditorSubmissionAssignmentPolicy.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CopyeditorSubmissionAssignmentPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Class to control access of copyeditors to submissions.
 *
 * NB: This policy expects a previously authorized copyeditor submission in the
 * authorization context.
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class CopyeditorSubmissionAssignmentPolicy extends AuthorizationPolicy {
	/** @var PKPRequest */
	var $_request;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function CopyeditorSubmissionAssignmentPolicy(&$request) {
		parent::AuthorizationPolicy('user.authorization.copyeditorAssignmentMissing');
		$this->_request =& $request;
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Get the user
		$user =& $this->_request->getUser();
		if (!is_a($user, 'PKPUser')) return AUTHORIZATION_DENY;

		// Get the copyeditor submission
		$copyeditorSubmission =& $this->getAuthorizedContextObject(ASSOC_TYPE_ARTICLE);
		if (!is_a($copyeditorSubmission, 'CopyeditorSubmission')) return AUTHORIZATION_DENY;

		// Copyeditors can only access submissions
		// they have been explicitly assigned to.
		if ($copyeditorSubmission->getUserIdBySignoffType('SIGNOFF_COPYEDITING_INITIAL') != $user->getId()) return AUTHORIZATION_DENY;

		return AUTHORIZATION_PERMIT;
	}
}

?>
