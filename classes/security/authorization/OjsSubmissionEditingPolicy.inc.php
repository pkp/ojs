<?php
/**
 * @file classes/security/authorization/OjsSubmissionEditingPolicy.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OjsSubmissionEditingPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to OJS's submission editing components
 */

import('classes.security.authorization.OjsJournalPolicy');
import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');

class OjsSubmissionEditingPolicy extends OjsJournalPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function OjsSubmissionEditingPolicy(&$request, &$args, $submissionParameterName = 'articleId') {
		parent::OjsJournalPolicy($request);

		// Editorial components can only be called if there's a
		// valid section editor submission in the request.
		import('classes.security.authorization.SectionEditorSubmissionRequiredPolicy');
		$this->addPolicy(new SectionEditorSubmissionRequiredPolicy($request, $args, $submissionParameterName));

		// Create an "allow overrides" policy set that specifies
		// role-specific access to editorial components.
		$editorialRolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);


		//
		// Editor role
		//
		// Editors can access all operations for all submissions.
		$editorialRolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_EDITOR));


		//
		// Section editor role
		//
		// 1) Series editors can access all operations ...
		$sectionEditorPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
		$sectionEditorPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_SECTION_EDITOR));

		// 2) ... but only if the requested submission has been explicitly assigned to them.
		import('classes.security.authorization.SectionSubmissionAssignmentPolicy');
		$sectionEditorPolicy->addPolicy(new SectionSubmissionAssignmentPolicy($request));
		$editorialRolePolicy->addPolicy($sectionEditorPolicy);


		// Add the role-specific policies to this policy set.
		$this->addPolicy($editorialRolePolicy);
	}
}

?>
