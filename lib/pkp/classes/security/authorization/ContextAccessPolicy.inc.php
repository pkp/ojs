<?php
/**
 * @file classes/security/authorization/ContextAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to PKP applications' setup components
 */

import('lib.pkp.classes.security.authorization.internal.ContextPolicy');

class ContextAccessPolicy extends ContextPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $roleAssignments array
	 */
	function __construct($request, $roleAssignments) {
		parent::__construct($request);

		// On context level we don't have role-specific conditions
		// so we can simply add all role assignments. It's ok if
		// any of these role conditions permits access.
		$contextRolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		foreach($roleAssignments as $role => $operations) {
			$contextRolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
		}
		$this->addPolicy($contextRolePolicy);
	}
}

?>
