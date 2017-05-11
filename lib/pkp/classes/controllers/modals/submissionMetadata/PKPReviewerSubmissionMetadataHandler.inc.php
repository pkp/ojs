<?php

/**
 * @file classes/controllers/modals/submissionMetadata/PKPReviewerSubmissionMetadataHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPReviewerSubmissionMetadataHandler
 * @ingroup controllers_modals_submissionMetadata
 *
 * @brief Display submission metadata to reviewers.
 */

import('lib.pkp.classes.controllers.modals.submissionMetadata.SubmissionMetadataHandler');

// import JSON class for use with all AJAX requests
import('lib.pkp.classes.core.JSONMessage');

class PKPReviewerSubmissionMetadataHandler extends SubmissionMetadataHandler {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(ROLE_ID_REVIEWER), array('fetch'));
	}

	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see classes/controllers/modals/submissionMetadata/SubmissionMetadataHandler::fetch()
	 */
	function fetch($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$reviewMethod = $reviewAssignment->getReviewMethod();

		if ($reviewMethod == SUBMISSION_REVIEW_METHOD_DOUBLEBLIND) {
			$anonymous = true;
		} else { /* SUBMISSION_REVIEW_METHOD_BLIND or _OPEN */
			$anonymous = false;
		}

		$params = array('readOnly' => true, 'anonymous' => $anonymous, 'hideSubmit' => true);

		return parent::fetch($args, $request, $params);
	}
}

?>
