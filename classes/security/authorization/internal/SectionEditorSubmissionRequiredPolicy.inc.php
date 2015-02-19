<?php
/**
 * @file classes/security/authorization/internal/SectionEditorSubmissionRequiredPolicy.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionEditorSubmissionRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid section
 *  editor submission.
 */

import('lib.pkp.classes.security.authorization.DataObjectRequiredPolicy');

class SectionEditorSubmissionRequiredPolicy extends DataObjectRequiredPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function SectionEditorSubmissionRequiredPolicy(&$request, &$args, $submissionParameterName = 'articleId') {
		parent::DataObjectRequiredPolicy($request, $args, $submissionParameterName, 'user.authorization.invalidSectionEditorSubmission');
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Get the submission id.
		$submissionId = $this->getDataObjectId();
		if ($submissionId === false) return AUTHORIZATION_DENY;

		// Validate the section editor submission id.
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($submissionId);
		if (!is_a($sectionEditorSubmission, 'SectionEditorSubmission')) return AUTHORIZATION_DENY;

		// Check whether the article is actually part of the journal
		// in the context.
		$request =& $this->getRequest();
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		if (!is_a($journal, 'Journal')) return AUTHORIZATION_DENY;
		if ($sectionEditorSubmission->getJournalId() != $journal->getId()) return AUTHORIZATION_DENY;

		// Save the section editor submission to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_ARTICLE, $sectionEditorSubmission);
		return AUTHORIZATION_PERMIT;
	}
}

?>
