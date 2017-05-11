<?php
/**
 * @file classes/security/authorization/PKPPublicAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPublicAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to handler operations based on an
 *  operation whitelist.
 */

import('lib.pkp.classes.security.authorization.HandlerOperationPolicy');

class PKPPublicAccessPolicy extends HandlerOperationPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $operations array|string either a single operation or a list of operations that
	 *  this policy is targeting.
	 * @param $message string a message to be displayed if the authorization fails
	 */
	function __construct($request, $operations, $message = 'user.authorization.privateOperation') {
		parent::__construct($request, $operations, $message);
	}


	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		if ($this->_checkOperationWhitelist()) {
			return AUTHORIZATION_PERMIT;
		} else {
			return AUTHORIZATION_DENY;
		}
	}
}

?>
