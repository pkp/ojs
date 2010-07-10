<?php
/**
 * @file classes/security/authorization/OjsJournalPolicy.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OjsJournalPolicy
 * @ingroup security_authorization
 *
 * @brief Policy that ensures availability of an OJS journal in
 *  the request context
 */

import('lib.pkp.classes.security.authorization.PolicySet');

class OjsJournalPolicy extends PolicySet {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function OjsJournalPolicy(&$request) {
		parent::PolicySet();

		// Ensure that we have a journal in the context
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request, 'No journal in context!'));
	}
}

?>
