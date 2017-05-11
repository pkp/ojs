<?php
/**
 * @file classes/security/authorization/SubmissionAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Base class to control (write) access to submissions and (read) access to
 * submission details in OMP.
 */

import('lib.pkp.classes.security.authorization.internal.ContextPolicy');
import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');

class SubmissionAccessPolicy extends ContextPolicy {

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $roleAssignments array
	 * @param $submissionParameterName string the request parameter we
	 *  expect the submission id in.
	 */
	function __construct($request, $args, $roleAssignments, $submissionParameterName = 'submissionId') {
		parent::__construct($request);

		// We need a submission in the request.
		import('lib.pkp.classes.security.authorization.internal.SubmissionRequiredPolicy');
		$this->addPolicy(new SubmissionRequiredPolicy($request, $args, $submissionParameterName));

		// Authors, managers and sub editors potentially have
		// access to submissions. We'll have to define differentiated
		// policies for those roles in a policy set.
		$submissionAccessPolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

		//
		// Managerial role
		//
		if (isset($roleAssignments[ROLE_ID_MANAGER])) {
			// Managers have access to all submissions.
			$submissionAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_MANAGER, $roleAssignments[ROLE_ID_MANAGER]));
		}

		//
		// Author role
		//
		if (isset($roleAssignments[ROLE_ID_AUTHOR])) {
			// 1) Author role user groups can access whitelisted operations ...
			$authorSubmissionAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$authorSubmissionAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_AUTHOR, $roleAssignments[ROLE_ID_AUTHOR], 'user.authorization.authorRoleMissing'));

			// 2) ... if they meet one of the following requirements:
			$authorSubmissionAccessOptionsPolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

			// 2a) ...the requested submission is their own ...
			import('lib.pkp.classes.security.authorization.internal.SubmissionAuthorPolicy');
			$authorSubmissionAccessOptionsPolicy->addPolicy(new SubmissionAuthorPolicy($request));

			// 2b) ...OR, at least one workflow stage has been assigned to them in the requested submission.
			import('lib.pkp.classes.security.authorization.internal.UserAccessibleWorkflowStageRequiredPolicy');
			$authorSubmissionAccessOptionsPolicy->addPolicy(new UserAccessibleWorkflowStageRequiredPolicy($request));

			$authorSubmissionAccessPolicy->addPolicy($authorSubmissionAccessOptionsPolicy);
			$submissionAccessPolicy->addPolicy($authorSubmissionAccessPolicy);
		}


		//
		// Reviewer role
		//
		if (isset($roleAssignments[ROLE_ID_REVIEWER])) {
			// 1) Reviewers can access whitelisted operations ...
			$reviewerSubmissionAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$reviewerSubmissionAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_REVIEWER, $roleAssignments[ROLE_ID_REVIEWER]));

			// 2) ... but only if they have been assigned to the submission as reviewers.
			import('lib.pkp.classes.security.authorization.internal.ReviewAssignmentAccessPolicy');
			$reviewerSubmissionAccessPolicy->addPolicy(new ReviewAssignmentAccessPolicy($request));
			$submissionAccessPolicy->addPolicy($reviewerSubmissionAccessPolicy);
		}

		//
		// Assistant role
		//
		if (isset($roleAssignments[ROLE_ID_ASSISTANT])) {
			// 1) Assistants can access whitelisted operations ...
			$contextSubmissionAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$contextSubmissionAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_ASSISTANT, $roleAssignments[ROLE_ID_ASSISTANT]));

			// 2) ... but only if they have been assigned to the submission workflow.
			import('lib.pkp.classes.security.authorization.internal.UserAccessibleWorkflowStageRequiredPolicy');
			$contextSubmissionAccessPolicy->addPolicy(new UserAccessibleWorkflowStageRequiredPolicy($request));
			$submissionAccessPolicy->addPolicy($contextSubmissionAccessPolicy);
		}

		//
		// Sub editor role
		//
		if (isset($roleAssignments[ROLE_ID_SUB_EDITOR])) {
			// 1) Sub editors can access all operations on submissions ...
			$subEditorSubmissionAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$subEditorSubmissionAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_SUB_EDITOR, $roleAssignments[ROLE_ID_SUB_EDITOR]));

			// 2b) ... but only if they have been assigned to the requested submission.
			import('lib.pkp.classes.security.authorization.internal.UserAccessibleWorkflowStageRequiredPolicy');
			$subEditorSubmissionAccessPolicy->addPolicy(new UserAccessibleWorkflowStageRequiredPolicy($request));

			$submissionAccessPolicy->addPolicy($subEditorSubmissionAccessPolicy);
		}

		$this->addPolicy($submissionAccessPolicy);

		return $submissionAccessPolicy;
	}
}

?>
