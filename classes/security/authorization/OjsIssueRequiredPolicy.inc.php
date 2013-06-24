<?php
/**
 * @file classes/security/authorization/OjsIssueRequiredPolicy.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OjsIssueRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid issue.
 */

import('lib.pkp.classes.security.authorization.DataObjectRequiredPolicy');

class OjsIssueRequiredPolicy extends DataObjectRequiredPolicy {
	/** @var $journal Journal */
	var $journal;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $operations array
	 */
	function OjsIssueRequiredPolicy($request, &$args, $operations = null) {
		parent::DataObjectRequiredPolicy($request, $args, 'issueId', 'user.authorization.invalidIssue', $operations);
		$this->journal = $request->getJournal();
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

		// Make sure the issue belongs to the journal.
		$issueDao = DAORegistry::getDAO('IssueDAO');
		if ($this->journal->getSetting('enablePublicIssueId')) {
			$issue = $issueDao->getByBestId($issueId,  $this->journal->getId());
		} else {
			$issue = $issueDao->getById((int) $issueId, null, true);
		}

		if (!is_a($issue, 'Issue')) return AUTHORIZATION_DENY;

		// Save the issue to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_ISSUE, $issue);
		return AUTHORIZATION_PERMIT;
	}
}

?>
