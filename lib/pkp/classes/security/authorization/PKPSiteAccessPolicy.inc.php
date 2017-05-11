<?php
/**
 * @file classes/security/authorization/PKPSiteAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSiteAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to that makes sure that a user is logged in.
 */


define('SITE_ACCESS_ALL_ROLES', 0x01);

import('lib.pkp.classes.security.authorization.PolicySet');

class PKPSiteAccessPolicy extends PolicySet {
	/**
	 * Constructor
	 *
	 * @param $request PKPRequest
	 * @param $operations array|string either a single operation or a list of operations that
	 *  this policy is targeting.
	 * @param $roleAssignments array|int Either an array of role -> operation assignments or the constant SITE_ACCESS_ALL_ROLES
	 * @param $message string a message to be displayed if the authorization fails
	 */
	function __construct($request, $operations, $roleAssignments, $message = 'user.authorization.loginRequired') {
		parent::__construct();
		$siteRolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		if(is_array($roleAssignments)) {
			import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
			foreach($roleAssignments as $role => $operations) {
				$siteRolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
			}
		} elseif ($roleAssignments === SITE_ACCESS_ALL_ROLES) {
			import('lib.pkp.classes.security.authorization.PKPPublicAccessPolicy');
			$siteRolePolicy->addPolicy(new PKPPublicAccessPolicy($request, $operations));
		} else {
			fatalError('Invalid role assignments!');
		}
		$this->addPolicy($siteRolePolicy);
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Retrieve the user from the session.
		$request = $this->getRequest();
		$user = $request->getUser();

		if (!is_a($user, 'User')) {
			return AUTHORIZATION_DENY;
		}

		// Execute handler operation checks.
		return parent::effect();
	}
}

?>
