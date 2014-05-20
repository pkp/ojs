<?php
/**
 * @file classes/security/authorization/SignoffAccessPolicy.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SignoffAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to signoffs in OJS.
 */

import('lib.pkp.classes.security.authorization.PKPSignoffAccessPolicy');

class SignoffAccessPolicy extends PKPSignoffAccessPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $roleAssignments array
	 * @param $mode int bitfield SIGNOFF_ACCESS_...
	 * @param $stageId int
	 */
	function SignoffAccessPolicy($request, $args, $roleAssignments, $mode, $stageId) {
		parent::PKPSignoffAccessPolicy($request, $args, $roleAssignments, $mode, $stageId);

		$signoffAccessPolicy = $this->_baseSignoffAccessPolicy;
		//
		// Series editor role
		//
		if (isset($roleAssignments[ROLE_ID_SUB_EDITOR])) {
			// 1) Section editors can access all operations on signoffs ...
			$sectionEditorFileAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$sectionEditorFileAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_SUB_EDITOR, $roleAssignments[ROLE_ID_SUB_EDITOR]));

			// 2) ... but only if the requested signoff submission is part of their series.
			import('classes.security.authorization.internal.SectionAssignmentPolicy');
			$sectionEditorFileAccessPolicy->addPolicy(new SectionAssignmentPolicy($request));
			$signoffAccessPolicy->addPolicy($sectionEditorFileAccessPolicy);
		}
	}
}

?>
