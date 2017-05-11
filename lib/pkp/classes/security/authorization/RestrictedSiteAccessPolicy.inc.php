<?php
/**
 * @file classes/security/authorization/RestrictedSiteAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RestrictedSiteAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Policy enforcing restricted site access when the context
 *  contains such a setting.
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class RestrictedSiteAccessPolicy extends AuthorizationPolicy {
	/** @var PKPRouter */
	var $_router;

	/** @var Request */
	var $_request;

	/**
	 * Constructor
	 *
	 * @param $request PKPRequest
	 */
	function __construct($request) {
		parent::__construct('user.authorization.restrictedSiteAccess');
		$this->_request = $request;
		$this->_router = $request->getRouter();
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::applies()
	 */
	function applies() {
		$context = $this->_router->getContext($this->_request);
		return ( $context && $context->getSetting('restrictSiteAccess'));
	}

	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		if (is_a($this->_router, 'PKPPageRouter')) {
			$page = $this->_router->getRequestedPage($this->_request);
		} else {
			$page = null;
		}

		if (Validation::isLoggedIn() || in_array($page, $this->_getLoginExemptions())) {
			return AUTHORIZATION_PERMIT;
		} else {
			return AUTHORIZATION_DENY;
		}
	}

	//
	// Private helper method
	//
	/**
	 * Return the pages that can be accessed
	 * even while in restricted site mode.
	 *
	 * @return array
	 */
	function _getLoginExemptions() {
		return array('user', 'login', 'help', 'header', 'sidebar', 'payment');
	}
}

?>
