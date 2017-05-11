<?php
/**
 * @file classes/security/authorization/QueryAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to queries.
 */

import('lib.pkp.classes.security.authorization.internal.ContextPolicy');
import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');

class QueryAccessPolicy extends ContextPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $roleAssignments array
	 * @param $stageId int
	 */
	function __construct($request, $args, $roleAssignments, $stageId) {
		parent::__construct($request);

		// We need a valid workflow stage.
		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));

		// We need a query matching the submission in the request.
		import('lib.pkp.classes.security.authorization.internal.QueryRequiredPolicy');
		$this->addPolicy(new QueryRequiredPolicy($request, $args));

		// Authors, context managers and sub editors potentially have
		// access to queries. We'll have to define
		// differentiated policies for those roles in a policy set.
		$queryAccessPolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

		//
		// Managerial role
		//
		if (isset($roleAssignments[ROLE_ID_MANAGER])) {
			// Managers have all access to all queries.
			$queryAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_MANAGER, $roleAssignments[ROLE_ID_MANAGER]));
		}


		//
		// Assistants
		//
		if (isset($roleAssignments[ROLE_ID_ASSISTANT])) {

			// 1) Assistants can access all operations on queries...
			$assistantQueryAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$assistantQueryAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_ASSISTANT, $roleAssignments[ROLE_ID_ASSISTANT]));

			// 2) ... but only if they have access to the workflow stage.
			import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy'); // pulled from context-specific class path.
			$assistantQueryAccessPolicy->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));

			// 3) ... and the assistant is assigned to the query.
			import('lib.pkp.classes.security.authorization.internal.QueryAssignedToUserAccessPolicy');
			$assistantQueryAccessPolicy->addPolicy(new QueryAssignedToUserAccessPolicy($request));

			$queryAccessPolicy->addPolicy($assistantQueryAccessPolicy);
		}


		//
		// Authors
		//
		if (isset($roleAssignments[ROLE_ID_AUTHOR])) {
			// 1) Authors can access read operations on queries...
			$authorQueryAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$authorQueryAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_AUTHOR, $roleAssignments[ROLE_ID_AUTHOR]));

			// 2) ... but only if they are assigned to the workflow stage as an stage participant...
			import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
			$authorQueryAccessPolicy->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));

			// 3) ... and the author is assigned to the query.
			import('lib.pkp.classes.security.authorization.internal.QueryAssignedToUserAccessPolicy');
			$authorQueryAccessPolicy->addPolicy(new QueryAssignedToUserAccessPolicy($request));

			$queryAccessPolicy->addPolicy($authorQueryAccessPolicy);
		}

		//
		// Sub editor role
		//
		if (isset($roleAssignments[ROLE_ID_SUB_EDITOR])) {
			// 1) Sub editors can access all operations on submissions ...
			$subEditorQueryAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$subEditorQueryAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_SUB_EDITOR, $roleAssignments[ROLE_ID_SUB_EDITOR]));

			// 2) ... but only if they have been assigned to the requested submission.
			import('lib.pkp.classes.security.authorization.internal.UserAccessibleWorkflowStageRequiredPolicy');
			$subEditorQueryAccessPolicy->addPolicy(new UserAccessibleWorkflowStageRequiredPolicy($request));

			$queryAccessPolicy->addPolicy($subEditorQueryAccessPolicy);
		}
		$this->addPolicy($queryAccessPolicy);

		return $queryAccessPolicy;
	}
}

?>
