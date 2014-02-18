<?php
/**
 * @file classes/security/authorization/WorkflowStageAccessPolicy.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowStageAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to OMP's submission workflow stage components
 */

import('lib.pkp.classes.security.authorization.internal.PKPWorkflowStageAccessPolicy');

class WorkflowStageAccessPolicy extends PKPWorkflowStageAccessPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request arguments
	 * @param $roleAssignments array
	 * @param $submissionParameterName string
	 * @param $stageId integer One of the WORKFLOW_STAGE_ID_* constants.
	 */
	function WorkflowStageAccessPolicy($request, &$args, $roleAssignments, $submissionParameterName = 'submissionId', $stageId) {
		parent::PKPWorkflowStageAccessPolicy($request, $args, $roleAssignments, $submissionParameterName, $stageId);

		// A workflow stage component can only be called if there's a
		// valid section editor submission in the request.
		import('classes.security.authorization.internal.SectionEditorSubmissionRequiredPolicy');
		$this->addPolicy(new SectionEditorSubmissionRequiredPolicy($request, $args, $submissionParameterName));

		// Add the user accessible workflow stages object to the authorized context.
		import('classes.security.authorization.internal.UserAccessibleWorkflowStageRequiredPolicy');
		$this->addPolicy(new UserAccessibleWorkflowStageRequiredPolicy($request));

		// Users can access all whitelisted operations for submissions and workflow stages...
		$roleBasedPolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		foreach ($roleAssignments as $roleId => $operations) {
			$roleBasedPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $roleId, $operations));
		}
		$this->addPolicy($roleBasedPolicy);

		// ... if they can access the requested workflow stage.
		import('lib.pkp.classes.security.authorization.internal.UserAccessibleWorkflowStagePolicy');
		$this->addPolicy(new UserAccessibleWorkflowStagePolicy($stageId));
	}
}

?>
