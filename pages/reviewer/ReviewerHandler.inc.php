<?php

/**
 * @file pages/reviewer/ReviewerHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer functions.
 */

import('lib.pkp.pages.reviewer.PKPReviewerHandler');

class ReviewerHandler extends PKPReviewerHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			ROLE_ID_REVIEWER, array(
				'submission', 'step', 'saveStep',
				'showDeclineReview', 'saveDeclineReview', 'downloadFile'
			)
		);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$context = $request->getContext();
		if ($context->getData('reviewerAccessKeysEnabled')) {
			$this->_validateAccessKey($request);
		}

		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$router = $request->getRouter();
		$this->addPolicy(new SubmissionAccessPolicy(
			$request,
			$args,
			$roleAssignments
		));


		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Tests if the request contains a valid access token. If this is the case
	 * the regular login process will be skipped
	 *
	 * @param $request PKPRequest
	 * @return void
	 */
	function _validateAccessKey($request) {
		$accessKeyCode = $request->getUserVar('key');
		$reviewId = $request->getUserVar('reviewId');
		if (!($accessKeyCode && $reviewId)) { return false; }

		// Check if the user is already logged in
		$sessionManager = SessionManager::getManager();
		$session = $sessionManager->getUserSession();
		if ($session->getUserId()) { return false; }

		import('lib.pkp.classes.security.AccessKeyManager');
		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission = $reviewerSubmissionDao->getReviewerSubmission($reviewId);

		// Validate the access key
		$context = $request->getContext();
		$accessKeyManager = new AccessKeyManager();
		$accessKeyHash = AccessKeyManager::generateKeyHash($accessKeyCode);
		$accessKey = $accessKeyManager->validateKey(
			$context->getId(),
			$reviewerSubmission->getReviewerId(),
			$accessKeyHash
		);
		if (!$accessKey) { return false; }

		// Get the reviewer user object
		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->getById($accessKey->getUserId());
		if (!$user) { return false; }

		// Register the user object in the session
		import('lib.pkp.classes.security.Validation');
		$reason = null;
		if (Validation::registerUserSession($user, $reason)) {
			$this->submission = $reviewerSubmission;
			$this->user = $user;
		}
	}
}


