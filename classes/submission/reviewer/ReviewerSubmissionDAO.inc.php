<?php

/**
 * @file classes/submission/reviewer/ReviewerSubmissionDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSubmissionDAO
 * @ingroup submission
 * @see ReviewerSubmission
 *
 * @brief Operations for retrieving and modifying ReviewerSubmission objects.
 */

import('classes.article.ArticleDAO');
import('classes.submission.reviewer.ReviewerSubmission');

class ReviewerSubmissionDAO extends ArticleDAO {
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $submissionFileDao;
	var $submissionCommentDao;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$this->submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');
	}

	/**
	 * Retrieve a reviewer submission by submission ID.
	 * @param $submissionId int
	 * @param $reviewerId int
	 * @return ReviewerSubmission
	 */
	function getReviewerSubmission($reviewId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$result = $this->retrieve(
			'SELECT	a.*,
				r.*,
				ps.date_published,
				u.first_name, u.last_name,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	submissions a
				LEFT JOIN published_submissions ps ON (a.submission_id = ps.submission_id)
				LEFT JOIN review_assignments r ON (a.submission_id = r.submission_id)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	r.review_id = ?',
			array(
				'title', $primaryLocale, // Section title
				'title', $locale, // Section title
				'abbrev', $primaryLocale, // Section abbreviation
				'abbrev', $locale, // Section abbreviation
				(int) $reviewId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ReviewerSubmission
	 */
	function newDataObject() {
		return new ReviewerSubmission();
	}

	/**
	 * Internal function to return a ReviewerSubmission object from a row.
	 * @param $row array
	 * @return ReviewerSubmission
	 */
	function _fromRow($row) {
		// Get the ReviewerSubmission object, populated with submission data
		$reviewerSubmission = parent::_fromRow($row);

		// Comments
		$reviewerSubmission->setMostRecentPeerReviewComment($this->submissionCommentDao->getMostRecentSubmissionComment($row['submission_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		// Editor Decisions
		$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
		$decisions = $editDecisionDao->getEditorDecisions($row['submission_id']);
		$reviewerSubmission->setDecisions($decisions);

		// Review Assignment
		$reviewerSubmission->setReviewId($row['review_id']);
		$reviewerSubmission->setReviewerId($row['reviewer_id']);
		$reviewerSubmission->setReviewerFullName($row['first_name'].' '.$row['last_name']);
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
		$reviewerSubmission->setQuality($row['quality']);
		$reviewerSubmission->setRound($row['round']);
		$reviewerSubmission->setStep($row['step']);
		$reviewerSubmission->setStageId($row['stage_id']);
		$reviewerSubmission->setReviewMethod($row['review_method']);

		HookRegistry::call('ReviewerSubmissionDAO::_fromRow', array(&$reviewerSubmission, &$row));
		return $reviewerSubmission;
	}

	/**
	 * Update an existing review submission.
	 * @param $reviewSubmission ReviewSubmission
	 */
	function updateReviewerSubmission($reviewerSubmission) {
		$this->update(
			sprintf('UPDATE review_assignments
				SET	submission_id = ?,
					reviewer_id = ?,
					stage_id = ?,
					review_method = ?,
					round = ?,
					step = ?,
					competing_interests = ?,
					recommendation = ?,
					declined = ?,
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
				$this->datetimeToDB($reviewerSubmission->getDateResponseDue())),
			array(
				(int) $reviewerSubmission->getId(),
				(int) $reviewerSubmission->getReviewerId(),
				(int) $reviewerSubmission->getStageId(),
				(int) $reviewerSubmission->getReviewMethod(),
				(int) $reviewerSubmission->getRound(),
				(int) $reviewerSubmission->getStep(),
				$reviewerSubmission->getCompetingInterests(),
				(int) $reviewerSubmission->getRecommendation(),
				(int) $reviewerSubmission->getDeclined(),
				(int) $reviewerSubmission->getQuality(),
				(int) $reviewerSubmission->getReviewId()
			)
		);
	}

	/**
	 * Get all submissions for a reviewer of a journal.
	 * @param $reviewerId int
	 * @param $journalId int
	 * @param $rangeInfo object
	 * @return array ReviewerSubmissions
	 */
	function getReviewerSubmissionsByReviewerId($reviewerId, $journalId = null, $active = true, $skipDeclined = true, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$sql = 'SELECT	a.*,
				r.*,
				ps.date_published,
				u.first_name, u.last_name,
				atl.setting_value AS submission_title,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	submissions a
				LEFT JOIN published_submissions ps ON (a.submission_id = ps.submission_id)
				LEFT JOIN review_assignments r ON (a.submission_id = r.submission_id)
				LEFT JOIN submission_settings atl ON (atl.submission_id = a.submission_id AND atl.setting_name = ? AND atl.locale = ?)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE r.reviewer_id = ? ' . ($journalId?	' AND a.context_id = ? ':'') .
				'AND r.date_notified IS NOT NULL';

		if ($active) {
			$sql .=  ' AND r.date_completed IS NULL AND r.declined <> 1';
		} else {
			$sql .= ' AND (r.date_completed IS NOT NULL OR r.declined = 1)';
		}

		if ($skipDeclined) {
			$sql .= ' AND a.status <> ' . STATUS_DECLINED;
		}

		if ($sortBy) {
			$sql .=  " ORDER BY $sortBy " . $this->getDirectionMapping($sortDirection);
		}

		$params = array(
			'title', $locale, // Submission title
			'title', $primaryLocale, // Section title
			'title', $locale, // Section title
			'abbrev', $primaryLocale, // Section abbreviation
			'abbrev', $locale, // Section abbreviation
			(int) $reviewerId
		);
		if ($journalId) $params[] = (int) $journalId;

		$result = $this->retrieveRange($sql, $params, $rangeInfo);
		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Get count of active and complete assignments
	 * @param reviewerId int
	 * @param journalId int
	 * @return array(int active, int complete)
	 */
	function getSubmissionsCount($reviewerId, $journalId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$result = $this->retrieve(
			'SELECT	r.date_completed, r.declined
			FROM	submissions a
				LEFT JOIN review_assignments r ON (a.submission_id = r.submission_id)
				LEFT JOIN section s ON (s.section_id = a.section_id)
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.stage_id = r2.stage_id AND r.round = r2.round)
			WHERE	a.context_id = ? AND
				r.reviewer_id = ? AND
				r.date_notified IS NOT NULL',
			array((int) $journalId, (int) $reviewerId)
		);

		while (!$result->EOF) {
			if ($result->fields['date_completed'] == null && $result->fields['declined'] != 1) {
				$submissionsCount[0] += 1; // Active
			} else {
				$submissionsCount[1] += 1; // Complete
			}
			$result->MoveNext();
		}

		$result->Close();
		return $submissionsCount;
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	function getSortMapping($heading) {
		switch ($heading) {
			case 'id': return 'a.submission_id';
			case 'assignDate': return 'r.date_assigned';
			case 'dueDate': return 'r.date_due';
			case 'section': return 'section_abbrev';
			case 'title': return 'submission_title';
			case 'round': return 'r.round';
			case 'review': return 'r.recommendation';
			case 'decision': return 'editor_decision';
			default: return null;
		}
	}
}

?>
