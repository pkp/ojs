<?php

/**
 * @file classes/submission/sectionEditor/SectionEditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionEditorSubmissionDAO
 * @ingroup submission
 * @see SectionEditorSubmission
 *
 * @brief Operations for retrieving and modifying SectionEditorSubmission objects.
 */

// $Id$


import('submission.sectionEditor.SectionEditorSubmission');
import('submission.author.AuthorSubmission'); // Bring in editor decision constants
import('submission.reviewer.ReviewerSubmission'); // Bring in editor decision constants

class SectionEditorSubmissionDAO extends DAO {
	var $articleDao;
	var $authorDao;
	var $userDao;
	var $editAssignmentDao;
	var $reviewAssignmentDao;
	var $copyeditorSubmissionDao;
	var $layoutAssignmentDao;
	var $articleFileDao;
	var $suppFileDao;
	var $galleyDao;
	var $articleEmailLogDao;
	var $articleCommentDao;
	var $proofAssignmentDao;

	/**
	 * Constructor.
	 */
	function SectionEditorSubmissionDAO() {
		parent::DAO();
		$this->articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->authorDao = &DAORegistry::getDAO('AuthorDAO');
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$this->reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$this->layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$this->articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$this->articleEmailLogDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		$this->articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$this->proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
	}

