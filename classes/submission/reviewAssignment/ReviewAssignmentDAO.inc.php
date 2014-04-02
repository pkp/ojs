<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignmentDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	 * Return the review file ID for a submission, given its submission ID.
	 * @param $submissionId int
	 * @return int
	 */
	function _getSubmissionReviewFileId($submissionId) {
		$result =& $this->retrieve(
			'SELECT review_file_id FROM articles WHERE article_id = ?',
			(int) $submissionId
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : null;
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
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getById($reviewId);
		return $returner;
	}

	/**
	 * Get all review assignments for an article.
	 * @param $articleId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByArticleId($articleId, $round = null) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getBySubmissionId($articleId, $round);
		return $returner;
	}

	/**
	 * Get all review assignments for a reviewer.
	 * @param $userId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByUserId($userId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getByUserId($userId);
		return $returner;
	}

	/**
	 * Get all review assignments for a review form.
	 * @param $reviewFormId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByReviewFormId($reviewFormId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getByReviewFormId($reviewFormId);
		return $returner;
	}

	/**
	 * Get a review file for an article for each round.
	 * @param $articleId int
	 * @return array ArticleFiles
	 */
	function &getReviewFilesByRound($articleId) {
		$returner = array();

		$result =& $this->retrieve(
			'SELECT	f.*, r.round as round
			FROM	review_rounds r,
				article_files f,
				articles a
			WHERE	a.article_id = r.submission_id AND
				r.submission_id = ? AND
				r.submission_id = f.article_id AND
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
	function &_fromRow(&$row) {
		$reviewAssignment =& parent::_fromRow($row);
		$reviewFileId = $this->_getSubmissionReviewFileId($reviewAssignment->getSubmissionId());
		$reviewAssignment->setReviewFileId($reviewFileId);

		// Files
		$reviewAssignment->setReviewFile($this->articleFileDao->getArticleFile($reviewFileId, $row['review_revision']));
		$reviewAssignment->setReviewerFile($this->articleFileDao->getArticleFile($row['reviewer_file_id']));
		$reviewAssignment->setReviewerFileRevisions($this->articleFileDao->getArticleFileRevisions($row['reviewer_file_id']));
		$reviewAssignment->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['submission_id']));

		// Comments
		$reviewAssignment->setMostRecentPeerReviewComment($this->articleCommentDao->getMostRecentArticleComment($row['submission_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		HookRegistry::call('ReviewAssignmentDAO::_fromRow', array(&$reviewAssignment, &$row));
		return $reviewAssignment;
	}

	/**
	* @see PKPReviewAssignmentDAO::getReviewRoundJoin()
	*/
	function getReviewRoundJoin() {
		return 'r.submission_id = r2.submission_id AND r.round = r2.round';
	}
}

?>
