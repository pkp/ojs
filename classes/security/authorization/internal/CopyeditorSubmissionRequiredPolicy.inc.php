<?php
/**
 * @file classes/security/authorization/internal/CopyeditorSubmissionRequiredPolicy.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CopyeditorSubmissionRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid
 *  copyeditor submission.
 */

import('lib.pkp.classes.security.authorization.DataObjectRequiredPolicy');

class CopyeditorSubmissionRequiredPolicy extends DataObjectRequiredPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function CopyeditorSubmissionRequiredPolicy(&$request, &$args, $submissionParameterName = 'articleId') {
		parent::DataObjectRequiredPolicy($request, $args, $submissionParameterName, 'user.authorization.invalidCopyditorSubmission');
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Get the request.
		$request =& $this->getRequest();

		// Get the submission id.
		$submissionId = $this->getDataObjectId();
		if ($submissionId === false) return AUTHORIZATION_DENY;

		// Get the user
		$user =& $request->getUser();
		if (!is_a($user, 'PKPUser')) return AUTHORIZATION_DENY;

		// Validate the article id.
		$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$copyeditorSubmission =& $copyeditorSubmissionDao->getCopyeditorSubmission($submissionId, $user->getId());
		if (!is_a($copyeditorSubmission, 'CopyeditorSubmission')) return AUTHORIZATION_DENY;

		// Check whether the article is actually part of the journal
		// in the context.
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		if (!is_a($journal, 'Journal')) return AUTHORIZATION_DENY;
		if ($copyeditorSubmission->getJournalId() != $journal->getId()) return AUTHORIZATION_DENY;

		// Save the copyeditor submission to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_ARTICLE, $copyeditorSubmission);
		return AUTHORIZATION_PERMIT;
	}
}

?>
