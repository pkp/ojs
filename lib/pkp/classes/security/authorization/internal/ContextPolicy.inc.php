<?php
/**
 * @file classes/security/authorization/internal/ContextPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Basic policy that ensures availability of a context in
 *  the request context and a valid user group. All context based policies
 *  extend this policy.
 */

import('lib.pkp.classes.security.authorization.PolicySet');

class ContextPolicy extends PolicySet {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function __construct($request) {
		parent::__construct();

		// Ensure we're in a context
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request, 'user.authorization.noContext'));
	}
}

?>
