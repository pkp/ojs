<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignmentDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewAssignmentDAO
 * @ingroup submission
 * @see ReviewAssignment
 *
 * @brief Class for DAO relating reviewers to articles.
 */

import('classes.submission.reviewAssignment.ReviewAssignment');
import('lib.pkp.classes.submission.reviewAssignment.PKPReviewAssignmentDAO');

class ReviewAssignmentDAO extends PKPReviewAssignmentDAO {
	var $articleFileDao;
	var $suppFileDao;
	var $submissionCommentsDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::PKPReviewAssignmentDAO();
		$this->articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao = DAORegistry::getDAO('SuppFileDAO');
		$this->submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');
	}

	/**
	 * Get a review file for an article for each round.
	 * @param $articleId int
	 * @return array ArticleFiles
	 */
	function &getReviewFilesByRound($articleId) {
		$returner = array();

		$result = $this->retrieve(
			'SELECT	f.*, r.round as round
			FROM	review_rounds r,
				article_files f,
				submissions a
			WHERE	a.submission_id = r.submission_id AND
				r.submission_id = ? AND
				r.submission_id = f.submission_id AND
				f.file_id = a.review_file_id AND
				f.revision = r.review_revision',
			(int) $articleId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['round']] =& $this->articleFileDao->_returnArticleFileFromRow($row);
			$result->MoveNext();
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Get all author-viewable reviewer files for an article for each round.
	 * @param $articleId int
	 * @return array returned[round][reviewer_index] = array of ArticleFiles
	 */
	function &getAuthorViewableFilesByRound($articleId) {
		$files = array();

		$result = $this->retrieve(
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
		return $files;
	}

	/**
	 * Get the average quality ratings and number of ratings for all users of a journal.
	 * @return array
	 */
	function getAverageQualityRatings($journalId) {
		$averageQualityRatings = Array();
		$result = $this->retrieve(
			'SELECT	r.reviewer_id, AVG(r.quality) AS average, COUNT(r.quality) AS count
			FROM	review_assignments r, submissions a
			WHERE	r.submission_id = a.submission_id AND
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
		return $averageQualityRatings;
	}

	/**
	 * Get the average quality ratings and number of ratings for all users of a journal.
	 * @return array
	 */
	function getCompletedReviewCounts($journalId) {
		$returner = array();
		$result = $this->retrieve(
			'SELECT	r.reviewer_id, COUNT(r.review_id) AS count
			FROM	review_assignments r,
				submissions a
			WHERE	r.submission_id = a.submission_id AND
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
		return $returner;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ReviewAssignment
	 */
	function newDataObject() {
		$reviewAssignment = new ReviewAssignment();
		$reviewAssignment->setStageId(1); // Ensure correct default is used
		return $reviewAssignment;
	}

	/**
	 * Internal function to return a review assignment object from a row.
	 * @param $row array
	 * @return ReviewAssignment
	 */
	function _fromRow($row) {
		$reviewAssignment = parent::_fromRow($row);

		// Comments
		$reviewAssignment->setMostRecentPeerReviewComment($this->submissionCommentDao->getMostRecentSubmissionComment($row['submission_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		HookRegistry::call('ReviewAssignmentDAO::_fromRow', array(&$reviewAssignment, &$row));
		return $reviewAssignment;
	}

	/**
	* @see PKPReviewAssignmentDAO::getReviewRoundJoin()
	*/
	function getReviewRoundJoin() {
		return 'r.submission_id = r2.submission_id AND r.round = r2.round';
	}

	/**
	 * Get the last assigned and last completed dates for all reviewers of the given context.
	 * @param $contextId int
	 * @return array
	 */
	function getReviewerStatistics($contextId) {
		// Build an array of all reviewers and provide a placeholder for all statistics (so even if they don't
		//  have a value, it will be filled in as 0
		$statistics = array();
		$reviewerStatsPlaceholder = array('last_notified' => null, 'incomplete' => 0, 'total_span' => 0, 'completed_review_count' => 0, 'average_span' => 0);

		$userDao = DAORegistry::getDAO('UserDAO');
		$allReviewers = $userDao->getAllReviewers($contextId);
		while($reviewer = $allReviewers->next()) {
			$statistics[$reviewer->getId()] = $reviewerStatsPlaceholder;
		}

		// Get counts of completed submissions
		$result = $this->retrieve(
				'SELECT	r.reviewer_id, MAX(r.date_notified) AS last_notified
				FROM	review_assignments r, submissions a
				WHERE	r.submission_id = a.submission_id AND
					a.journal_id = ?
				GROUP BY r.reviewer_id',
				(int) $contextId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = $reviewerStatsPlaceholder;
			$statistics[$row['reviewer_id']]['last_notified'] = $this->datetimeFromDB($row['last_notified']);
			$result->MoveNext();
		}
		$result->Close();

		// Get completion status
		$result = $this->retrieve(
				'SELECT	r.reviewer_id, COUNT(*) AS incomplete
				FROM	review_assignments r, submissions a
				WHERE	r.submission_id = a.submission_id AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NULL AND
				r.cancelled = 0 AND
				a.journal_id = ?
				GROUP BY r.reviewer_id',
				(int) $contextId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = $reviewerStatsPlaceholder;
			$statistics[$row['reviewer_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();

		// Calculate time taken for completed reviews
		$result = $this->retrieve(
				'SELECT	r.reviewer_id, r.date_notified, r.date_completed
				FROM	review_assignments r, submissions a
				WHERE	r.submission_id = a.submission_id AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NOT NULL AND
				r.declined = 0 AND
				a.journal_id = ?',
				(int) $contextId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = $reviewerStatsPlaceholder;

			$completed = strtotime($this->datetimeFromDB($row['date_completed']));
			$notified = strtotime($this->datetimeFromDB($row['date_notified']));
			if (isset($statistics[$row['reviewer_id']]['total_span'])) {
				$statistics[$row['reviewer_id']]['total_span'] += $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] += 1;
			} else {
				$statistics[$row['reviewer_id']]['total_span'] = $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] = 1;
			}

			// Calculate the average length of review in days.
			$statistics[$row['reviewer_id']]['average_span'] = round(($statistics[$row['reviewer_id']]['total_span'] / $statistics[$row['reviewer_id']]['completed_review_count']) / 86400);
			$result->MoveNext();
		}

		$result->Close();
		return $statistics;
	}
}

?>
