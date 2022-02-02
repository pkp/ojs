<?php

/**
 * @file pages/reviewer/ReviewerHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer functions.
 */

use APP\facades\Repo;
use APP\submission\reviewer\form\ReviewerReviewStep3Form;
use PKP\security\AccessKeyManager;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;
use PKP\security\Validation;

// FIXME: Add namespacing
import('lib.pkp.pages.reviewer.PKPReviewerHandler');

class ReviewerHandler extends PKPReviewerHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            Role::ROLE_ID_REVIEWER,
            [
                'submission', 'step', 'saveStep',
                'showDeclineReview', 'saveDeclineReview', 'downloadFile'
            ]
        );
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $context = $request->getContext();
        if ($context->getData('reviewerAccessKeysEnabled')) {
            $this->_validateAccessKey($request);
        }

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
     * @param PKPRequest $request
     */
    protected function _validateAccessKey($request)
    {
        $accessKeyCode = $request->getUserVar('key');
        $reviewId = $request->getUserVar('reviewId');
        if (!($accessKeyCode && $reviewId)) {
            return;
        }

        // Check if the user is already logged in
        $sessionManager = SessionManager::getManager();
        $session = $sessionManager->getUserSession();
        if ($session->getUserId()) {
            return;
        }

        $reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO'); /** @var ReviewerSubmissionDAO $reviewerSubmissionDao */
        $reviewerSubmission = $reviewerSubmissionDao->getReviewerSubmission($reviewId);
        if (!$reviewerSubmission) {
            return;
        } // e.g. deleted review assignment

        // Validate the access key
        $context = $request->getContext();
        $accessKeyManager = new AccessKeyManager();
        $accessKeyHash = AccessKeyManager::generateKeyHash($accessKeyCode);
        $accessKey = $accessKeyManager->validateKey(
            $context->getId(),
            $reviewerSubmission->getReviewerId(),
            $accessKeyHash
        );
        if (!$accessKey) {
            return;
        }

        // Get the reviewer user object
        $user = Repo::user()->get($accessKey->getUserId());
        if (!$user) {
            return;
        }

        // Register the user object in the session
        $reason = null;
        if (Validation::registerUserSession($user, $reason)) {
            $this->submission = $reviewerSubmission;
            $this->user = $user;
        }
    }

    /**
     * @copydoc PKPReviewerHandler::getReviewForm()
     */
    public function getReviewForm(
        int $step,
        PKPRequest $request,
        ReviewerSubmission $reviewerSubmission,
        ReviewAssignment $reviewAssignment
    ): ReviewerReviewForm {
        switch ($step) {
        case 3:
            return new ReviewerReviewStep3Form($request, $reviewerSubmission, $reviewAssignment);
        }
        return parent::getReviewForm($step, $request, $reviewerSubmission, $reviewAssignment);
    }
}
