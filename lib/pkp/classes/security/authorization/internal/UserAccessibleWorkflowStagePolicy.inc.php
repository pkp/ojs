<?php
/**
 * @file classes/security/authorization/internal/UserAccessibleWorkflowStagePolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserAccessibleWorkflowStagePolicy
 * @ingroup security_authorization_internal
 *
 * @brief Class to control access to a
 *
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class UserAccessibleWorkflowStagePolicy extends AuthorizationPolicy {

	/** @var int */
	var $_stageId;

	/**
	 * Constructor
	 * @param $stageId The one that will be checked against accessible
	 * user workflow stages.
	 */
	function __construct($stageId) {
		parent::__construct('user.authorization.accessibleWorkflowStage');
		$this->_stageId = $stageId;
	}


	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		$userAccessibleStages = $this->getAuthorizedContextObject(ASSOC_TYPE_ACCESSIBLE_WORKFLOW_STAGES);
		if (empty($userAccessibleStages)) return AUTHORIZATION_DENY;

		$stageId = $this->_stageId;

		if (array_key_exists($stageId, $userAccessibleStages)) {
			return AUTHORIZATION_PERMIT;
		}

		return AUTHORIZATION_DENY;
	}
}

?>
