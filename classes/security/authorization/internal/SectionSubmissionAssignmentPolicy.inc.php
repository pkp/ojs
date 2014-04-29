<?php
/**
 * @file classes/security/authorization/internal/SectionSubmissionAssignmentPolicy.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionSubmissionAssignmentPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Class to control access to journal sections.
 *
 * NB: This policy expects a previously authorized section editor
 * submission in the authorization context.
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class SectionSubmissionAssignmentPolicy extends AuthorizationPolicy {
	/** @var PKPRequest */
	var $_request;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function SectionSubmissionAssignmentPolicy(&$request) {
		parent::AuthorizationPolicy('user.authorization.sectionAssignment');
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

		// Get the section editor submission.
		$sectionEditorSubmission =& $this->getAuthorizedContextObject(ASSOC_TYPE_ARTICLE);
		if (!is_a($sectionEditorSubmission, 'SectionEditorSubmission')) return AUTHORIZATION_DENY;

		// Section editors can only access submissions in their series
		// that they have been explicitly assigned to.

		// 1) Retrieve the edit assignments
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditAssignmentsByArticleId($sectionEditorSubmission->getId());
		if (!is_a($editAssignments, 'DAOResultFactory')) return AUTHORIZATION_DENY;
		$editAssignmentsArray =& $editAssignments->toArray();

		// 2) Check whether the user is the article's editor,
		//    otherwise deny access.
		$foundAssignment = false;
		foreach ($editAssignmentsArray as $editAssignment) {
			if ($editAssignment->getEditorId() == $user->getId()) {
				if ($editAssignment->getCanEdit()) $foundAssignment = true;
				break;
			}
		}

		if ($foundAssignment) {
			return AUTHORIZATION_PERMIT;
		} else {
			return AUTHORIZATION_DENY;
		}
	}
}

?>
