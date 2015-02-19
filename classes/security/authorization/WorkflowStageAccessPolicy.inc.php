<?php
/**
 * @file classes/security/authorization/WorkflowStageAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
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
	}

	/**
	 * Get the user-accessible workflow stage policy for this application
	 * @param $request PKPRequest
	 */
	protected function _addUserAccessibleWorkflowStageRequiredPolicy($request) {
		import('classes.security.authorization.internal.UserAccessibleWorkflowStageRequiredPolicy');
		$this->addPolicy(new UserAccessibleWorkflowStageRequiredPolicy($request));
	}
}

?>
