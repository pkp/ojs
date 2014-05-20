<?php
/**
 * @file classes/security/authorization/internal/SectionAssignmentPolicy.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionAssignmentPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Class to control access to sections.
 *
 * NB: This policy expects a previously authorized article in the
 * authorization context.
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class SectionAssignmentPolicy extends AuthorizationPolicy {
	/** @var PKPRequest */
	var $_request;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function SectionAssignmentPolicy($request) {
		parent::AuthorizationPolicy('user.authorization.seriesAssignment');
		$this->_request = $request;
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @copydoc AuthorizationPolicy::effect()
	 */
	function effect() {
		// Get the user
		$user = $this->_request->getUser();
		if (!is_a($user, 'PKPUser')) return AUTHORIZATION_DENY;

		// Get the journal
		$router = $this->_request->getRouter();
		$context = $router->getContext($this->_request);
		if (!is_a($context, 'Journal')) return AUTHORIZATION_DENY;

		// Get the article
		$article = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		if (!is_a($article, 'Article')) return AUTHORIZATION_DENY;

		import('classes.security.authorization.internal.SectionAssignmentRule');
		if (SectionAssignmentRule::effect($context->getId(), $article->getSectionId(), $user->getId())) {
			return AUTHORIZATION_PERMIT;
		} else {
			return AUTHORIZATION_DENY;
		}
	}
}

?>
