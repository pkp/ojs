<?php
/**
 * @file classes/security/authorization/internal/UserAccessibleWorkflowStageRequiredPolicy.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserAccessibleWorkflowStagesRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy to deny access if an user assigned workflow stage is not found.
 *
 */

import('lib.pkp.classes.security.authorization.internal.PKPUserAccessibleWorkflowStageRequiredPolicy');

class UserAccessibleWorkflowStageRequiredPolicy extends PKPUserAccessibleWorkflowStageRequiredPolicy {

	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function UserAccessibleWorkflowStageRequiredPolicy($request) {
		parent::PKPUserAccessibleWorkflowStageRequiredPolicy($request);
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
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		$accessibleStageRoles = parent::_getAccessibleStageRoles($userId, $contextId, $submission, $stageId);

		foreach ($userRoles as $roleId) {
			switch ($roleId) {
				case ROLE_ID_SUB_EDITOR:
					// The requested submission must be part of their series...
					// and the requested workflow stage must be assigned to
					// them in the journal settings.
					import('classes.security.authorization.internal.SectionAssignmentRule');
					if (SectionAssignmentRule::effect($contextId, $submission->getSectionId(), $userId) &&
					$userGroupDao->userAssignmentExists($contextId, $userId, $stageId)) {
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
