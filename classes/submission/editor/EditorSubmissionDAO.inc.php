<?php

/**
 * @file classes/editor/EditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditorSubmissionDAO
 * @ingroup submission
 * @see EditorSubmission
 *
 * @brief Operations for retrieving and modifying EditorSubmission objects.
 */

import('classes.submission.editor.EditorSubmission');

// Bring in editor decision constants
import('classes.submission.common.Action');
import('classes.submission.author.AuthorSubmission');

class EditorSubmissionDAO extends DAO {
	var $articleDao;
	var $authorDao;
	var $userDao;
	var $editAssignmentDao;

	/**
	 * Constructor.
	 */
	function EditorSubmissionDAO() {
		parent::DAO();
		$this->articleDao =& DAORegistry::getDAO('ArticleDAO');
		$this->articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
	}

	/**
	 * Retrieve an editor submission by article ID.
	 * @param $articleId int
	 * @return EditorSubmission
	 */
	function &getEditorSubmission($articleId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$result =& $this->retrieve(
			'SELECT
				a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
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
			$returner =& $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return an EditorSubmission object from a row.
	 * @param $row array
	 * @return EditorSubmission
	 */
	function &_returnEditorSubmissionFromRow(&$row) {
		$editorSubmission = new EditorSubmission();

		// Article attributes
		$this->articleDao->_articleFromRow($editorSubmission, $row);

		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByArticleId($row['article_id']);
		$editorSubmission->setEditAssignments($editAssignments->toArray());

		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$editorSubmission->setDecisions($this->getEditorDecisions($row['article_id'], $i), $i);
		}

		for ($i = 1; $i <= $row['current_round']; $i++) {
			$editorSubmission->setEditorFileRevisions($this->articleFileDao->getArticleFileRevisions($row['editor_file_id'], $i), $i);
			$editorSubmission->setAuthorFileRevisions($this->articleFileDao->getArticleFileRevisions($row['revised_file_id'], $i), $i);
		}

		// Review Rounds
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		for ($i = 1; $i <= $editorSubmission->getCurrentRound(); $i++) {
			$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($editorSubmission->getId(), $i);
			if (!empty($reviewAssignments)) {
				$editorSubmission->setReviewAssignments($reviewAssignments, $i);
			}
			unset($reviewAssignments);
		}

		HookRegistry::call('EditorSubmissionDAO::_returnEditorSubmissionFromRow', array(&$editorSubmission, &$row));

		return $editorSubmission;
	}

	/**
	 * Insert a new EditorSubmission.
	 * @param $editorSubmission EditorSubmission
	 */
	function insertEditorSubmission(&$editorSubmission) {
		$this->update(
			sprintf('INSERT INTO edit_assignments
				(article_id, editor_id, date_notified, date_completed, date_acknowledged)
				VALUES
				(?, ?, %s, %s, %s)',
				$this->datetimeToDB($editorSubmission->getDateNotified()), $this->datetimeToDB($editorSubmission->getDateCompleted()), $this->datetimeToDB($editorSubmission->getDateAcknowledged())),
			array(
				$editorSubmission->getId(),
				$editorSubmission->getEditorId()
			)
		);

		$editorSubmission->setEditId($this->getInsertEditId());

		// Insert review assignments.
		$reviewAssignments =& $editorSubmission->getReviewAssignments();
		for ($i=0, $count=count($reviewAssignments); $i < $count; $i++) {
			$reviewAssignments[$i]->setArticleId($editorSubmission->getId());
			$this->reviewAssignmentDao->insertReviewAssignment($reviewAssignments[$i]);
		}

		return $editorSubmission->getEditId();
	}

	/**
	 * Update an existing article.
	 * @param $article Article
	 */
	function updateEditorSubmission(&$editorSubmission) {
		// update edit assignments
		$editAssignments = $editorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getEditId() > 0) {
				$this->editAssignmentDao->updateEditAssignment($editAssignment);
			} else {
				$this->editAssignmentDao->insertEditAssignment($editAssignment);
			}
		}
	}

	/**
	 * Get all unfiltered submissions for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $editorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $additionalWhereSql String additional SQL "where" clause info
	 * @param $rangeInfo object
	 * @return array result
	 */
	function &_getUnfilteredEditorSubmissions($journalId, $sectionId = 0, $editorId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $additionalWhereSql, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$params = array(
			ASSOC_TYPE_ARTICLE,
			'SIGNOFF_COPYEDITING_FINAL',
			ASSOC_TYPE_ARTICLE,
			'SIGNOFF_PROOFREADING_PROOFREADER',
			ASSOC_TYPE_ARTICLE,
			'SIGNOFF_LAYOUT',
			'title', // Section title
			$primaryLocale,
			'title',
			$locale,
			'abbrev', // Section abbrev
			$primaryLocale,
			'abbrev',
			$locale,
			'cleanTitle', // Article title
			'cleanTitle',
			$locale,
			$journalId
		);
		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_ID:
				$params[] = (int) $search;
				$searchSql = ' AND a.article_id = ?';
				break;
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(COALESCE(atl.setting_value, atpl.setting_value)) = LOWER(?)';
				} elseif ($searchMatch === 'contains') {
					$searchSql = ' AND LOWER(COALESCE(atl.setting_value, atpl.setting_value)) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = ' AND LOWER(COALESCE(atl.setting_value, atpl.setting_value)) LIKE LOWER(?)';
					$search = $search . '%';
				}
				$params[] = $search;
				break;
			case SUBMISSION_FIELD_AUTHOR:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'aa.', $params);
				break;
			case SUBMISSION_FIELD_EDITOR:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'ed.', $params);
				break;
			case SUBMISSION_FIELD_REVIEWER:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 're.', $params);
				break;
			case SUBMISSION_FIELD_COPYEDITOR:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'ce.', $params);
				break;
			case SUBMISSION_FIELD_LAYOUTEDITOR:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'le.', $params);
				break;
			case SUBMISSION_FIELD_PROOFREADER:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'pe.', $params);
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
					$searchSql .= ' AND sfc.date_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND scf.date_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND sle.date_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND sle.date_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND spr.date_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND spr.date_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
		}

		$sql = 'SELECT DISTINCT
				a.*,
				scf.date_completed as copyedit_completed,
				spr.date_completed as proofread_completed,
				sle.date_completed as layout_completed,
				SUBSTRING(COALESCE(atl.setting_value, atpl.setting_value) FROM 1 FOR 255) AS submission_title,
				aap.last_name AS author_name,
				SUBSTRING(COALESCE(stl.setting_value, stpl.setting_value) FROM 1 FOR 255) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN authors aa ON (aa.submission_id = a.article_id)
				LEFT JOIN authors aap ON (aap.submission_id = a.article_id AND aap.primary_contact = 1)
				LEFT JOIN sections s ON (s.section_id = a.section_id)
				LEFT JOIN edit_assignments e ON (e.article_id = a.article_id)
				LEFT JOIN users ed ON (e.editor_id = ed.user_id)
				LEFT JOIN signoffs scf ON (a.article_id = scf.assoc_id AND scf.assoc_type = ? AND scf.symbolic = ?)
				LEFT JOIN users ce ON (scf.user_id = ce.user_id)
				LEFT JOIN signoffs spr ON (a.article_id = spr.assoc_id AND spr.assoc_type = ? AND spr.symbolic = ?)
				LEFT JOIN users pe ON (pe.user_id = spr.user_id)
				LEFT JOIN signoffs sle ON (a.article_id = sle.assoc_id AND sle.assoc_type = ? AND sle.symbolic = ?)
				LEFT JOIN users le ON (le.user_id = sle.user_id)
				LEFT JOIN review_assignments r ON (r.submission_id = a.article_id)
				LEFT JOIN users re ON (re.user_id = r.reviewer_id AND cancelled = 0)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
				LEFT JOIN article_settings atpl ON (a.article_id = atpl.article_id AND atpl.setting_name = ? AND atpl.locale = a.locale)
				LEFT JOIN article_settings atl ON (a.article_id = atl.article_id AND atl.setting_name = ? AND atl.locale = ?)
				LEFT JOIN edit_assignments ea ON (a.article_id = ea.article_id)
				LEFT JOIN edit_assignments ea2 ON (a.article_id = ea2.article_id AND ea.edit_id < ea2.edit_id)
				LEFT JOIN edit_decisions edec ON (a.article_id = edec.article_id)
				LEFT JOIN edit_decisions edec2 ON (a.article_id = edec2.article_id AND edec.edit_decision_id < edec2.edit_decision_id)
			WHERE	edec2.edit_decision_id IS NULL
				AND ea2.edit_id IS NULL
				AND a.journal_id = ?
				AND a.submission_progress = 0' .
				(!empty($additionalWhereSql)?" AND ($additionalWhereSql)":'');

		if ($sectionId) {
			$searchSql .= ' AND a.section_id = ?';
			$params[] = $sectionId;
		}

		if ($editorId) {
			$searchSql .= ' AND e.editor_id = ?';
			$params[] = $editorId;
		}

		$result =& $this->retrieveRange(
			$sql . ' ' . $searchSql . ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : ''),
			count($params)===1?array_shift($params):$params,
			$rangeInfo
		);
		return $result;
	}

	/**
	 * FIXME Move this into somewhere common (SubmissionDAO?) as this is used in several classes.
	 */
	function _generateUserNameSearchSQL($search, $searchMatch, $prefix, &$params) {
		$first_last = $this->concat($prefix.'first_name', '\' \'', $prefix.'last_name');
		$first_middle_last = $this->concat($prefix.'first_name', '\' \'', $prefix.'middle_name', '\' \'', $prefix.'last_name');
		$last_comma_first = $this->concat($prefix.'last_name', '\', \'', $prefix.'first_name');
		$last_comma_first_middle = $this->concat($prefix.'last_name', '\', \'', $prefix.'first_name', '\' \'', $prefix.'middle_name');
		if ($searchMatch === 'is') {
			$searchSql = " AND (LOWER({$prefix}last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
		} elseif ($searchMatch === 'contains') {
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$search = '%' . $search . '%';
		} else { // $searchMatch === 'startsWith'
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$search = $search . '%';
		}
		$params[] = $params[] = $params[] = $params[] = $params[] = $search;
		return $searchSql;
	}

	/**
	 * Get all submissions unassigned for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $editorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissionsUnassigned($journalId, $sectionId, $editorId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result =& $this->_getUnfilteredEditorSubmissions(
			$journalId, $sectionId, $editorId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'a.status = ' . STATUS_QUEUED . ' AND ea.edit_id IS NULL',
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnEditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all submissions in review for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $editorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissionsInReview($journalId, $sectionId, $editorId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result =& $this->_getUnfilteredEditorSubmissions(
			$journalId, $sectionId, $editorId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'a.status = ' . STATUS_QUEUED . ' AND ea.edit_id IS NOT NULL AND (edec.decision IS NULL OR edec.decision <> ' . SUBMISSION_EDITOR_DECISION_ACCEPT . ')',
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnEditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all submissions in editing for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $editorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissionsInEditing($journalId, $sectionId, $editorId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result =& $this->_getUnfilteredEditorSubmissions(
			$journalId, $sectionId, $editorId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'a.status = ' . STATUS_QUEUED . ' AND ea.edit_id IS NOT NULL AND edec.decision = ' . SUBMISSION_EDITOR_DECISION_ACCEPT,
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnEditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all submissions archived for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $editorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissionsArchives($journalId, $sectionId, $editorId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result =& $this->_getUnfilteredEditorSubmissions(
			$journalId, $sectionId, $editorId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'a.status <> ' . STATUS_QUEUED,
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnEditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all submissions for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $editorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissions($journalId, $sectionId, $editorId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result =& $this->_getUnfilteredEditorSubmissions(
			$journalId, $sectionId, $editorId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			null,
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnEditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Function used for counting purposes for right nav bar
	 */
	function &getEditorSubmissionsCount($journalId) {
		$submissionsCount = array();
		for($i = 0; $i < 3; $i++) {
			$submissionsCount[$i] = 0;
		}

		// Fetch a count of unassigned submissions.
		// "e2" and "e" are used to fetch only a single assignment
		// if several exist.
		$result =& $this->retrieve(
			'SELECT	COUNT(*) AS unassigned_count
			FROM	articles a
				LEFT JOIN edit_assignments e ON (a.article_id = e.article_id)
				LEFT JOIN edit_assignments e2 ON (a.article_id = e2.article_id AND e.edit_id < e2.edit_id)
			WHERE	a.journal_id = ?
				AND a.submission_progress = 0
				AND a.status = ' . STATUS_QUEUED . '
				AND e2.edit_id IS NULL
				AND e.edit_id IS NULL',
			array((int) $journalId)
		);
		$submissionsCount[0] = $result->Fields('unassigned_count');
		$result->Close();

		// Fetch a count of submissions in review.
		// "e2" and "e" are used to fetch only a single assignment
		// if several exist.
		// "d2" and "d" are used to fetch the single most recent
		// editor decision.
		$result =& $this->retrieve(
			'SELECT	COUNT(*) AS review_count
			FROM	articles a
				LEFT JOIN edit_assignments e ON (a.article_id = e.article_id)
				LEFT JOIN edit_assignments e2 ON (a.article_id = e2.article_id AND e.edit_id < e2.edit_id)
				LEFT JOIN edit_decisions d ON (a.article_id = d.article_id)
				LEFT JOIN edit_decisions d2 ON (a.article_id = d2.article_id AND d.edit_decision_id < d2.edit_decision_id)
			WHERE	a.journal_id = ?
				AND a.submission_progress = 0
				AND a.status = ' . STATUS_QUEUED . '
				AND e2.edit_id IS NULL
				AND e.edit_id IS NOT NULL
				AND d2.edit_decision_id IS NULL
				AND (d.decision IS NULL OR d.decision <> ' . SUBMISSION_EDITOR_DECISION_ACCEPT . ')',
			array((int) $journalId)
		);
		$submissionsCount[1] = $result->Fields('review_count');
		$result->Close();

		// Fetch a count of submissions in editing.
		// "e2" and "e" are used to fetch only a single assignment
		// if several exist.
		// "d2" and "d" are used to fetch the single most recent
		// editor decision.
		$result =& $this->retrieve(
			'SELECT	COUNT(*) AS editing_count
			FROM	articles a
				LEFT JOIN edit_assignments e ON (a.article_id = e.article_id)
				LEFT JOIN edit_assignments e2 ON (a.article_id = e2.article_id AND e.edit_id < e2.edit_id)
				LEFT JOIN edit_decisions d ON (a.article_id = d.article_id)
				LEFT JOIN edit_decisions d2 ON (a.article_id = d2.article_id AND d.edit_decision_id < d2.edit_decision_id)
			WHERE	a.journal_id = ?
				AND a.submission_progress = 0
				AND a.status = ' . STATUS_QUEUED . '
				AND e2.edit_id IS NULL
				AND e.edit_id IS NOT NULL
				AND d2.edit_decision_id IS NULL
				AND d.decision = ' . SUBMISSION_EDITOR_DECISION_ACCEPT,
			array((int) $journalId)
		);
		$submissionsCount[2] = $result->Fields('editing_count');
		$result->Close();

		return $submissionsCount;
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
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? ORDER BY edit_decision_id ASC', $articleId
			);
		} else {
			$result =& $this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? AND round = ? ORDER BY edit_decision_id ASC',
				array($articleId, $round)
			);
		}

		while (!$result->EOF) {
			$decisions[] = array('editDecisionId' => $result->fields[0], 'editorId' => $result->fields[1], 'decision' => $result->fields[2], 'dateDecided' => $this->datetimeFromDB($result->fields[3]));
			$result->moveNext();
		}
		$result->Close();
		unset($result);

		return $decisions;
	}

	/**
	 * Get the editor decisions for an editor.
	 * @param $userId int
	 */
	function transferEditorDecisions($oldUserId, $newUserId) {
		$this->update(
			'UPDATE edit_decisions SET editor_id = ? WHERE editor_id = ?',
			array($newUserId, $oldUserId)
		);
	}

	/**
	 * Retrieve a list of all users in the specified role not assigned as editors to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @param $roleId int
	 * @return DAOResultFactory containing matching Users
	 */
	function &getUsersNotAssignedToArticle($journalId, $articleId, $roleId, $searchType=null, $search=null, $searchMatch=null, $rangeInfo = null) {
		$users = array();

		$paramArray = array('interest', $articleId, $journalId, $roleId);
		$searchSql = '';

		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 'cves.setting_value'
		);

		if (!empty($search) && isset($searchTypeMap[$searchType])) {
			$fieldName = $searchTypeMap[$searchType];
			switch ($searchMatch) {
				case 'is':
					$searchSql = "AND LOWER($fieldName) = LOWER(?)";
					$paramArray[] = $search;
					break;
				case 'contains':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = '%' . $search . '%';
					break;
				case 'startsWith':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = $search . '%';
					break;
			}
		} elseif (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(u.last_name) LIKE LOWER(?) OR LOWER(u.username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}

		$result =& $this->retrieveRange(
			'SELECT DISTINCT
				u.*
			FROM	users u
				LEFT JOIN controlled_vocabs cv ON (cv.symbolic = ?)
				LEFT JOIN user_interests ui ON (ui.user_id = u.user_id)
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id AND ui.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)
				LEFT JOIN roles r ON (r.user_id = u.user_id)
				LEFT JOIN edit_assignments e ON (e.editor_id = u.user_id AND e.article_id = ?)
			WHERE	r.journal_id = ? AND
				r.role_id = ? AND
				(e.article_id IS NULL) ' . $searchSql . '
			ORDER BY last_name, first_name',
			$paramArray, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted editor assignment.
	 * @return int
	 */
	function getInsertEditId() {
		return $this->getInsertId('edit_assignments', 'edit_id');
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	function getSortMapping($heading) {
		switch ($heading) {
			case 'id': return 'a.article_id';
			case 'submitDate': return 'a.date_submitted';
			case 'section': return 'section_abbrev';
			case 'authors': return 'author_name';
			case 'title': return 'submission_title';
			case 'active': return 'a.submission_progress';
			case 'subCopyedit': return 'copyedit_completed';
			case 'subLayout': return 'layout_completed';
			case 'subProof': return 'proofread_completed';
			case 'status': return 'a.status';
			default: return null;
		}
	}
}

?>
