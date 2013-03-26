<?php
/**
 * @file classes/security/authorization/internal/IssueRequiredPolicy.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid issue.
 */

import('lib.pkp.classes.security.authorization.DataObjectRequiredPolicy');

class IssueRequiredPolicy extends DataObjectRequiredPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $submissionParameterName string the request parameter we expect
	 *  the submission id in.
	 */
	function IssueRequiredPolicy(&$request, &$args, $parameterName = 'issueId', $operations = null) {
		parent::DataObjectRequiredPolicy($request, $args, $parameterName, 'user.authorization.invalidIssue', $operations);
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see DataObjectRequiredPolicy::dataObjectEffect()
	 */
	function dataObjectEffect() {
		$issueId = (int)$this->getDataObjectId();
		if (!$issueId) return AUTHORIZATION_DENY;

		// Need a valid journal in request.
		$journal =& $this->getAuthorizedContextObject(ASSOC_TYPE_JOURNAL);
		if (!is_a($journal, 'Journal')) return AUTHORIZATION_DENY;

		// Make sure the issue belongs to the journal.
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getById($issueId, $journal->getId());
		if (!is_a($issue, 'Issue')) return AUTHORIZATION_DENY;

		// Save the publication format to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_ISSUE, $issue);
		return AUTHORIZATION_PERMIT;
	}
}

?>
