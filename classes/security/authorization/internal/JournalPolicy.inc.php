<?php
/**
 * @file classes/security/authorization/internal/JournalPolicy.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures availability of an OJS journal in
 *  the request context
 */

import('lib.pkp.classes.security.authorization.PolicySet');

class JournalPolicy extends PolicySet {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function JournalPolicy(&$request) {
		parent::PolicySet();

		// Ensure that we have a journal in the context.
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request, 'user.authorization.noJournal'));
	}
}

?>
