<?php

/**
 * @file classes/submission/reviewer/ReviewerSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSubmissionDAO
 * @ingroup submission
 * @see ReviewerSubmission
 *
 * @brief Operations for retrieving and modifying ReviewerSubmission objects.
 */

// $Id$


import('submission.reviewer.ReviewerSubmission');

class ReviewerSubmissionDAO extends DAO {
	var $articleDao;
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $editAssignmentDao;
	var $articleFileDao;
	var $suppFileDao;
	var $articleCommentDao;

	/**
	 * Constructor.
	 */
	function ReviewerSubmissionDAO() {
		parent::DAO();
		$this->articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->authorDao = &DAORegistry::getDAO('AuthorDAO');
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$this->articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
	}

	/**
	 * Retrieve a reviewer submission by article ID.
	 * @param $articleId int
	 * @param $reviewerId int
	 * @return ReviewerSubmission
	 */
	function &getReviewerSubmission($reviewId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$result = &$this->retrieve(
			'SELECT	a.*,
				r.*,
				r2.review_revision,
				u.first_name, u.last_name,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN review_assignments r ON (a.article_id = r.article_id)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (a.article_id = r2.article_id AND r.round = r2.round)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	r.review_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$reviewId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnReviewerSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a ReviewerSubmission object from a row.
	 * @param $row array
	 * @return ReviewerSubmission
	 */
	function &_returnReviewerSubmissionFromRow(&$row) {
		$reviewerSubmission = &new ReviewerSubmission();

		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByArticleId($row['article_id']);
		$reviewerSubmission->setEditAssignments($editAssignments->toArray());

		// Files
		$reviewerSubmission->setSubmissionFile($this->articleFileDao->getArticleFile($row['submission_file_id']));
		$reviewerSubmission->setRevisedFile($this->articleFileDao->getArticleFile($row['revised_file_id']));
		$reviewerSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		$reviewerSubmission->setReviewFile($this->articleFileDao->getArticleFile($row['review_file_id']));
		$reviewerSubmission->setReviewerFile($this->articleFileDao->getArticleFile($row['reviewer_file_id']));
		$reviewerSubmission->setReviewerFileRevisions($this->articleFileDao->getArticleFileRevisions($row['reviewer_file_id']));

		// Comments
		$reviewerSubmission->setMostRecentPeerReviewComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$reviewerSubmission->setDecisions($this->getEditorDecisions($row['article_id'], $i), $i);
		}

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
		$reviewerSubmission->setDeclined($row['declined']);
		$reviewerSubmission->setReplaced($row['replaced']);
		$reviewerSubmission->setCancelled($row['cancelled']==1?1:0);
		$reviewerSubmission->setReviewerFileId($row['reviewer_file_id']);
		$reviewerSubmission->setQuality($row['quality']);
		$reviewerSubmission->setRound($row['round']);
		$reviewerSubmission->setReviewFileId($row['review_file_id']);
		$reviewerSubmission->setReviewRevision($row['review_revision']);

		// Article attributes
		$this->articleDao->_articleFromRow($reviewerSubmission, $row);

		HookRegistry::call('ReviewerSubmissionDAO::_returnReviewerSubmissionFromRow', array(&$reviewerSubmission, &$row));

		return $reviewerSubmission;
	}

	/**
	 * Update an existing review submission.
	 * @param $reviewSubmission ReviewSubmission
	 */
	function updateReviewerSubmission(&$reviewerSubmission) {
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
					quality = ?
				WHERE review_id = ?',
				$this->datetimeToDB($reviewerSubmission->getDateAssigned()), $this->datetimeToDB($reviewerSubmission->getDateNotified()), $this->datetimeToDB($reviewerSubmission->getDateConfirmed()), $this->datetimeToDB($reviewerSubmission->getDateCompleted()), $this->datetimeToDB($reviewerSubmission->getDateAcknowledged()), $this->datetimeToDB($reviewerSubmission->getDateDue())),
			array(
				$reviewerSubmission->getArticleId(),
				$reviewerSubmission->getReviewerId(),
				$reviewerSubmission->getRound(),
				$reviewerSubmission->getCompetingInterests(),
				$reviewerSubmission->getRecommendation(),
				$reviewerSubmission->getDeclined(),
				$reviewerSubmission->getReplaced(),
				$reviewerSubmission->getCancelled(),
				$reviewerSubmission->getReviewerFileId(),
				$reviewerSubmission->getQuality(),
				$reviewerSubmission->getReviewId()
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
	function &getReviewerSubmissionsByReviewerId($reviewerId, $journalId, $active = true, $rangeInfo = null) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$sql = 'SELECT	a.*,
				r.*,
				r2.review_revision,
				u.first_name, u.last_name,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN review_assignments r ON (a.article_id = r.article_id)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.journal_id = ?
				AND r.reviewer_id = ?
				AND r.date_notified IS NOT NULL';

		if ($active) {
			$sql .=  ' AND r.date_completed IS NULL AND r.declined <> 1 AND (r.cancelled = 0 OR r.cancelled IS NULL)';
		} else {
			$sql .= ' AND (r.date_completed IS NOT NULL OR r.cancelled = 1 OR r.declined = 1)';
		}

		$result = &$this->retrieveRange(
			$sql,
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$journalId,
				$reviewerId
			),
			$rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnReviewerSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get count of active and complete assignments
	 * @param reviewerId int
	 * @param journalId int
	 */
	function getSubmissionsCount($reviewerId, $journalId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = 'SELECT r.date_completed, r.declined, r.cancelled FROM articles a LEFT JOIN review_assignments r ON (a.article_id = r.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round)  WHERE a.journal_id = ? AND r.reviewer_id = ? AND r.date_notified IS NOT NULL';

		$result = &$this->retrieve($sql, array($journalId, $reviewerId));

		while (!$result->EOF) {
			if ($result->fields['date_completed'] == null && $result->fields['declined'] != 1 && $result->fields['cancelled'] != 1) {
				$submissionsCount[0] += 1;
			} else {
				$submissionsCount[1] += 1;
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $submissionsCount;
	}

	/**
	 * Get the editor decisions for a review round of an article.
	 * @param $articleId int
	 * @param $round int
	 */
	function getEditorDecisions($articleId, $round = null) {
		$decisions = array();

		if ($round == null) {
			$result = &$this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? ORDER BY date_decided ASC', $articleId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? AND round = ? ORDER BY date_decided ASC',
				array($articleId, $round)
			);
		}

		while (!$result->EOF) {
			$decisions[] = array(
				'editDecisionId' => $result->fields['edit_decision_id'],
				'editorId' => $result->fields['editor_id'],
				'decision' => $result->fields['decision'],
				'dateDecided' => $this->datetimeFromDB($result->fields['date_decided'])
			);
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $decisions;
	}
}

?>
