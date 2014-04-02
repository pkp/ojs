<?php

/**
 * @file classes/submission/author/AuthorSubmissionDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmissionDAO
 * @ingroup submission
 * @see AuthorSubmission
 *
 * @brief Operations for retrieving and modifying AuthorSubmission objects.
 */

import('classes.submission.author.AuthorSubmission');

class AuthorSubmissionDAO extends DAO {
	var $articleDao;
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $articleFileDao;
	var $suppFileDao;
	var $copyeditorSubmissionDao;
	var $articleCommentDao;
	var $galleyDao;

	/**
	 * Constructor.
	 */
	function AuthorSubmissionDAO() {
		parent::DAO();
		$this->articleDao =& DAORegistry::getDAO('ArticleDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$this->articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$this->articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$this->galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
	}

	/**
	 * Retrieve a author submission by article ID.
	 * @param $articleId int
	 * @return AuthorSubmission
	 */
	function &getAuthorSubmission($articleId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$result =& $this->retrieve(
			'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM articles a
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.article_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$articleId,
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnAuthorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a AuthorSubmission object from a row.
	 * @param $row array
	 * @return AuthorSubmission
	 */
	function &_returnAuthorSubmissionFromRow(&$row) {
		$authorSubmission = new AuthorSubmission();

		// Article attributes
		$this->articleDao->_articleFromRow($authorSubmission, $row);

		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByArticleId($row['article_id']);
		$authorSubmission->setEditAssignments($editAssignments->toArray());

		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setDecisions($this->getEditorDecisions($row['article_id'], $i), $i);
		}

		// Review Assignments
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setReviewAssignments($this->reviewAssignmentDao->getBySubmissionId($row['article_id'], $i), $i);
		}

		// Comments
		$authorSubmission->setMostRecentEditorDecisionComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_EDITOR_DECISION, $row['article_id']));
		$authorSubmission->setMostRecentCopyeditComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_COPYEDIT, $row['article_id']));
		$authorSubmission->setMostRecentProofreadComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_PROOFREAD, $row['article_id']));
		$authorSubmission->setMostRecentLayoutComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_LAYOUT, $row['article_id']));

		// Files
		$authorSubmission->setSubmissionFile($this->articleFileDao->getArticleFile($row['submission_file_id']));
		$authorSubmission->setRevisedFile($this->articleFileDao->getArticleFile($row['revised_file_id']));
		$authorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setAuthorFileRevisions($this->articleFileDao->getArticleFileRevisions($row['revised_file_id'], $i), $i);
		}
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setEditorFileRevisions($this->articleFileDao->getArticleFileRevisions($row['editor_file_id'], $i), $i);
		}
		$authorSubmission->setGalleys($this->galleyDao->getGalleysByArticle($row['article_id']));

		HookRegistry::call('AuthorSubmissionDAO::_returnAuthorSubmissionFromRow', array(&$authorSubmission, &$row));

		return $authorSubmission;
	}

	/**
	 * Update an existing author submission.
	 * @param $authorSubmission AuthorSubmission
	 */
	function updateAuthorSubmission(&$authorSubmission) {
		// Update article
		if ($authorSubmission->getId()) {
			$article =& $this->articleDao->getArticle($authorSubmission->getId());

			// Only update fields that an author can actually edit.
			$article->setRevisedFileId($authorSubmission->getRevisedFileId());
			$article->setDateStatusModified($authorSubmission->getDateStatusModified());
			$article->setLastModified($authorSubmission->getLastModified());
			// FIXME: These two are necessary for designating the
			// original as the review version, but they are probably
			// best not exposed like this.
			$article->setReviewFileId($authorSubmission->getReviewFileId());
			$article->setEditorFileId($authorSubmission->getEditorFileId());

			$this->articleDao->updateArticle($article);
		}

	}

	/**
	 * Get all author submissions for an author.
	 * @param $authorId int
	 * @return DAOResultFactory continaing AuthorSubmissions
	 */
	function &getAuthorSubmissions($authorId, $journalId, $active = true, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$result =& $this->retrieveRange(
			'SELECT	a.*,
				COALESCE(atl.setting_value, atpl.setting_value) AS submission_title,
				aa.last_name AS author_name,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN authors aa ON (aa.submission_id = a.article_id AND aa.primary_contact = 1)
				LEFT JOIN article_settings atpl ON (atpl.article_id = a.article_id AND atpl.setting_name = ? AND atpl.locale = a.locale)
				LEFT JOIN article_settings atl ON (atl.article_id = a.article_id AND atl.setting_name = ? AND atl.locale = ?)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.user_id = ? AND a.journal_id = ? AND ' .
			($active?('a.status = ' . STATUS_QUEUED):('(a.status <> ' . STATUS_QUEUED . ' AND a.submission_progress = 0)')) .
			($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : ''),
			array(
				'cleanTitle',
				'cleanTitle',
				$locale,
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$authorId,
				$journalId
			),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAuthorSubmissionFromRow');
		return $returner;
	}

	//
	// Miscellaneous
	//

	/**
	 * Get the editor decisions for a review round of an article.
	 * @param $articleId int
	 * @param $round int
	 */
	function getEditorDecisions($articleId, $round = null) {
		$decisions = array();

		if ($round == null) {
			$result =& $this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? ORDER BY date_decided ASC', $articleId
			);
		} else {
			$result =& $this->retrieve(
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

	/**
	 * Get count of active, rejected, and complete assignments
	 * @param authorId int
	 * @param journalId int
	 */
	function getSubmissionsCount($authorId, $journalId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0; //pending items
		$submissionsCount[1] = 0; //all non-pending items

		$sql = 'SELECT count(*), status FROM articles a LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ? AND a.user_id = ? GROUP BY a.status';

		$result =& $this->retrieve($sql, array($journalId, $authorId));

		while (!$result->EOF) {
			if ($result->fields['status'] != 1) {
				$submissionsCount[1] += $result->fields[0];
			} else {
				$submissionsCount[0] += $result->fields[0];
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $submissionsCount;
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	function getSortMapping($heading) {
		switch ($heading) {
			case 'status': return 'a.status';
			case 'id': return 'a.article_id';
			case 'submitDate': return 'a.date_submitted';
			case 'section': return 'section_abbrev';
			case 'authors': return 'author_name';
			case 'title': return 'submission_title';
			case 'active': return 'a.submission_progress';
			case 'views': return 'galley_views';
			case 'status': return 'a.status';
			default: return null;
		}
	}
}

?>
