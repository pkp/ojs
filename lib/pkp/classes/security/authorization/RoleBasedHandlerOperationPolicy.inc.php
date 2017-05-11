<?php
/**
 * @file classes/security/authorization/RoleBasedHandlerOperationPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleBasedHandlerOperationPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to handler operations via role based access
 *  control.
 */

import('lib.pkp.classes.security.authorization.HandlerOperationPolicy');

class RoleBasedHandlerOperationPolicy extends HandlerOperationPolicy {
	/** @var array the target roles */
	var $_roles = array();

	/** @var boolean */
	var $_allRoles;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $roles array|integer either a single role ID or an array of role ids
	 * @param $operations array|string either a single operation or a list of operations that
	 *  this policy is targeting.
	 * @param $message string a message to be displayed if the authorization fails
	 * @param $allRoles boolean whether all roles must match ("all of") or whether it is
	 *  enough for only one role to match ("any of").
	 */
	function __construct($request, $roles, $operations,
			$message = 'user.authorization.roleBasedAccessDenied',
			$allRoles = false) {
		parent::__construct($request, $operations, $message);

		// Make sure a single role doesn't have to be
		// passed in as an array.
		assert(is_integer($roles) || is_array($roles));
		if (!is_array($roles)) {
			$roles = array($roles);
		}
		$this->_roles = $roles;
		$this->_allRoles = $allRoles;
	}


	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Check whether the user has one of the allowed roles
		// assigned. If that's the case we'll permit access.
		// Get user roles grouped by context.
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		if (empty($userRoles)) return AUTHORIZATION_DENY;

		if (!$this->_checkUserRoleAssignment($userRoles)) return AUTHORIZATION_DENY;
		if (!$this->_checkOperationWhitelist()) return AUTHORIZATION_DENY;

		return AUTHORIZATION_PERMIT;
	}


	//
	// Private helper methods
	//
	/**
	 * Check whether the given user has been assigned
	 * to any of the allowed roles. If so then grant
	 * access.
	 * @param $userRoles array
	 * @return boolean
	 */
	function _checkUserRoleAssignment($userRoles) {
		// Find matching roles.
		$foundMatchingRole = false;
		foreach($this->_roles as $roleId) {
			$foundMatchingRole = in_array($roleId, $userRoles);

			if ($this->_allRoles) {
				if (!$foundMatchingRole) {
					// When the "all roles" flag is switched on then
					// one missing role is enough to fail.
					return false;
				}
			} else {
				if ($foundMatchingRole) {
					// When the "all roles" flag is not set then
					// one matching role is enough to succeed.
					return true;
				}
			}
		}

		if ($this->_allRoles) {
			// All roles matched, otherwise we'd have failed before.
			return true;
		} else {
			// None of the roles matched, otherwise we'd have succeeded already.
			return false;
		}
	}
}

?>
