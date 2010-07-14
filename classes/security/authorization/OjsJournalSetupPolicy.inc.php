<?php
/**
 * @file classes/security/authorization/OjsJournalSetupPolicy.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OjsJournalSetupPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to OJS' journal setup components
 */

import('classes.security.authorization.OjsJournalPolicy');

class OjsJournalSetupPolicy extends OjsJournalPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $roleAssignments array
	 */
	function OjsJournalSetupPolicy(&$request, $roleAssignments) {
		parent::OjsJournalPolicy($request);

		// Only journal managers may access setup pages.
		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		$this->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_JOURNAL_MANAGER, $roleAssignments[ROLE_ID_JOURNAL_MANAGER], 'You are not a journal manager!'));
	}
}

?>
