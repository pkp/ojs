<?php
/**
 * @file classes/security/authorization/OjsJournalMustPublishPolicy.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OjsAuthorDashboardAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to OMP author dashboard.
 */

import('lib.pkp.classes.security.authorization.PolicySet');
import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class OjsJournalMustPublishPolicy extends AuthorizationPolicy {

	var $_context;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request arguments
	 * @param $roleAssignments array
	 */
	function OjsJournalMustPublishPolicy($request) {
		parent::AuthorizationPolicy('user.authorization.journalDoesNotPublish');
		$this->_context = $request->getContext();
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	function effect() {

		if ($this->_context && $this->_context->getSetting('publishingMode') == PUBLISHING_MODE_NONE) {
			return AUTHORIZATION_DENY;
		}

		return AUTHORIZATION_PERMIT;
	}
}
?>
