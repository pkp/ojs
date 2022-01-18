<?php

/**
 * @file classes/submission/reviewer/ReviewerSubmissionDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSubmissionDAO
 * @ingroup submission
 *
 * @see ReviewerSubmission
 *
 * @brief Operations for retrieving and modifying ReviewerSubmission objects.
 */

namespace APP\submission\reviewer;

use APP\facades\Repo;
use PKP\facades\Locale;
use PKP\db\DAO;
use PKP\db\DAORegistry;

use PKP\plugins\HookRegistry;

class ReviewerSubmissionDAO extends DAO
{
    public $reviewAssignmentDao;
    public $submissionCommentDao;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $this->submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');
    }

    /**
     * Retrieve a reviewer submission by submission ID.
     *
     * @return ReviewerSubmission
     */
    public function getReviewerSubmission($reviewId)
    {
        $primaryLocale = Locale::getPrimaryLocale();
        $locale = Locale::getLocale();
        $result = $this->retrieve(
            'SELECT	a.*,
				r.*,
				p.date_published,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	submissions a
				LEFT JOIN publications p ON (a.submission_id = p.submission_id AND p.publication_id = a.current_publication_id)
				LEFT JOIN review_assignments r ON (a.submission_id = r.submission_id)
				LEFT JOIN sections s ON (s.section_id = p.section_id)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE r.review_id = ?',
            [
                'title', $primaryLocale, // Section title
                'title', $locale, // Section title
                'abbrev', $primaryLocale, // Section abbreviation
                'abbrev', $locale, // Section abbreviation
                (int) $reviewId
            ]
        );
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Construct a new data object corresponding to this DAO.
     *
     * @return ReviewerSubmission
     */
    public function newDataObject()
    {
        return new ReviewerSubmission();
    }

    /**
     * Internal function to return a ReviewerSubmission object from a row.
     *
     * @param array $row
     *
     * @return ReviewerSubmission
     */
    public function _fromRow($row)
    {
        // Get the ReviewerSubmission object, populated with submission data
        $submission = Repo::submission()->dao->fromRow((object) $row);
        $reviewerSubmission = $this->newDataObject();
        $reviewerSubmission->setAllData($submission->getAllData());
        $reviewer = Repo::user()->get($row['reviewer_id'], true);

        // Review Assignment
        $reviewerSubmission->setReviewId($row['review_id']);
        $reviewerSubmission->setReviewerId($row['reviewer_id']);
        $reviewerSubmission->setReviewerFullName($reviewer->getFullName());
        $reviewerSubmission->setCompetingInterests($row['competing_interests']);
        $reviewerSubmission->setRecommendation($row['recommendation']);
        $reviewerSubmission->setDateAssigned($this->datetimeFromDB($row['date_assigned']));
        $reviewerSubmission->setDateNotified($this->datetimeFromDB($row['date_notified']));
        $reviewerSubmission->setDateConfirmed($this->datetimeFromDB($row['date_confirmed']));
        $reviewerSubmission->setDateCompleted($this->datetimeFromDB($row['date_completed']));
        $reviewerSubmission->setDateAcknowledged($this->datetimeFromDB($row['date_acknowledged']));
        $reviewerSubmission->setDateDue($this->datetimeFromDB($row['date_due']));
        $reviewerSubmission->setDateResponseDue($this->datetimeFromDB($row['date_response_due']));
        $reviewerSubmission->setDeclined($row['declined']);
        $reviewerSubmission->setCancelled($row['cancelled']);
        $reviewerSubmission->setQuality($row['quality']);
        $reviewerSubmission->setRound($row['round']);
        $reviewerSubmission->setStep($row['step']);
        $reviewerSubmission->setStageId($row['stage_id']);
        $reviewerSubmission->setReviewMethod($row['review_method']);

        HookRegistry::call('ReviewerSubmissionDAO::_fromRow', [&$reviewerSubmission, &$row]);
        return $reviewerSubmission;
    }

    /**
     * Update an existing review submission.
     */
    public function updateReviewerSubmission($reviewerSubmission)
    {
        $this->update(
            sprintf(
                'UPDATE review_assignments
				SET	submission_id = ?,
					reviewer_id = ?,
					stage_id = ?,
					review_method = ?,
					round = ?,
					step = ?,
					competing_interests = ?,
					recommendation = ?,
					declined = ?,
					cancelled = ?,
					date_assigned = %s,
					date_notified = %s,
					date_confirmed = %s,
					date_completed = %s,
					date_acknowledged = %s,
					date_due = %s,
					date_response_due = %s,
					quality = ?
				WHERE	review_id = ?',
                $this->datetimeToDB($reviewerSubmission->getDateAssigned()),
                $this->datetimeToDB($reviewerSubmission->getDateNotified()),
                $this->datetimeToDB($reviewerSubmission->getDateConfirmed()),
                $this->datetimeToDB($reviewerSubmission->getDateCompleted()),
                $this->datetimeToDB($reviewerSubmission->getDateAcknowledged()),
                $this->datetimeToDB($reviewerSubmission->getDateDue()),
                $this->datetimeToDB($reviewerSubmission->getDateResponseDue())
            ),
            [
                (int) $reviewerSubmission->getId(),
                (int) $reviewerSubmission->getReviewerId(),
                (int) $reviewerSubmission->getStageId(),
                (int) $reviewerSubmission->getReviewMethod(),
                (int) $reviewerSubmission->getRound(),
                (int) $reviewerSubmission->getStep(),
                $reviewerSubmission->getCompetingInterests(),
                (int) $reviewerSubmission->getRecommendation(),
                (int) $reviewerSubmission->getDeclined(),
                (int) $reviewerSubmission->getCancelled(),
                $reviewerSubmission->getQuality(),
                (int) $reviewerSubmission->getReviewId()
            ]
        );
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\submission\reviewer\ReviewerSubmissionDAO', '\ReviewerSubmissionDAO');
}
