<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignmentDAO.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewAssignmentDAO
 * @ingroup submission
 * @see ReviewAssignment
 *
 * @brief Class for DAO relating reviewers to articles.
 */

// $Id$


import('classes.submission.reviewAssignment.ReviewAssignment');
import('lib.pkp.classes.submission.reviewAssignment.PKPReviewAssignmentDAO');

class ReviewAssignmentDAO extends PKPReviewAssignmentDAO {
	var $articleFileDao;
	var $suppFileDao;
	var $articleCommentsDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::PKPReviewAssignmentDAO();
		$this->articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
	}

	/**
	 * Retrieve a review assignment by reviewer and article.
	 * @param $articleId int
	 * @param $reviewerId int
	 * @param $round int
	 * @return ReviewAssignment
	 */
	function &getReviewAssignment($articleId, $reviewerId, $round) {
		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN articles a ON (r.submission_id = a.article_id)
			WHERE	a.article_id = ? AND
				r.reviewer_id = ? AND
				r.cancelled <> 1 AND
				r.round = ?',
			array((int) $articleId, (int) $reviewerId, (int) $round)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a review assignment by review assignment id.
	 * @param $reviewId int
	 * @return ReviewAssignment
	 */
	function &getReviewAssignmentById($reviewId) {
		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN articles a ON (r.submission_id = a.article_id)
			WHERE	r.review_id = ?',
			(int) $reviewId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get all incomplete review assignments for all journals
	 * @param $articleId int
	 * @return array ReviewAssignments
	 */
	function &getIncompleteReviewAssignments() {
		$reviewAssignments = array();

		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN articles a ON (r.submission_id = a.article_id)
			WHERE	(r.cancelled IS NULL OR r.cancelled = 0) AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NULL AND
				r.declined <> 1
			ORDER BY r.submission_id'
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Get all review assignments for an article.
	 * @param $articleId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByArticleId($articleId, $round = null) {
		$reviewAssignments = array();

		$args = array((int) $articleId);
		if ($round) $args[] = (int) $round;

		$result =& $this->retrieve(
			'SELECT	r.*,
				r2.review_revision,
				a.review_file_id,
				u.first_name,
				u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN articles a ON (r.submission_id = a.article_id)
			WHERE	r.submission_id = ?
			' . ($round ? ' AND r.round = ? ':'') . '
			ORDER BY review_id',
			$args
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Get all review assignments for a reviewer.
	 * @param $userId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByUserId($userId) {
		$reviewAssignments = array();

		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN articles a ON (r.submission_id = a.article_id)
			WHERE	r.reviewer_id = ?
			ORDER BY round, review_id',
			(int) $userId
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Get all review assignments for a review form.
	 * @param $reviewFormId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByReviewFormId($reviewFormId) {
		$reviewAssignments = array();

		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission__id AND r.round = r2.round)
				LEFT JOIN articles a ON (r.submission_id = a.article_id)
			WHERE	r.review_form_id = ?
			ORDER BY round, review_id',
			(int) $reviewFormId
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Get a review file for an article for each round.
	 * @param $articleId int
	 * @return array ArticleFiles
	 */
	function &getReviewFilesByRound($articleId) {
		$returner = array();

		$result =& $this->retrieve(
			'SELECT	a.*, r.round as round
			FROM	review_rounds r,
				article_files a,
				articles art
			WHERE	art.article_id = r.submission_id AND
				r.submission_id = ? AND
				r.submission_id = a.article_id AND
				a.file_id = art.review_file_id AND
				a.revision = r.review_revision',
			(int) $articleId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['round']] =& $this->articleFileDao->_returnArticleFileFromRow($row);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get all author-viewable reviewer files for an article for each round.
	 * @param $articleId int
	 * @return array returned[round][reviewer_index] = array of ArticleFiles
	 */
	function &getAuthorViewableFilesByRound($articleId) {
		$files = array();

		$result =& $this->retrieve(
			'SELECT	f.*, r.reviewer_id, r.review_id
			FROM	review_assignments r,
				article_files f
			WHERE	reviewer_file_id = file_id AND
				viewable = 1 AND
				r.submission_id = ?
			ORDER BY r.round, r.reviewer_id, r.review_id',
			array((int) $articleId)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($files[$row['round']]) || !is_array($files[$row['round']])) {
				$files[$row['round']] = array();
				$thisReviewerId = $row['reviewer_id'];
				$reviewerIndex = 0;
			} else if ($thisReviewerId != $row['reviewer_id']) {
				$thisReviewerId = $row['reviewer_id'];
				$reviewerIndex++;
			}

			$thisArticleFile =& $this->articleFileDao->_returnArticleFileFromRow($row);
			$files[$row['round']][$reviewerIndex][$row['review_id']][] = $thisArticleFile;
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $files;
	}

	/**
	 * Get all cancelled/declined review assignments for an article.
	 * @param $articleId int
	 * @return array ReviewAssignments
	 */
	function &getCancelsAndRegrets($articleId) {
		$reviewAssignments = array();

		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN articles a ON (r.submission_id = a.article_id)
			WHERE	r.submission_id = ? AND
				(r.cancelled = 1 OR r.declined = 1)
			ORDER BY round, review_id',
			(int) $articleId
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Internal function to return a review assignment object from a row.
	 * @param $row array
	 * @return ReviewAssignment
	 */
	function &_returnReviewAssignmentFromRow(&$row) {
		$reviewAssignment = new ReviewAssignment();
		$reviewAssignment->setId($row['review_id']);
		$reviewAssignment->setSubmissionId($row['submission_id']);
		$reviewAssignment->setReviewerId($row['reviewer_id']);
		$reviewAssignment->setReviewerFullName($row['first_name'].' '.$row['last_name']);
		$reviewAssignment->setCompetingInterests($row['competing_interests']);
		$reviewAssignment->setRecommendation($row['recommendation']);
		$reviewAssignment->setDateAssigned($this->datetimeFromDB($row['date_assigned']));
		$reviewAssignment->setDateNotified($this->datetimeFromDB($row['date_notified']));
		$reviewAssignment->setDateConfirmed($this->datetimeFromDB($row['date_confirmed']));
		$reviewAssignment->setDateCompleted($this->datetimeFromDB($row['date_completed']));
		$reviewAssignment->setDateAcknowledged($this->datetimeFromDB($row['date_acknowledged']));
		$reviewAssignment->setDateDue($this->datetimeFromDB($row['date_due']));
		$reviewAssignment->setLastModified($this->datetimeFromDB($row['last_modified']));
		$reviewAssignment->setDeclined($row['declined']);
		$reviewAssignment->setReplaced($row['replaced']);
		$reviewAssignment->setCancelled($row['cancelled']);
		$reviewAssignment->setReviewerFileId($row['reviewer_file_id']);
		$reviewAssignment->setQuality($row['quality']);
		$reviewAssignment->setDateRated($this->datetimeFromDB($row['date_rated']));
		$reviewAssignment->setDateReminded($this->datetimeFromDB($row['date_reminded']));
		$reviewAssignment->setReminderWasAutomatic($row['reminder_was_automatic']);
		$reviewAssignment->setRound($row['round']);
		$reviewAssignment->setReviewFileId($row['review_file_id']);
		$reviewAssignment->setReviewRevision($row['review_revision']);
		$reviewAssignment->setReviewFormId($row['review_form_id']);

		// Files
		$reviewAssignment->setReviewFile($this->articleFileDao->getArticleFile($row['review_file_id'], $row['review_revision']));
		$reviewAssignment->setReviewerFile($this->articleFileDao->getArticleFile($row['reviewer_file_id']));
		$reviewAssignment->setReviewerFileRevisions($this->articleFileDao->getArticleFileRevisions($row['reviewer_file_id']));
		$reviewAssignment->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['submission_id']));


		// Comments
		$reviewAssignment->setMostRecentPeerReviewComment($this->articleCommentDao->getMostRecentArticleComment($row['submission_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		HookRegistry::call('ReviewAssignmentDAO::_returnReviewAssignmentFromRow', array(&$reviewAssignment, &$row));

		return $reviewAssignment;
	}

	/**
	 * Delete review assignments by article.
	 * @param $articleId int
	 * @return boolean
	 */
	function deleteReviewAssignmentsByArticle($articleId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->deleteBySubmissionId($articleId);
	}

	/**
	 * Get the average quality ratings and number of ratings for all users of a journal.
	 * @return array
	 */
	function getAverageQualityRatings($journalId) {
		$averageQualityRatings = Array();
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, AVG(r.quality) AS average, COUNT(r.quality) AS count
			FROM	review_assignments r, articles a
			WHERE	r.submission_id = a.article_id AND
				a.journal_id = ?
			GROUP BY r.reviewer_id',
			(int) $journalId
			);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$averageQualityRatings[$row['reviewer_id']] = array('average' => $row['average'], 'count' => $row['count']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $averageQualityRatings;
	}

	/**
	 * Get the average quality ratings and number of ratings for all users of a journal.
	 * @return array
	 */
	function getCompletedReviewCounts($journalId) {
		$returner = Array();
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, COUNT(r.review_id) AS count
			FROM	review_assignments r,
				articles a
			WHERE	r.submission_id = a.article_id AND
				a.journal_id = ? AND
				r.date_completed IS NOT NULL AND
				r.cancelled = 0
			GROUP BY r.reviewer_id',
			(int) $journalId
			);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['reviewer_id']] = $row['count'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the number of completed reviews for all published review forms of a journal.
	 * @return array
	 */
	function getCompletedReviewCountsForReviewForms($journalId) {
		$returner = array();
		$result =& $this->retrieve(
			'SELECT	r.review_form_id, COUNT(r.review_id) AS count
			FROM	review_assignments r,
				articles a,
				review_forms rf
			WHERE	r.submission_id = a.article_id AND
				a.journal_id = ? AND
				r.review_form_id = rf.review_form_id AND
				rf.published = 1 AND
				r.date_completed IS NOT NULL
			GROUP BY r.review_form_id',
			(int) $journalId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['review_form_id']] = $row['count'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the number of active reviews for all published review forms of a journal.
	 * @return array
	 */
	function getActiveReviewCountsForReviewForms($journalId) {
		$returner = array();
		$result =& $this->retrieve(
			'SELECT	r.review_form_id, COUNT(r.review_id) AS count
			FROM	review_assignments r,
				articles a,
				review_forms rf
			WHERE	r.submission_id = a.article_id AND
				a.journal_id = ? AND
				r.review_form_id = rf.review_form_id AND
				rf.published = 1 AND
				r.date_confirmed IS NOT NULL AND
				r.date_completed IS NULL
			GROUP BY r.review_form_id',
			$journalId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['review_form_id']] = $row['count'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
