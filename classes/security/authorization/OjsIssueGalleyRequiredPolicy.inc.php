<?php
/**
 * @file classes/security/authorization/OjsIssueGalleyRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OjsIssueGalleyRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid issue galley.
 */

import('lib.pkp.classes.security.authorization.DataObjectRequiredPolicy');

class OjsIssueGalleyRequiredPolicy extends DataObjectRequiredPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $operations array
	 */
	function __construct($request, &$args, $operations = null) {
		parent::__construct($request, $args, 'issueGalleyId', 'user.authorization.invalidIssueGalley', $operations);
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see DataObjectRequiredPolicy::dataObjectEffect()
	 */
	function dataObjectEffect() {
		$issueGalleyId = (int)$this->getDataObjectId();
		if (!$issueGalleyId) return AUTHORIZATION_DENY;

		// Make sure the issue galley belongs to the journal.
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
		$issueGalley = $issueGalleyDao->getById($issueGalleyId, $issue->getId());
		if (!is_a($issueGalley, 'IssueGalley')) return AUTHORIZATION_DENY;

		// Save the publication format to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_ISSUE_GALLEY, $issueGalley);
		return AUTHORIZATION_PERMIT;
	}
}


