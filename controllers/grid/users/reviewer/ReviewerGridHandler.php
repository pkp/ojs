<?php

/**
 * @file controllers/grid/users/reviewer/ReviewerGridHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerGridHandler
 *
 * @ingroup controllers_grid_users_reviewer
 *
 * @brief Handle reviewer grid requests.
 */

namespace APP\controllers\grid\users\reviewer;

use APP\core\Application;
use APP\facades\Repo;
use PKP\controllers\grid\users\reviewer\PKPReviewerGridHandler;
use PKP\core\Core;
use PKP\core\PKPApplication;
use PKP\security\Validation;

class ReviewerGridHandler extends PKPReviewerGridHandler
{
    /**
     * @copydoc PKPReviewerGridHandler::reviewRead()
     */
    public function reviewRead($args, $request)
    {
        // Retrieve review assignment.
        $reviewAssignment = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_REVIEW_ASSIGNMENT); /** @var \PKP\submission\reviewAssignment\ReviewAssignment $reviewAssignment */

        // Recommendation
        $newRecommendation = $request->getUserVar('recommendation');
        // If editor set or changed the recommendation
        if ($newRecommendation && $reviewAssignment->getRecommendation() != $newRecommendation) {
            $reviewAssignment->setRecommendation($newRecommendation);

            // Add log entry
            $submission = $this->getSubmission();
            $reviewer = Repo::user()->get($reviewAssignment->getReviewerId(), true);
            $user = $request->getUser();

            class_exists(\APP\log\event\SubmissionEventLogEntry::class); // Force definition of SUBMISSION_LOG_REVIEW_RECOMMENDATION_BY_PROXY
            $eventLog = Repo::eventLog()->newDataObject([
                'assocType' => PKPApplication::ASSOC_TYPE_SUBMISSION,
                'assocId' => $submission->getId(),
                'eventType' => \SUBMISSION_LOG_REVIEW_RECOMMENDATION_BY_PROXY,
                'userId' => Validation::loggedInAs() ?? $user->getId(),
                'message' => 'log.review.reviewRecommendationSetByProxy',
                'isTranslated' => false,
                'dateLogged' => Core::getCurrentDate(),
                'round' => $reviewAssignment->getRound(),
                'submissionId' => $submission->getId(),
                'editorName' => $user->getFullName(),
                'reviewAssignmentId' => $reviewAssignment->getId(),
                'reviewerName' => $reviewer->getFullName(),
            ]);
            Repo::eventLog()->add($eventLog);
        }
        return parent::reviewRead($args, $request);
    }
}