	/**
	 * Retrieve a section editor submission by article ID.
	 * @param $articleId int
	 * @return EditorSubmission
	 */
	function &getSectionEditorSubmission($articleId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$result = &$this->retrieve(
			'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				c.copyed_id,
				c.copyeditor_id,
				c.date_notified AS copyeditor_date_notified,
				c.date_underway AS copyeditor_date_underway,
				c.date_completed AS copyeditor_date_completed,
				c.date_acknowledged AS copyeditor_date_acknowledged,
				c.date_author_notified AS ce_date_author_notified,
				c.date_author_underway AS ce_date_author_underway,
				c.date_author_completed AS ce_date_author_completed,
				c.date_author_acknowledged AS ce_date_author_acknowledged,
				c.date_final_notified AS ce_date_final_notified,
				c.date_final_underway AS ce_date_final_underway,
				c.date_final_completed AS ce_date_final_completed,
				c.date_final_acknowledged AS ce_date_final_acknowledged,
				c.initial_revision AS copyeditor_initial_revision,
				c.editor_author_revision AS ce_editor_author_revision,
				c.final_revision AS copyeditor_final_revision,
				r2.review_revision
			FROM	articles a
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id)
				LEFT JOIN review_rounds r2 ON (a.article_id = r2.article_id AND a.current_round = r2.round)
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
				$articleId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a SectionEditorSubmission object from a row.
	 * @param $row array
	 * @return SectionEditorSubmission
	 */
	function &_returnSectionEditorSubmissionFromRow(&$row) {
		$sectionEditorSubmission = &new SectionEditorSubmission();

		// Article attributes
		$this->articleDao->_articleFromRow($sectionEditorSubmission, $row);

		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByArticleId($row['article_id']);
		$sectionEditorSubmission->setEditAssignments($editAssignments->toArray());

		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$sectionEditorSubmission->setDecisions($this->getEditorDecisions($row['article_id'], $i), $i);
		}

		// Comments
		$sectionEditorSubmission->setMostRecentEditorDecisionComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_EDITOR_DECISION, $row['article_id']));
		$sectionEditorSubmission->setMostRecentCopyeditComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_COPYEDIT, $row['article_id']));
		$sectionEditorSubmission->setMostRecentLayoutComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_LAYOUT, $row['article_id']));
		$sectionEditorSubmission->setMostRecentProofreadComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_PROOFREAD, $row['article_id']));

		// Files
		$sectionEditorSubmission->setSubmissionFile($this->articleFileDao->getArticleFile($row['submission_file_id']));
		$sectionEditorSubmission->setRevisedFile($this->articleFileDao->getArticleFile($row['revised_file_id']));
		$sectionEditorSubmission->setReviewFile($this->articleFileDao->getArticleFile($row['review_file_id']));
		$sectionEditorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		$sectionEditorSubmission->setEditorFile($this->articleFileDao->getArticleFile($row['editor_file_id']));
		$sectionEditorSubmission->setCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id']));

		// Initial Copyedit File
		if ($row['copyeditor_initial_revision'] != null) {
			$sectionEditorSubmission->setInitialCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['copyeditor_initial_revision']));
		}

		// Editor / Author Copyedit File
		if ($row['ce_editor_author_revision'] != null) {
			$sectionEditorSubmission->setEditorAuthorCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['ce_editor_author_revision']));
		}

		// Final Copyedit File
		if ($row['copyeditor_final_revision'] != null) {
			$sectionEditorSubmission->setFinalCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['copyeditor_final_revision']));
		}

		$sectionEditorSubmission->setCopyeditFileRevisions($this->articleFileDao->getArticleFileRevisionsInRange($row['copyedit_file_id']));

		for ($i = 1; $i <= $row['current_round']; $i++) {
			$sectionEditorSubmission->setEditorFileRevisions($this->articleFileDao->getArticleFileRevisions($row['editor_file_id'], $i), $i);
			$sectionEditorSubmission->setAuthorFileRevisions($this->articleFileDao->getArticleFileRevisions($row['revised_file_id'], $i), $i);
		}

		// Review Rounds
		$sectionEditorSubmission->setReviewRevision($row['review_revision']);

		// Review Assignments
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$sectionEditorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByArticleId($row['article_id'], $i), $i);
		}

		// Copyeditor Assignment
		$sectionEditorSubmission->setCopyedId($row['copyed_id']);
		$sectionEditorSubmission->setCopyeditorId($row['copyeditor_id']);
		$sectionEditorSubmission->setCopyeditor($this->userDao->getUser($row['copyeditor_id']), true);
		$sectionEditorSubmission->setCopyeditorDateNotified($this->datetimeFromDB($row['copyeditor_date_notified']));
		$sectionEditorSubmission->setCopyeditorDateUnderway($this->datetimeFromDB($row['copyeditor_date_underway']));
		$sectionEditorSubmission->setCopyeditorDateCompleted($this->datetimeFromDB($row['copyeditor_date_completed']));
		$sectionEditorSubmission->setCopyeditorDateAcknowledged($this->datetimeFromDB($row['copyeditor_date_acknowledged']));
		$sectionEditorSubmission->setCopyeditorDateAuthorNotified($this->datetimeFromDB($row['ce_date_author_notified']));
		$sectionEditorSubmission->setCopyeditorDateAuthorUnderway($this->datetimeFromDB($row['ce_date_author_underway']));
		$sectionEditorSubmission->setCopyeditorDateAuthorCompleted($this->datetimeFromDB($row['ce_date_author_completed']));
		$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged($this->datetimeFromDB($row['ce_date_author_acknowledged']));
		$sectionEditorSubmission->setCopyeditorDateFinalNotified($this->datetimeFromDB($row['ce_date_final_notified']));
		$sectionEditorSubmission->setCopyeditorDateFinalUnderway($this->datetimeFromDB($row['ce_date_final_underway']));
		$sectionEditorSubmission->setCopyeditorDateFinalCompleted($this->datetimeFromDB($row['ce_date_final_completed']));
		$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged($this->datetimeFromDB($row['ce_date_final_acknowledged']));
		$sectionEditorSubmission->setCopyeditorInitialRevision($row['copyeditor_initial_revision']);
		$sectionEditorSubmission->setCopyeditorEditorAuthorRevision($row['ce_editor_author_revision']);
		$sectionEditorSubmission->setCopyeditorFinalRevision($row['copyeditor_final_revision']);

		// Layout Editing
		$sectionEditorSubmission->setLayoutAssignment($this->layoutAssignmentDao->getLayoutAssignmentByArticleId($row['article_id']));

		$sectionEditorSubmission->setGalleys($this->galleyDao->getGalleysByArticle($row['article_id']));

		// Proof Assignment
		$sectionEditorSubmission->setProofAssignment($this->proofAssignmentDao->getProofAssignmentByArticleId($row['article_id']));

		HookRegistry::call('SectionEditorSubmissionDAO::_returnSectionEditorSubmissionFromRow', array(&$sectionEditorSubmission, &$row));

		return $sectionEditorSubmission;
	}

	/**
	 * Update an existing section editor submission.
	 * @param $sectionEditorSubmission SectionEditorSubmission
	 */
	function updateSectionEditorSubmission(&$sectionEditorSubmission) {
		// update edit assignment
		$editAssignments =& $sectionEditorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getEditId() > 0) {
				$this->editAssignmentDao->updateEditAssignment($editAssignment);
			} else {
				$this->editAssignmentDao->insertEditAssignment($editAssignment);
			}
		}

		// Update editor decisions
		for ($i = 1; $i <= $sectionEditorSubmission->getCurrentRound(); $i++) {
			$editorDecisions = $sectionEditorSubmission->getDecisions($i);
			if (is_array($editorDecisions)) {
				foreach ($editorDecisions as $editorDecision) {
					if ($editorDecision['editDecisionId'] == null) {
						$this->update(
							sprintf('INSERT INTO edit_decisions
								(article_id, round, editor_id, decision, date_decided)
								VALUES (?, ?, ?, ?, %s)',
								$this->datetimeToDB($editorDecision['dateDecided'])),
							array($sectionEditorSubmission->getArticleId(), $sectionEditorSubmission->getCurrentRound(), $editorDecision['editorId'], $editorDecision['decision'])
						);
					}
				}
			}
		}
		if ($this->reviewRoundExists($sectionEditorSubmission->getArticleId(), $sectionEditorSubmission->getCurrentRound())) {
			$this->update(
				'UPDATE review_rounds
					SET
						review_revision = ?
					WHERE article_id = ? AND round = ?',
				array(
					$sectionEditorSubmission->getReviewRevision(),
					$sectionEditorSubmission->getArticleId(),
					$sectionEditorSubmission->getCurrentRound()
				)
			);
		} elseif ($sectionEditorSubmission->getReviewRevision()!=null) {
			$this->createReviewRound(
				$sectionEditorSubmission->getArticleId(),
				$sectionEditorSubmission->getCurrentRound() === null ? 1 : $sectionEditorSubmission->getCurrentRound(),
				$sectionEditorSubmission->getReviewRevision()
			);
		}

		// Update copyeditor assignment
		if ($sectionEditorSubmission->getCopyedId()) {
			$copyeditorSubmission = &$this->copyeditorSubmissionDao->getCopyeditorSubmission($sectionEditorSubmission->getArticleId());
		} else {
			$copyeditorSubmission = &new CopyeditorSubmission();
		}

		// Only update the fields that an editor can modify.
		$copyeditorSubmission->setArticleId($sectionEditorSubmission->getArticleId());
		$copyeditorSubmission->setCopyeditorId($sectionEditorSubmission->getCopyeditorId());
				$copyeditorSubmission->setDateUnderway($sectionEditorSubmission->getCopyeditorDateUnderway());
		$copyeditorSubmission->setDateNotified($sectionEditorSubmission->getCopyeditorDateNotified());
		$copyeditorSubmission->setDateCompleted($sectionEditorSubmission->getCopyeditorDateCompleted());
		$copyeditorSubmission->setDateAcknowledged($sectionEditorSubmission->getCopyeditorDateAcknowledged());
		$copyeditorSubmission->setDateAuthorUnderway($sectionEditorSubmission->getCopyeditorDateAuthorUnderway());
		$copyeditorSubmission->setDateAuthorNotified($sectionEditorSubmission->getCopyeditorDateAuthorNotified());
		$copyeditorSubmission->setDateAuthorCompleted($sectionEditorSubmission->getCopyeditorDateAuthorCompleted());
		$copyeditorSubmission->setDateAuthorAcknowledged($sectionEditorSubmission->getCopyeditorDateAuthorAcknowledged());
		$copyeditorSubmission->setDateFinalUnderway($sectionEditorSubmission->getCopyeditorDateFinalUnderway());
		$copyeditorSubmission->setDateFinalNotified($sectionEditorSubmission->getCopyeditorDateFinalNotified());
		$copyeditorSubmission->setDateFinalCompleted($sectionEditorSubmission->getCopyeditorDateFinalCompleted());
		$copyeditorSubmission->setDateFinalAcknowledged($sectionEditorSubmission->getCopyeditorDateFinalAcknowledged());
		$copyeditorSubmission->setInitialRevision($sectionEditorSubmission->getCopyeditorInitialRevision());
		$copyeditorSubmission->setEditorAuthorRevision($sectionEditorSubmission->getCopyeditorEditorAuthorRevision());
		$copyeditorSubmission->setFinalRevision($sectionEditorSubmission->getCopyeditorFinalRevision());
		$copyeditorSubmission->setDateStatusModified($sectionEditorSubmission->getDateStatusModified());
		$copyeditorSubmission->setLastModified($sectionEditorSubmission->getLastModified());

		if ($copyeditorSubmission->getCopyedId() != null) {
			$this->copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
		} else {
			$this->copyeditorSubmissionDao->insertCopyeditorSubmission($copyeditorSubmission);
		}

		// update review assignments
		foreach ($sectionEditorSubmission->getReviewAssignments() as $roundReviewAssignments) {
			foreach ($roundReviewAssignments as $reviewAssignment) {
				if ($reviewAssignment->getReviewId() > 0) {
					$this->reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				} else {
					$this->reviewAssignmentDao->insertReviewAssignment($reviewAssignment);
				}
			}
		}

		// Remove deleted review assignments
		$removedReviewAssignments = $sectionEditorSubmission->getRemovedReviewAssignments();
		for ($i=0, $count=count($removedReviewAssignments); $i < $count; $i++) {
			$this->reviewAssignmentDao->deleteReviewAssignmentById($removedReviewAssignments[$i]);
		}

		// Update layout editing assignment
		$layoutAssignment =& $sectionEditorSubmission->getLayoutAssignment();
		if (isset($layoutAssignment)) {
			if ($layoutAssignment->getLayoutId() > 0) {
				$this->layoutAssignmentDao->updateLayoutAssignment($layoutAssignment);
			} else {
				$this->layoutAssignmentDao->insertLayoutAssignment($layoutAssignment);
			}
		}

		// Update article
		if ($sectionEditorSubmission->getArticleId()) {

			$article = &$this->articleDao->getArticle($sectionEditorSubmission->getArticleId());

			// Only update fields that can actually be edited.
			$article->setSectionId($sectionEditorSubmission->getSectionId());
			$article->setCurrentRound($sectionEditorSubmission->getCurrentRound());
			$article->setReviewFileId($sectionEditorSubmission->getReviewFileId());
			$article->setEditorFileId($sectionEditorSubmission->getEditorFileId());
			$article->setStatus($sectionEditorSubmission->getStatus());
			$article->setCopyeditFileId($sectionEditorSubmission->getCopyeditFileId());
			$article->setDateStatusModified($sectionEditorSubmission->getDateStatusModified());
			$article->setLastModified($sectionEditorSubmission->getLastModified());
			$article->setCommentsStatus($sectionEditorSubmission->getCommentsStatus());

			$this->articleDao->updateArticle($article);
		}

	}

	function createReviewRound($articleId, $round, $reviewRevision) {
		$this->update(
			'INSERT INTO review_rounds
				(article_id, round, review_revision)
				VALUES
				(?, ?, ?)',
			array($articleId, $round, $reviewRevision)
		);
	}

	/**
	 * Get all section editor submissions for a section editor.
	 * @param $sectionEditorId int
	 * @param $status boolean true if active, false if completed.
	 * @return array SectionEditorSubmission
	 */
	function &getSectionEditorSubmissions($sectionEditorId, $journalId, $status = true) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$sectionEditorSubmissions = array();

		$result = &$this->retrieve(
			'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				c.copyed_id,
				c.copyeditor_id,
				c.date_notified AS copyeditor_date_notified,
				c.date_underway AS copyeditor_date_underway,
				c.date_completed AS copyeditor_date_completed,
				c.date_acknowledged AS copyeditor_date_acknowledged,
				c.date_author_notified AS ce_date_author_notified,
				c.date_author_underway AS ce_date_author_underway,
				c.date_author_completed AS ce_date_author_completed,
				c.date_author_acknowledged AS ce_date_author_acknowledged,
				c.date_final_notified AS ce_date_final_notified,
				c.date_final_underway AS ce_date_final_underway,
				c.date_final_completed AS ce_date_final_completed,
				c.date_final_acknowledged AS ce_date_final_acknowledged,
				c.initial_revision AS copyeditor_initial_revision,
				c.editor_author_revision AS ce_editor_author_revision,
				c.final_revision AS copyeditor_final_revision,
				r2.review_revision
			FROM	articles a
				LEFT JOIN edit_assignments e ON (e.article_id = a.article_id)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id)
				LEFT JOIN review_rounds r2 ON (a.article_id = r2.article_id AND a.current_round = r2.round)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.journal_id = ?
				AND e.editor_id = ?
				AND a.status = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primarylocale,
				'abbrev',
				$locale,
				$journalId,
				$sectionEditorId,
				$status
			)
		);

		while (!$result->EOF) {
			$sectionEditorSubmissions[] = &$this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $sectionEditorSubmissions;
	}

	/**
	 * Retrieve unfiltered section editor submissions
	 */
	function &getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId, $sectionId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $status = true, $additionalWhereSql = '', $rangeInfo = null) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$params = array(
			'title', // Section title
			$primaryLocale,
			'title',
			$locale,
			'abbrev', // Section abbrev
			$primaryLocale,
			'abbrev',
			$locale,
			'title', // Article title
			$journalId,
			$sectionEditorId
		);

		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(atl.setting_value) = LOWER(?)';
				} else {
					$searchSql = ' AND LOWER(atl.setting_value) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				}
				$params[] = $search;
				break;
			case SUBMISSION_FIELD_AUTHOR:
				$first_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.last_name');
				$first_middle_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.middle_name', '\' \'', 'aa.last_name');
				$last_comma_first = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name', '\' \'', 'aa.middle_name');

				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(aa.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} else {
					$searchSql = " AND (LOWER(aa.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
			case SUBMISSION_FIELD_EDITOR:
				$first_last = $this->_dataSource->Concat('ed.first_name', '\' \'', 'ed.last_name');
				$first_middle_last = $this->_dataSource->Concat('ed.first_name', '\' \'', 'ed.middle_name', '\' \'', 'ed.last_name');
				$last_comma_first = $this->_dataSource->Concat('ed.last_name', '\', \'', 'ed.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('ed.last_name', '\', \'', 'ed.first_name', '\' \'', 'ed.middle_name');
				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(ed.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} else {
					$searchSql = " AND (LOWER(ed.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
		}

		if (!empty($dateFrom) || !empty($dateTo)) switch($dateField) {
			case SUBMISSION_FIELD_DATE_SUBMITTED:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND a.date_submitted >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND a.date_submitted <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND c.date_final_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND c.date_final_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND l.date_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND l.date_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND p.date_proofreader_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= 'AND p.date_proofreader_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
		}

		$sql = 'SELECT DISTINCT
				a.*,
				e.can_review AS can_review,
				e.can_edit AS can_edit,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				c.copyed_id,
				c.copyeditor_id,
				c.date_notified AS copyeditor_date_notified,
				c.date_underway AS copyeditor_date_underway,
				c.date_completed AS copyeditor_date_completed,
				c.date_acknowledged AS copyeditor_date_acknowledged,
				c.date_author_notified AS ce_date_author_notified,
				c.date_author_underway AS ce_date_author_underway,
				c.date_author_completed AS ce_date_author_completed,
				c.date_author_acknowledged AS ce_date_author_acknowledged,
				c.date_final_notified AS ce_date_final_notified,
				c.date_final_underway AS ce_date_final_underway,
				c.date_final_completed AS ce_date_final_completed,
				c.date_final_acknowledged AS ce_date_final_acknowledged,
				c.initial_revision AS copyeditor_initial_revision,
				c.editor_author_revision AS ce_editor_author_revision,
				c.final_revision AS copyeditor_final_revision,
				r2.review_revision
			FROM
				articles a
				INNER JOIN article_authors aa ON (aa.article_id = a.article_id)
				LEFT JOIN edit_assignments e ON (e.article_id = a.article_id)
				LEFT JOIN users ed ON (e.editor_id = ed.user_id)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id)
				LEFT JOIN users ce ON (c.copyeditor_id = ce.user_id)
				LEFT JOIN proof_assignments p ON (p.article_id = a.article_id)
				LEFT JOIN users pe ON (pe.user_id = p.proofreader_id)
				LEFT JOIN review_rounds r2 ON (a.article_id = r2.article_id and a.current_round = r2.round)
				LEFT JOIN layouted_assignments l ON (l.article_id = a.article_id) LEFT JOIN users le ON (le.user_id = l.editor_id)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
				LEFT JOIN article_settings atl ON (a.article_id = atl.article_id AND atl.setting_name = ?)
			WHERE	a.journal_id = ?
				AND e.editor_id = ?
				AND a.submission_progress = 0' . (!empty($additionalWhereSql)?" AND ($additionalWhereSql)":"");

		// "Active" submissions have a status of STATUS_QUEUED and
		// the layout editor has not yet been acknowledged.
		if ($status) $sql .= ' AND a.status = ' . STATUS_QUEUED;
		else $sql .= ' AND a.status <> ' . STATUS_QUEUED;

		if ($sectionId) {
			$params[] = $sectionId;
			$searchSql .= ' AND a.section_id = ?';
		}

		$result = &$this->retrieveRange($sql . ' ' . $searchSql . ' ORDER BY article_id ASC',
			$params,
			$rangeInfo
		);	

		return $result;	
	}

	/**
	 * Get all submissions in review for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getSectionEditorSubmissionsInReview($sectionEditorId, $journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId, $sectionId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true, 'e.can_review = 1');

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submission = &$this->_returnSectionEditorSubmissionFromRow($row);
			$articleId = $submission->getArticleId();

			// check if submission is still in review
			$inReview = true;
			$decisions = $submission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT) {
					$inReview = false;
				}
			}
			if ($inReview) $submissions[] =& $submission;

			unset($submission);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($submissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($submissions);
		}
		return $returner;

	}

	/**
	 * Get all submissions in editing for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getSectionEditorSubmissionsInEditing($sectionEditorId, $journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId, $sectionId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true, 'e.can_edit = 1');

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submission = &$this->_returnSectionEditorSubmissionFromRow($row);

			// check if submission is still in review
			$inReview = true;
			$decisions = $submission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT) {
					$inReview = false;
				}
			}
			if (!$inReview) $submissions[] =& $submission;

			unset($submission);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($submissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($submissions);
		}
		return $returner;
	}

	/**
	 * Get all submissions in archives for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getSectionEditorSubmissionsArchives($sectionEditorId, $journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		$result = $this->getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId, $sectionId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, false, '', $rangeInfo);

		while (!$result->EOF) {
			$submission = &$this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
			$submissions[] =& $submission;
			unset($submission);
			$result->MoveNext();
		}

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new VirtualArrayIterator($submissions, $result->MaxRecordCount(), $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($submissions);
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Function used for counting purposes for right nav bar
	 */
	function &getSectionEditorSubmissionsCount($sectionEditorId, $journalId) {

		$submissionsCount = array();
		for($i = 0; $i < 4; $i++) {
			$submissionsCount[$i] = 0;
		}

		$result = $this->getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$sectionEditorSubmission = &$this->_returnSectionEditorSubmissionFromRow($row);

			// check if submission is still in review
			$inReview = true;
			$decisions = $sectionEditorSubmission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT) {
					$inReview = false;
				}
			}

			if ($inReview) {
				if ($row['can_review']) {
					// in review submissions
					$submissionsCount[0] += 1;
				}
			} else {
				// in editing submissions
				if ($row['can_edit']) {
					$submissionsCount[1] += 1;
				}
			}
			unset($sectionEditorSubmission);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $submissionsCount;
	}

	//
	// Miscellaneous
	//

	/**
	 * Delete copyediting assignments by article.
	 * @param $articleId int
	 */
	function deleteDecisionsByArticle($articleId) {
		return $this->update(
			'DELETE FROM edit_decisions WHERE article_id = ?',
			$articleId
		);
	}

	/**
	 * Delete review rounds article.
	 * @param $articleId int
	 */
	function deleteReviewRoundsByArticle($articleId) {
		return $this->update(
			'DELETE FROM review_rounds WHERE article_id = ?',
			$articleId
		);
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

	/**
	 * Get the highest review round.
	 * @param $articleId int
	 * @return int
	 */
	function getMaxReviewRound($articleId) {
		$result = &$this->retrieve(
			'SELECT MAX(round) FROM review_rounds WHERE article_id = ?', $articleId
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}	

	/**
	 * Check if a review round exists for a specified article.
	 * @param $articleId int
	 * @param $round int
	 * @return boolean
	 */
	function reviewRoundExists($articleId, $round) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM review_rounds WHERE article_id = ? AND round = ?', array($articleId, $round)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a reviewer is assigned to a specified article.
	 * @param $articleId int
	 * @param $reviewerId int
	 * @return boolean
	 */
	function reviewerExists($articleId, $reviewerId, $round) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM review_assignments WHERE article_id = ? AND reviewer_id = ? AND round = ? AND cancelled = 0', array($articleId, $reviewerId, $round)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a list of all reviewers along with information about their current status with respect to an article's current round.
	 * @param $journalId int
	 * @param $articleId int
	 * @return DAOResultFactory containing matching Users
	 */
	function &getReviewersForArticle($journalId, $articleId, $round, $searchType = null, $search = null, $searchMatch = null, $rangeInfo = null) {
		$paramArray = array('interests', $articleId, $round, $journalId, RoleDAO::getRoleIdFromPath('reviewer'));
		$searchSql = '';

		if (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND LOWER(first_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND LOWER(last_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND LOWER(username) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND LOWER(email) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND LOWER(s.setting_value) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(last_name) LIKE LOWER(?) OR LOWER(username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}

		$result = &$this->retrieveRange(
			'SELECT DISTINCT
				u.*,
				a.review_id
			FROM	users u
				LEFT JOIN user_settings s ON (u.user_id = s.user_id AND s.setting_name = ?)
				LEFT JOIN roles r ON (r.user_id = u.user_id)
				LEFT JOIN review_assignments a ON (a.reviewer_id = u.user_id AND a.cancelled = 0 AND a.article_id = ? AND a.round = ?)
			WHERE	u.user_id = r.user_id AND
				r.journal_id = ? AND
				r.role_id = ? ' . $searchSql . '
			ORDER BY last_name, first_name',
			$paramArray, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnReviewerUserFromRow');
		return $returner;
	}

	function &_returnReviewerUserFromRow(&$row) { // FIXME
		$user = &$this->userDao->_returnUserFromRowWithData($row);
		$user->review_id = $row['review_id'];

		HookRegistry::call('SectionEditorSubmissionDAO::_returnReviewerUserFromRow', array(&$user, &$row));

		return $user;
	}

	/**
	 * Retrieve a list of all reviewers not assigned to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @return array matching Users
	 */
	function &getReviewersNotAssignedToArticle($journalId, $articleId) {
		$users = array();

		$result = &$this->retrieve(
			'SELECT	u.*
			FROM	users u
				LEFT JOIN roles r ON (r.user_id = u.user_id)
				LEFT JOIN review_assignments a ON (a.reviewer_id = u.user_id AND a.article_id = ?)
			WHERE	r.journal_id = ? AND
				r.role_id = ? AND
				a.article_id IS NULL
			ORDER BY last_name, first_name',
			array($articleId, $journalId, RoleDAO::getRoleIdFromPath('reviewer'))
		);

		while (!$result->EOF) {
			$users[] = &$this->userDao->_returnUserFromRowWithData($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $users;
	}

	/**
	 * Check if a copyeditor is assigned to a specified article.
	 * @param $articleId int
	 * @param $copyeditorId int
	 * @return boolean
	 */
	function copyeditorExists($articleId, $copyeditorId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM copyed_assignments WHERE article_id = ? AND copyeditor_id = ?', array($articleId, $copyeditorId)
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}

	/**
	 * Retrieve a list of all copyeditors not assigned to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @return array matching Users
	 */
	function &getCopyeditorsNotAssignedToArticle($journalId, $articleId, $searchType = null, $search = null, $searchMatch = null) {
		$users = array();

		$paramArray = array('interests', $articleId, $journalId, RoleDAO::getRoleIdFromPath('copyeditor'));
		$searchSql = '';

		if (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND LOWER(first_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND LOWER(last_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND LOWER(username) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND LOWER(email) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND LOWER(setting_value) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(last_name) LIKE LOWER(?) OR LOWER(username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}

		$result = &$this->retrieve(
			'SELECT	u.*
			FROM	users u
				LEFT JOIN user_settings s ON (u.user_id = s.user_id AND s.setting_name = ?)
				LEFT JOIN roles r ON (r.user_id = u.user_id)
				LEFT JOIN copyed_assignments a ON (a.copyeditor_id = u.user_id AND a.article_id = ?)
			WHERE	r.journal_id = ? AND
				r.role_id = ? AND
				a.article_id IS NULL
				' . $searchSql . '
			ORDER BY last_name, first_name',
			$paramArray
		);

		while (!$result->EOF) {
			$users[] = &$this->userDao->_returnUserFromRowWithData($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $users;
	}

	/**
	 * Get the assignment counts and last assigned date for all layout editors of the given journal.
	 * @return array
	 */
	function getLayoutEditorStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('SELECT la.editor_id AS editor_id, COUNT(la.article_id) AS complete FROM layouted_assignments la, articles a INNER JOIN proof_assignments p ON (p.article_id = a.article_id) WHERE la.article_id = a.article_id AND (la.date_completed IS NOT NULL AND p.date_layouteditor_completed IS NOT NULL) AND la.date_notified IS NOT NULL AND a.journal_id = ? GROUP BY la.editor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get counts of incomplete submissions
		$result = &$this->retrieve('SELECT la.editor_id AS editor_id, COUNT(la.article_id) AS incomplete FROM layouted_assignments la, articles a INNER JOIN proof_assignments p ON (p.article_id = a.article_id) WHERE la.article_id = a.article_id AND (la.date_completed IS NULL OR p.date_layouteditor_completed IS NULL) AND la.date_notified IS NOT NULL AND a.journal_id = ? GROUP BY la.editor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get last assignment date
		$result = &$this->retrieve('SELECT la.editor_id AS editor_id, MAX(la.date_notified) AS last_assigned FROM layouted_assignments la, articles a WHERE la.article_id=a.article_id AND a.journal_id=? GROUP BY la.editor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_assigned'] = $this->datetimeFromDB($row['last_assigned']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $statistics;
	}

	/**
	 * Get the last assigned and last completed dates for all reviewers of the given journal.
	 * @return array
	 */
	function getReviewerStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('SELECT ra.reviewer_id AS editor_id, MAX(ra.date_notified) AS last_notified FROM review_assignments ra, articles a WHERE ra.article_id = a.article_id AND a.journal_id = ? GROUP BY ra.reviewer_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_notified'] = $this->datetimeFromDB($row['last_notified']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get completion status
		$result =& $this->retrieve('SELECT r.reviewer_id, COUNT(*) AS incomplete FROM review_assignments r, articles a WHERE r.article_id = a.article_id AND r.date_notified IS NOT NULL AND r.date_completed IS NULL AND r.cancelled = 0 AND a.journal_id = ? GROUP BY r.reviewer_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();
			$statistics[$row['reviewer_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Calculate time taken for completed reviews
		$result = &$this->retrieve('SELECT r.reviewer_id, r.date_notified, r.date_completed FROM review_assignments r, articles a WHERE r.article_id = a.article_id AND r.date_notified IS NOT NULL AND r.date_completed IS NOT NULL AND r.declined = 0 AND a.journal_id = ?', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();

			$completed = strtotime($this->datetimeFromDB($row['date_completed']));
			$notified = strtotime($this->datetimeFromDB($row['date_notified']));
			if (isset($statistics[$row['reviewer_id']]['total_span'])) {
				$statistics[$row['reviewer_id']]['total_span'] += $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] += 1;
			} else {
				$statistics[$row['reviewer_id']]['total_span'] = $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] = 1;
			}

			// Calculate the average length of review in weeks.
			$statistics[$row['reviewer_id']]['average_span'] = (($statistics[$row['reviewer_id']]['total_span'] / $statistics[$row['reviewer_id']]['completed_review_count']) / 60 / 60 / 24 / 7);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $statistics;
	}

	/**
	 * Get the assignment counts and last assigned date for all copyeditors of the given journal.
	 * @return array
	 */
	function getCopyeditorStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('SELECT ca.copyeditor_id AS editor_id, COUNT(ca.article_id) AS complete FROM copyed_assignments ca, articles a WHERE ca.article_id = a.article_id AND ca.date_completed IS NOT NULL AND a.journal_id = ? GROUP BY ca.copyeditor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get counts of incomplete submissions
		$result = &$this->retrieve('SELECT ca.copyeditor_id AS editor_id, COUNT(ca.article_id) AS incomplete FROM copyed_assignments ca, articles a WHERE ca.article_id = a.article_id AND ca.date_completed IS NULL AND a.journal_id = ? GROUP BY ca.copyeditor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get last assignment date
		$result = &$this->retrieve('SELECT ca.copyeditor_id AS editor_id, MAX(ca.date_notified) AS last_assigned FROM copyed_assignments ca, articles a WHERE ca.article_id = a.article_id AND a.journal_id = ? GROUP BY ca.copyeditor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_assigned'] = $this->datetimeFromDB($row['last_assigned']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $statistics;
	}

	/**
	 * Get the assignment counts and last assigned date for all proofreaders of the given journal.
	 * @return array
	 */
	function getProofreaderStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('SELECT pa.proofreader_id AS editor_id, COUNT(pa.article_id) AS complete FROM proof_assignments pa, articles a WHERE pa.article_id = a.article_id AND pa.date_proofreader_completed IS NOT NULL AND a.journal_id = ? GROUP BY pa.proofreader_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get counts of incomplete submissions
		$result = &$this->retrieve('SELECT pa.proofreader_id AS editor_id, COUNT(pa.article_id) AS incomplete FROM proof_assignments pa, articles a WHERE pa.article_id = a.article_id AND pa.date_proofreader_completed IS NULL AND a.journal_id = ? GROUP BY pa.proofreader_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get last assignment date
		$result = &$this->retrieve('SELECT pa.proofreader_id AS editor_id, MAX(pa.date_proofreader_notified) AS last_assigned FROM proof_assignments pa, articles a WHERE pa.article_id = a.article_id AND a.journal_id = ? GROUP BY pa.proofreader_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_assigned'] = $this->datetimeFromDB($row['last_assigned']);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return $statistics;
	}
}

?>
