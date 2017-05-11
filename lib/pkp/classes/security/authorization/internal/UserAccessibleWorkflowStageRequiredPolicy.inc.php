<?php
/**
 * @file classes/security/authorization/internal/UserAccessibleWorkflowStageRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserAccessibleWorkflowStageRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy to deny access if an user assigned workflow stage is not found.
 *
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');
import('lib.pkp.classes.workflow.WorkflowStageDAO');

class UserAccessibleWorkflowStageRequiredPolicy extends AuthorizationPolicy {
	/** @var PKPRequest */
	var $_request;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function __construct($request) {
		parent::__construct('user.authorization.accessibleWorkflowStage');
		$this->_request = $request;
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		$request = $this->_request;
		$context = $request->getContext();
		$contextId = $context->getId();
		$user = $request->getUser();
		if (!is_a($user, 'User')) return AUTHORIZATION_DENY;

		$userId = $user->getId();
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		$workflowStages = WorkflowStageDAO::getWorkflowStageTranslationKeys();

		$accessibleWorkflowStages = array();

		foreach ($workflowStages as $stageId => $translationKey) {
			$accessibleStageRoles = $this->_getAccessibleStageRoles($userId, $contextId, $submission, $stageId);
			if (!empty($accessibleStageRoles)) {
				$accessibleWorkflowStages[$stageId] = $accessibleStageRoles;
			}
		}

		if (empty($accessibleWorkflowStages)) {
			return AUTHORIZATION_DENY;
		} else {
			$this->addAuthorizedContextObject(ASSOC_TYPE_ACCESSIBLE_WORKFLOW_STAGES, $accessibleWorkflowStages);
			return AUTHORIZATION_PERMIT;
		}
	}


	//
	// Private helper methods.
	//
	/**
	 * Check for roles that give access to the passed workflow stage.
	 * @param int $userId
	 * @param int $contextId
	 * @param Submission $submission
	 * @param int $stageId
	 * @return array
	 */
	function _getAccessibleStageRoles($userId, $contextId, &$submission, $stageId) {
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		$accessibleStageRoles = array();
		foreach ($userRoles as $roleId) {
			switch ($roleId) {
				case ROLE_ID_MANAGER:
					// Context managers have access to all submission stages.
					$accessibleStageRoles[] = $roleId;
					break;

				case ROLE_ID_ASSISTANT:
				case ROLE_ID_SUB_EDITOR:
				case ROLE_ID_AUTHOR:
					// The requested workflow stage has been assigned to them
					// in the requested submission.
					$stageAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($submission->getId(), $roleId, $stageId, $userId);
					if(!$stageAssignments->wasEmpty()) {
						$accessibleStageRoles[] = $roleId;
					}
					break;
				default:
					break;
			}
		}
		return $accessibleStageRoles;
	}
}

?>
