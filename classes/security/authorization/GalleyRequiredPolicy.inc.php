<?php
/**
 * @file classes/security/authorization/GalleyRequiredPolicy.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleyRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid galley.
 */

import('lib.pkp.classes.security.authorization.DataObjectRequiredPolicy');

class GalleyRequiredPolicy extends DataObjectRequiredPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $submissionParameterName string the request parameter we expect
	 *  the submission id in.
	 */
	function GalleyRequiredPolicy($request, &$args, $parameterName = 'articleGalleyId', $operations = null) {
		parent::DataObjectRequiredPolicy($request, $args, $parameterName, 'user.authorization.invalidGalley', $operations);
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see DataObjectRequiredPolicy::dataObjectEffect()
	 */
	function dataObjectEffect() {
		$galleyId = (int)$this->getDataObjectId();
		if (!$galleyId) return AUTHORIZATION_DENY;

		// Need a valid submission in request.
		$submission =& $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		if (!is_a($submission, 'Submission')) return AUTHORIZATION_DENY;

		// Make sure the galley belongs to the submission.
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = $articleGalleyDao->getById($galleyId, $submission->getId());
		if (!is_a($galley, 'ArticleGalley')) return AUTHORIZATION_DENY;

		// Save the Galley to the authorization context.
		$this->addAuthorizedContextObject(ASSOC_TYPE_GALLEY, $galley);
		return AUTHORIZATION_PERMIT;
	}
}

?>
