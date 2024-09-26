<?php

/**
 * @file pages/reviewer/ReviewerHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerHandler
 *
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer functions.
 */

namespace APP\pages\reviewer;

use APP\facades\Repo;
use APP\submission\reviewer\form\ReviewerReviewStep3Form;
use APP\submission\Submission;
use PKP\core\PKPRequest;
use PKP\invitation\core\enums\InvitationAction;
use PKP\pages\reviewer\PKPReviewerHandler;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\submission\reviewer\form\ReviewerReviewForm;
use PKP\user\User;

class ReviewerHandler extends PKPReviewerHandler
{
    /** @var Submission */
    public $submission;

    /** @var User */
    public $user;

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
     *
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $context = $request->getContext();
        if ($context->getData('reviewerAccessKeysEnabled')) {
            $accessKeyCode = $request->getUserVar('key');
            if ($accessKeyCode) {
                $invitation = Repo::invitation()
                    ->getByKey($accessKeyCode);

                if (isset($invitation)) {
                    $invitationHandler = $invitation->getInvitationActionRedirectController();
                    $invitationHandler->preRedirectActions(InvitationAction::ACCEPT);
                    $invitationHandler->acceptHandle($request);
                }
            }
        }

        $this->addPolicy(new SubmissionAccessPolicy(
            $request,
            $args,
            $roleAssignments
        ));

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc PKPReviewerHandler::getReviewForm()
     */
    public function getReviewForm(
        int $step,
        PKPRequest $request,
        Submission $reviewSubmission,
        ReviewAssignment $reviewAssignment
    ): ReviewerReviewForm {
        switch ($step) {
            case 3:
                return new ReviewerReviewStep3Form($request, $reviewSubmission, $reviewAssignment);
        }
        return parent::getReviewForm($step, $request, $reviewSubmission, $reviewAssignment);
    }
}
