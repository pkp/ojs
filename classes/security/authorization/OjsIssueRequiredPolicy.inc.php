<?php
/**
 * @file classes/security/authorization/OjsIssueRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OjsIssueRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid issue.
 */

import('lib.pkp.classes.security.authorization.DataObjectRequiredPolicy');

class OjsIssueRequiredPolicy extends DataObjectRequiredPolicy {
	/** @var Journal */
	var $journal;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $operations array
	 */
	function __construct($request, &$args, $operations = null) {
		parent::__construct($request, $args, 'issueId', 'user.authorization.invalidIssue', $operations);
		$this->journal = $request->getJournal();
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see DataObjectRequiredPolicy::dataObjectEffect()
	 */
	function dataObjectEffect() {
		$issueId = $this->getDataObjectId();
		if (!$issueId) return AUTHORIZATION_DENY;

		// Make sure the issue belongs to the journal.
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issue = $issueDao->getByBestId($issueId,  $this->journal->getId());

		if (!is_a($issue, 'Issue')) return AUTHORIZATION_DENY;

		// The issue must be published, or we must have pre-publication
		// access to it.
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		if (!$issue->getPublished() && count(array_intersect(
			$userRoles,
			array(
				ROLE_ID_SITE_ADMIN,
				ROLE_ID_MANAGER,
				ROLE_ID_SUB_EDITOR,
				ROLE_ID_ASSISTANT,
			)
		))==0) {
			return AUTHORIZATION_DENY;
		}

		// Save the issue to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_ISSUE, $issue);
		return AUTHORIZATION_PERMIT;
	}

	/**
	 * @copydoc DataObjectRequiredPolicy::getDataObjectId()
	 * Considers a not numeric public URL identifier
	 */
	function getDataObjectId($lookOnlyByParameterName = false) {
		if ($lookOnlyByParameterName) throw new Exception('lookOnlyByParameterName not supported for issues.');
		// Identify the data object id.
		$router = $this->_request->getRouter();
		switch(true) {
			case is_a($router, 'PKPPageRouter'):
				if ( ctype_digit((string) $this->_request->getUserVar($this->_parameterName)) ) {
					// We may expect a object id in the user vars
					return (int) $this->_request->getUserVar($this->_parameterName);
				} else if (isset($this->_args[0])) {
					// Or the object id can be expected as the first path in the argument list
					return $this->_args[0];
				}
				break;

			default:
				return parent::getDataObjectId();
		}

		return false;
	}
}


