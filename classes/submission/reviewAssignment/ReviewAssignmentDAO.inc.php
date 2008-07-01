<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignmentDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewAssignmentDAO
 * @ingroup submission
 * @see ReviewAssignment
 *
 * @brief Class for DAO relating reviewers to articles.
 */

// $Id$


import('submission.reviewAssignment.ReviewAssignment');

class ReviewAssignmentDAO extends DAO {
	var $userDao;
	var $articleFileDao;
	var $suppFileDao;
	var $articleCommentsDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::DAO();
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
	}

	/**
	 * Retrieve a review assignment by reviewer and article.
	 * @param $articleId int
	 * @param $reviewerId int
	 * @return ReviewAssignment
	 */
	function &getReviewAssignment($articleId, $reviewerId, $round) {
		$result = &$this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.article_id = ? AND r.reviewer_id = ? AND r.cancelled <> 1 AND r.round = ?',
			array((int) $articleId, (int) $reviewerId, (int) $round)
			);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
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
		$result = &$this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.review_id = ?',
			(int) $reviewId
			);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Determine the order of active reviews for the given round of the given article
	 * @param $articleId int
	 * @param $round int
	 * @return array associating review ID with number; ie if review ID 26 is first, returned['26']=0
	 */
	function &getReviewIndexesForRound($articleId, $round) {
		$returner = array();
		$index = 0;
		$result = &$this->retrieve(
			'SELECT review_id FROM review_assignments WHERE article_id = ? and round = ? AND (cancelled = 0 OR cancelled IS NULL) ORDER BY review_id',
			array((int) $articleId, (int) $round)
			);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['review_id']] = $index++;
			$result->MoveNext();
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

		$result = &$this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE (r.cancelled IS NULL OR r.cancelled = 0) AND r.date_notified IS NOT NULL AND r.date_completed IS NULL AND r.declined <> 1 ORDER BY r.article_id'
		);

		while (!$result->EOF) {
			$reviewAssignments[] = &$this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
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

		if ($round == null) {
			$result = &$this->retrieve(
				'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.article_id = ? ORDER BY round, review_id',
				(int) $articleId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.article_id = ? AND r.round = ? ORDER BY review_id',
				array((int) $articleId, (int) $round)
			);
		}

		while (!$result->EOF) {
			$reviewAssignments[] = &$this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
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

		$result = &$this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.reviewer_id = ? ORDER BY round, review_id',
			(int) $userId
		);

		while (!$result->EOF) {
			$reviewAssignments[] = &$this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
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

		$result = &$this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.review_form_id = ? ORDER BY round, review_id',
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

		$result = &$this->retrieve(
			'SELECT a.*, r.round as round from review_rounds r, article_files a, articles art where art.article_id=r.article_id and r.article_id=? and r.article_id=a.article_id and a.file_id=art.review_file_id and a.revision=r.review_revision and a.article_id=r.article_id', 
			(int) $articleId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['round']] = &$this->articleFileDao->_returnArticleFileFromRow($row);
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

		$result = &$this->retrieve(
			'select f.*, a.reviewer_id as reviewer_id, a.review_id from review_assignments a, article_files f where reviewer_file_id = file_id and viewable=1 and a.article_id=? order by a.round, a.reviewer_id, a.review_id', 
			(int) $articleId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($files[$row['round']]) || !is_array($files[$row['round']])) {
				$files[$row['round']] = array();
				$this_reviewer_id = $row['reviewer_id'];
				$reviewer_index = 0;
			}
			else if ($this_reviewer_id != $row['reviewer_id']) {
				$this_reviewer_id = $row['reviewer_id'];
				$reviewer_index++;
			}

			if (!isset($files[$row['round']][$reviewer_index]) || !is_array($files[$row['round']][$reviewer_index])) {
				$files[$row['round']][$reviewer_index] = array();
			}
			$thisArticleFile = &$this->articleFileDao->_returnArticleFileFromRow($row);
			$files[$row['round']][$reviewer_index][$row['review_id']] = $thisArticleFile;
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $files;
	}

	/**
	 * Get the most recent last modified date for all review assignments for each round of a submission.
	 * @param $articleId int
	 * @param $round int
	 * @return array associating round with most recent last modified date
	 */
	function &getLastModifiedByRound($articleId) {
		$returner = array();

		$result = &$this->retrieve(
			'SELECT round, MAX(last_modified) as last_modified FROM review_assignments WHERE article_id=? GROUP BY round', 
			(int) $articleId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['round']] = $this->datetimeFromDB($row['last_modified']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the first notified date from all review assignments for a round of a submission.
	 * @param $articleId int
	 * @param $round int
	 * @return array Associative array of ($round_num => $earliest_date_of_notification)*
	 */
	function &getEarliestNotificationByRound($articleId) {
		$returner = array();

		$result = &$this->retrieve(
			'SELECT round, MIN(date_notified) as earliest_date FROM review_assignments WHERE article_id=? GROUP BY round', 
			(int) $articleId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['round']] = $this->datetimeFromDB($row['earliest_date']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get all cancelled/declined review assignments for an article.
	 * @param $articleId int
	 * @return array ReviewAssignments
	 */
	function &getCancelsAndRegrets($articleId) {
		$reviewAssignments = array();

		$result = &$this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.article_id = ? AND (r.cancelled = 1 OR r.declined = 1) ORDER BY round, review_id',
			(int) $articleId
		);

		while (!$result->EOF) {
			$reviewAssignments[] = &$this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
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
		$reviewAssignment = &new ReviewAssignment();
		$reviewAssignment->setReviewId($row['review_id']);
		$reviewAssignment->setArticleId($row['article_id']);
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
		$reviewAssignment->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));


		// Comments
		$reviewAssignment->setMostRecentPeerReviewComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		HookRegistry::call('ReviewAssignmentDAO::_returnReviewAssignmentFromRow', array(&$reviewAssignment, &$row));

		return $reviewAssignment;
	}

	/**
	 * Insert a new Review Assignment.
	 * @param $reviewAssignment ReviewAssignment
	 */	
	function insertReviewAssignment(&$reviewAssignment) {
		$this->update(
			sprintf('INSERT INTO review_assignments
				(article_id, reviewer_id, round, competing_interests, recommendation, declined, replaced, cancelled, date_assigned, date_notified, date_confirmed, date_completed, date_acknowledged, date_due, reviewer_file_id, quality, date_rated, last_modified, date_reminded, reminder_was_automatic, review_form_id)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, %s, %s, %s, %s, %s, %s, ?, ?, %s, %s, %s, ?, ?)',
				$this->datetimeToDB($reviewAssignment->getDateAssigned()), $this->datetimeToDB($reviewAssignment->getDateNotified()), $this->datetimeToDB($reviewAssignment->getDateConfirmed()), $this->datetimeToDB($reviewAssignment->getDateCompleted()), $this->datetimeToDB($reviewAssignment->getDateAcknowledged()), $this->datetimeToDB($reviewAssignment->getDateDue()), $this->datetimeToDB($reviewAssignment->getDateRated()), $this->datetimeToDB($reviewAssignment->getLastModified()), $this->datetimeToDB($reviewAssignment->getDateReminded())),
			array(
				(int) $reviewAssignment->getArticleId(),
				(int) $reviewAssignment->getReviewerId(),
				max((int) $reviewAssignment->getRound(), 1),
				$reviewAssignment->getCompetingInterests(),
				$reviewAssignment->getRecommendation(),
				(int) $reviewAssignment->getDeclined(),
				(int) $reviewAssignment->getReplaced(),
				(int) $reviewAssignment->getCancelled(),
				$reviewAssignment->getReviewerFileId(),
				$reviewAssignment->getQuality(),
				$reviewAssignment->getReminderWasAutomatic(),
				$reviewAssignment->getReviewFormId()
			)
		);

		$reviewAssignment->setReviewId($this->getInsertReviewId());
		return $reviewAssignment->getReviewId();
	}

	/**
	 * Update an existing review assignment.
	 * @param $reviewAssignment object
	 */
	function updateReviewAssignment(&$reviewAssignment) {
		return $this->update(
			sprintf('UPDATE review_assignments
				SET	article_id = ?,
					reviewer_id = ?,
					round = ?,
					competing_interests = ?,
					recommendation = ?,
					declined = ?,
					replaced = ?,
					cancelled = ?,
					date_assigned = %s,
					date_notified = %s,
					date_confirmed = %s,
					date_completed = %s,
					date_acknowledged = %s,
					date_due = %s,
					reviewer_file_id = ?,
					quality = ?,
					date_rated = %s,
					last_modified = %s,
					date_reminded = %s,
					reminder_was_automatic = ?,
					review_form_id = ?
				WHERE review_id = ?',
				$this->datetimeToDB($reviewAssignment->getDateAssigned()), $this->datetimeToDB($reviewAssignment->getDateNotified()), $this->datetimeToDB($reviewAssignment->getDateConfirmed()), $this->datetimeToDB($reviewAssignment->getDateCompleted()), $this->datetimeToDB($reviewAssignment->getDateAcknowledged()), $this->datetimeToDB($reviewAssignment->getDateDue()), $this->datetimeToDB($reviewAssignment->getDateRated()), $this->datetimeToDB($reviewAssignment->getLastModified()), $this->datetimeToDB($reviewAssignment->getDateReminded())),
			array(
				(int) $reviewAssignment->getArticleId(),
				(int) $reviewAssignment->getReviewerId(),
				(int) $reviewAssignment->getRound(),
				$reviewAssignment->getCompetingInterests(),
				$reviewAssignment->getRecommendation(),
				(int) $reviewAssignment->getDeclined(),
				(int) $reviewAssignment->getReplaced(),
				(int) $reviewAssignment->getCancelled(),
				$reviewAssignment->getReviewerFileId(),
				$reviewAssignment->getQuality(),
				$reviewAssignment->getReminderWasAutomatic(),
				$reviewAssignment->getReviewFormId(),
				(int) $reviewAssignment->getReviewId()
			)
		);
	}

	/**
	 * Delete review assignment.
	 * @param $reviewId int
	 */
	function deleteReviewAssignmentById($reviewId) {
		$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponseDao->deleteReviewFormResponseByReviewId($reviewId);

		return $this->update(
			'DELETE FROM review_assignments WHERE review_id = ?',
			(int) $reviewId
		);
	}

	/**
	 * Delete review assignments by article.
	 * @param $articleId int
	 * @return boolean
	 */
	function deleteReviewAssignmentsByArticle($articleId) {
		$returner = false;
		$result =& $this->retrieve(
			'SELECT review_id FROM review_assignments WHERE article_id = ?',
			(int) $articleId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$reviewId = $row['review_id'];

			$this->update('DELETE FROM review_form_responses WHERE review_id = ?', $reviewId);
			$this->update('DELETE FROM review_assignments WHERE review_id = ?', $reviewId);

			$result->MoveNext();
			$returner = true;
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get the ID of the last inserted review assignment.
	 * @return int
	 */
	function getInsertReviewId() {
		return $this->getInsertId('review_assignments', 'review_id');
	}

	/**
	 * Get the average quality ratings and number of ratings for all users of a journal.
	 * @return array
	 */
	function getAverageQualityRatings($journalId) {
		$averageQualityRatings = Array();
		$result = &$this->retrieve(
			'SELECT R.reviewer_id, AVG(R.quality) AS average, COUNT(R.quality) AS count FROM review_assignments R, articles A WHERE R.article_id = A.article_id AND A.journal_id = ? GROUP BY R.reviewer_id',
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
		$result = &$this->retrieve(
			'SELECT r.reviewer_id, COUNT(r.review_id) AS count FROM review_assignments r, articles a WHERE r.article_id = a.article_id AND a.journal_id = ? AND r.date_completed IS NOT NULL GROUP BY r.reviewer_id',
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
			WHERE	r.article_id = a.article_id AND
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
			WHERE	r.article_id = a.article_id AND
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
