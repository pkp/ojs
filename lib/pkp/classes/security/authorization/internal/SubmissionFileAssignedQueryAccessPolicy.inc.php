<?php
/**
 * @file classes/security/authorization/internal/SubmissionFileAssignedQueryAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileAssignedQueryAccessPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Submission file policy to check if the current user is a participant
 * 	in a query the file belongs to.
 *
 */

import('lib.pkp.classes.security.authorization.internal.SubmissionFileBaseAccessPolicy');

class SubmissionFileAssignedQueryAccessPolicy extends SubmissionFileBaseAccessPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function __construct($request, $fileIdAndRevision = null) {
		parent::__construct($request, $fileIdAndRevision);
	}


	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		$request = $this->getRequest();

		// Get the user
		$user = $request->getUser();
		if (!is_a($user, 'PKPUser')) return AUTHORIZATION_DENY;

		// Get the submission file
		$submissionFile = $this->getSubmissionFile($request);
		if (!is_a($submissionFile, 'SubmissionFile')) return AUTHORIZATION_DENY;

		// Check if it's associated with a note.
		if ($submissionFile->getAssocType() != ASSOC_TYPE_NOTE) return AUTHORIZATION_DENY;

		$noteDao = DAORegistry::getDAO('NoteDAO');
		$note = $noteDao->getById($submissionFile->getAssocId());
		if (!is_a($note, 'Note')) return AUTHORIZATION_DENY;

		if ($note->getAssocType() != ASSOC_TYPE_QUERY) return AUTHORIZATION_DENY;
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$query = $queryDao->getById($note->getAssocId());
		if (!$query) return AUTHORIZATION_DENY;

		if ($queryDao->getParticipantIds($note->getAssocId(), $user->getId())) {
			return AUTHORIZATION_PERMIT;
		}

		return AUTHORIZATION_DENY;
	}
}

?>
