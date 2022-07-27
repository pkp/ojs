<?php

/**
 * @file classes/submission/reviewer/ReviewerSubmission.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSubmission
 * @ingroup submission
 *
 * @see ReviewerSubmissionDAO
 *
 * @brief ReviewerSubmission class.
 */

namespace APP\submission\reviewer;

use APP\submission\Submission;

class ReviewerSubmission extends Submission
{
    /** @var array SubmissionFile reviewer file revisions of this submission */
    public $reviewerFileRevisions;

    /** @var array SubmissionComments peer review comments of this submission */
    public $peerReviewComments;

    /**
     * Get/Set Methods.
     */

    /**
     * Get the competing interests for this submission.
     *
     * @return string
     */
    public function getCompetingInterests()
    {
        return $this->getData('competingInterests');
    }

    /**
     * Set the competing interests statement.
     *
     * @param string $competingInterests
     */
    public function setCompetingInterests($competingInterests)
    {
        $this->setData('competingInterests', $competingInterests);
    }

    /**
     * Get ID of review assignment.
     *
     * @return int
     */
    public function getReviewId()
    {
        return $this->getData('reviewId');
    }

    /**
     * Set ID of review assignment
     *
     * @param int $reviewId
     */
    public function setReviewId($reviewId)
    {
        $this->setData('reviewId', $reviewId);
    }

    /**
     * Get ID of reviewer.
     *
     * @return int
     */
    public function getReviewerId()
    {
        return $this->getData('reviewerId');
    }

    /**
     * Set ID of reviewer.
     *
     * @param int $reviewerId
     */
    public function setReviewerId($reviewerId)
    {
        $this->setData('reviewerId', $reviewerId);
    }

    /**
     * Get full name of reviewer.
     *
     * @return string
     */
    public function getReviewerFullName()
    {
        return $this->getData('reviewerFullName');
    }

    /**
     * Set full name of reviewer.
     *
     * @param string $reviewerFullName
     */
    public function setReviewerFullName($reviewerFullName)
    {
        $this->setData('reviewerFullName', $reviewerFullName);
    }

    /**
     * Get reviewer recommendation.
     *
     * @return string
     */
    public function getRecommendation()
    {
        return $this->getData('recommendation');
    }

    /**
     * Set reviewer recommendation.
     *
     * @param string $recommendation
     */
    public function setRecommendation($recommendation)
    {
        $this->setData('recommendation', $recommendation);
    }

    /**
     * Get the reviewer's assigned date.
     *
     * @return string
     */
    public function getDateAssigned()
    {
        return $this->getData('dateAssigned');
    }

    /**
     * Set the reviewer's assigned date.
     *
     * @param string $dateAssigned
     */
    public function setDateAssigned($dateAssigned)
    {
        $this->setData('dateAssigned', $dateAssigned);
    }

    /**
     * Get the reviewer's notified date.
     *
     * @return string
     */
    public function getDateNotified()
    {
        return $this->getData('dateNotified');
    }

    /**
     * Set the reviewer's notified date.
     *
     * @param string $dateNotified
     */
    public function setDateNotified($dateNotified)
    {
        $this->setData('dateNotified', $dateNotified);
    }

    /**
     * Get the reviewer's confirmed date.
     *
     * @return string
     */
    public function getDateConfirmed()
    {
        return $this->getData('dateConfirmed');
    }

    /**
     * Set the reviewer's confirmed date.
     *
     * @param string $dateConfirmed
     */
    public function setDateConfirmed($dateConfirmed)
    {
        $this->setData('dateConfirmed', $dateConfirmed);
    }

    /**
     * Get the reviewer's completed date.
     *
     * @return string
     */
    public function getDateCompleted()
    {
        return $this->getData('dateCompleted');
    }

    /**
     * Set the reviewer's completed date.
     *
     * @param string $dateCompleted
     */
    public function setDateCompleted($dateCompleted)
    {
        $this->setData('dateCompleted', $dateCompleted);
    }

    /**
     * Get the reviewer's acknowledged date.
     *
     * @return string
     */
    public function getDateAcknowledged()
    {
        return $this->getData('dateAcknowledged');
    }

    /**
     * Set the reviewer's acknowledged date.
     *
     * @param string $dateAcknowledged
     */
    public function setDateAcknowledged($dateAcknowledged)
    {
        $this->setData('dateAcknowledged', $dateAcknowledged);
    }

    /**
     * Get the reviewer's due date.
     *
     * @return string
     */
    public function getDateDue()
    {
        return $this->getData('dateDue');
    }

    /**
     * Set the reviewer's due date.
     *
     * @param string $dateDue
     */
    public function setDateDue($dateDue)
    {
        $this->setData('dateDue', $dateDue);
    }

    /**
     * Get the reviewer's response due date.
     *
     * @return string
     */
    public function getDateResponseDue()
    {
        return $this->getData('dateResponseDue');
    }

    /**
     * Set the reviewer's response due date.
     *
     * @param string $dateResponseDue
     */
    public function setDateResponseDue($dateResponseDue)
    {
        $this->setData('dateResponseDue', $dateResponseDue);
    }

    /**
     * Get the declined value.
     *
     * @return bool
     */
    public function getDeclined()
    {
        return $this->getData('declined');
    }

    /**
     * Set the reviewer's declined value.
     *
     * @param bool $declined
     */
    public function setDeclined($declined)
    {
        $this->setData('declined', $declined);
    }

    /**
     * Get the cancelled value.
     *
     * @return bool
     */
    public function getCancelled()
    {
        return $this->getData('cancelled');
    }

    /**
     * Set the reviewer's cancelled value.
     *
     * @param bool $cancelled
     */
    public function setCancelled($cancelled)
    {
        $this->setData('cancelled', $cancelled);
    }

    /**
     * Get quality.
     *
     * @return int|null
     */
    public function getQuality()
    {
        return $this->getData('quality');
    }

    /**
     * Set quality.
     *
     * @param int|null $quality
     */
    public function setQuality($quality)
    {
        $this->setData('quality', $quality);
    }

    /**
     * Get stageId.
     *
     * @return int
     */
    public function getStageId()
    {
        return $this->getData('stageId');
    }

    /**
     * Set stageId.
     *
     * @param int $stageId
     */
    public function setStageId($stageId)
    {
        $this->setData('stageId', $stageId);
    }

    /**
     * Get the method of the review (open, anonymous, or double-anonymous).
     *
     * @return int
     */
    public function getReviewMethod()
    {
        return $this->getData('reviewMethod');
    }

    /**
     * Set the type of review.
     *
     * @param int $method
     */
    public function setReviewMethod($method)
    {
        $this->setData('reviewMethod', $method);
    }

    /**
     * Get round.
     *
     * @return int
     */
    public function getRound()
    {
        return $this->getData('round');
    }

    /**
     * Set round.
     *
     * @param int $round
     */
    public function setRound($round)
    {
        $this->setData('round', $round);
    }

    /**
     * Get step.
     *
     * @return int
     */
    public function getStep()
    {
        return $this->getData('step');
    }

    /**
     * Set status.
     */
    public function setStep($step)
    {
        $this->setData('step', $step);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\submission\reviewer\ReviewerSubmission', '\ReviewerSubmission');
}
