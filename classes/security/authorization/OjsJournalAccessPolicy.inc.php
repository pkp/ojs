<?php
/**
 * @file classes/security/authorization/OjsJournalAccessPolicy.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OjsJournalAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to OJS' journal setup components
 */

import('classes.security.authorization.internal.JournalPolicy');

class OjsJournalAccessPolicy extends JournalPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $roleAssignments array
	 */
	function OjsJournalAccessPolicy(&$request, $roleAssignments) {
		parent::JournalPolicy($request);

		// On journal level we don't have role-specific conditions
		// so we can simply add all role assignments. It's ok if
		// any of these role conditions permits access.
		$journalRolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		foreach($roleAssignments as $role => $operations) {
			$journalRolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
		}
		$this->addPolicy($journalRolePolicy);
	}
}

?>
