<?php

/**
 * CopyeditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for CopyeditorSubmission DAO.
 * Operations for retrieving and modifying CopyeditorSubmission objects.
 *
 * $Id$
 */

import('submission.copyeditor.CopyeditorSubmission');

class CopyeditorSubmissionDAO extends DAO {

	var $articleDao;
	var $authorDao;
	var $userDao;
	var $editAssignmentDao;
	var $layoutAssignmentDao;
	var $articleDao;
	var $articleFileDao;
	var $suppFileDao;
	var $galleyDao;
	var $articleCommentDao;
	var $proofAssignmentDao;

	/**
	 * Constructor.
	 */
	function CopyeditorSubmissionDAO() {
		parent::DAO();
		$this->articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$this->layoutAssignmentDao = DAORegistry::getDAO('LayoutAssignmentDAO');
		$this->articleDao = DAORegistry::getDAO('ArticleDAO');
		$this->articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		$this->articleCommentDao = DAORegistry::getDAO('ArticleCommentDAO');
		$this->proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$this->suppFileDao = DAORegistry::getDAO('SuppFileDAO');
		$this->galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
	}
	
	/**
	 * Retrieve a copyeditor submission by article ID.
	 * @param $articleId int
	 * @return CopyeditorSubmission
	 */
	function &getCopyeditorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, e.editor_id, c.*, s.abbrev as section_abbrev, s.title as section_title FROM articles a LEFT JOIN edit_assignments e on (a.article_id = e.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (c.article_id = a.article_id) WHERE a.article_id = ?',
			$articleId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnCopyeditorSubmissionFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}
	
	/**
	 * Internal function to return a CopyeditorSubmission object from a row.
	 * @param $row array
	 * @return CopyeditorSubmission
	 */
	function &_returnCopyeditorSubmissionFromRow(&$row) {
		$copyeditorSubmission = &new CopyeditorSubmission();

		// Article attributes
		$this->articleDao->_articleFromRow($copyeditorSubmission, $row);

		// Copyedit Assignment
		$copyeditorSubmission->setCopyedId($row['copyed_id']);
		$copyeditorSubmission->setCopyeditorId($row['copyeditor_id']);
		$copyeditorSubmission->setCopyeditor($this->userDao->getUser($row['copyeditor_id']), true);
		$copyeditorSubmission->setDateNotified($row['date_notified']);
		$copyeditorSubmission->setDateUnderway($row['date_underway']);
		$copyeditorSubmission->setDateCompleted($row['date_completed']);
		$copyeditorSubmission->setDateAcknowledged($row['date_acknowledged']);
		$copyeditorSubmission->setDateAuthorNotified($row['date_author_notified']);
		$copyeditorSubmission->setDateAuthorUnderway($row['date_author_underway']);
		$copyeditorSubmission->setDateAuthorCompleted($row['date_author_completed']);
		$copyeditorSubmission->setDateAuthorAcknowledged($row['date_author_acknowledged']);
		$copyeditorSubmission->setDateFinalNotified($row['date_final_notified']);
		$copyeditorSubmission->setDateFinalUnderway($row['date_final_underway']);
		$copyeditorSubmission->setDateFinalCompleted($row['date_final_completed']);
		$copyeditorSubmission->setDateFinalAcknowledged($row['date_final_acknowledged']);
		$copyeditorSubmission->setInitialRevision($row['initial_revision']);
		$copyeditorSubmission->setEditorAuthorRevision($row['editor_author_revision']);
		$copyeditorSubmission->setFinalRevision($row['final_revision']);

		// Editor Assignment
		$copyeditorSubmission->setEditor($this->editAssignmentDao->getEditAssignmentByArticleId($row['article_id']));
		
		// Comments
		$copyeditorSubmission->setMostRecentCopyeditComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_COPYEDIT, $row['article_id']));
		$copyeditorSubmission->setMostRecentLayoutComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_LAYOUT, $row['article_id']));
		
		// Files
		
		// Initial Copyedit File
		if ($row['initial_revision'] != null) {
			$copyeditorSubmission->setInitialCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['initial_revision']));
		}

		// Information for Layout table access
		$copyeditorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		$copyeditorSubmission->setGalleys($this->galleyDao->getGalleysByArticle($row['article_id']));

		// Editor / Author Copyedit File
		if ($row['editor_author_revision'] != null) {
			$copyeditorSubmission->setEditorAuthorCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['editor_author_revision']));
		}
		
		// Final Copyedit File
		if ($row['final_revision'] != null) {
			$copyeditorSubmission->setFinalCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['final_revision']));
		}

		$copyeditorSubmission->setLayoutAssignment($this->layoutAssignmentDao->getLayoutAssignmentByArticleId($row['article_id']));
		$copyeditorSubmission->setProofAssignment($this->proofAssignmentDao->getProofAssignmentByArticleId($row['article_id']));
		
		return $copyeditorSubmission;
	}
	
	/**
	 * Insert a new CopyeditorSubmission.
	 * @param $copyeditorSubmission CopyeditorSubmission
	 */	
	function insertCopyeditorSubmission(&$copyeditorSubmission) {
		$this->update(
			'INSERT INTO copyed_assignments
				(article_id, copyeditor_id, date_notified, date_underway, date_completed, date_acknowledged, date_author_notified, date_author_underway, date_author_completed, date_author_acknowledged, date_final_notified, date_final_underway, date_final_completed, date_final_acknowledged, initial_revision, editor_author_revision, final_revision)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$copyeditorSubmission->getArticleId(),
				$copyeditorSubmission->getCopyeditorId() === null ? 0 : $copyeditorSubmission->getCopyeditorId(),
				$copyeditorSubmission->getDateNotified(),
				$copyeditorSubmission->getDateUnderway(),
				$copyeditorSubmission->getDateCompleted(),
				$copyeditorSubmission->getDateAcknowledged(),
				$copyeditorSubmission->getDateAuthorNotified(),
				$copyeditorSubmission->getDateAuthorUnderway(),
				$copyeditorSubmission->getDateAuthorCompleted(),
				$copyeditorSubmission->getDateAuthorAcknowledged(),
				$copyeditorSubmission->getDateFinalNotified(),
				$copyeditorSubmission->getDateFinalUnderway(),
				$copyeditorSubmission->getDateFinalCompleted(),
				$copyeditorSubmission->getDateFinalAcknowledged(),
				$copyeditorSubmission->getInitialRevision(),
				$copyeditorSubmission->getEditorAuthorRevision(),
				$copyeditorSubmission->getFinalRevision()
			)
		);
		
		$copyeditorSubmission->setCopyedId($this->getInsertCopyedId());
		return $copyeditorSubmission->getCopyedId();
	}

	/**
	 * Update an existing copyeditor submission.
	 * @param $copyeditorSubmission CopyeditorSubmission
	 */
	function updateCopyeditorSubmission(&$copyeditorSubmission) {
		$this->update(
			'UPDATE copyed_assignments
				SET
					article_id = ?,
					copyeditor_id = ?,
					date_notified = ?,
					date_underway = ?,
					date_completed = ?,
					date_acknowledged = ?,
					date_author_notified = ?,
					date_author_underway = ?,
					date_author_completed = ?,
					date_author_acknowledged = ?,
					date_final_notified = ?,
					date_final_underway = ?,
					date_final_completed = ?,
					date_final_acknowledged = ?,
					initial_revision = ?,
					editor_author_revision = ?,
					final_revision = ?
				WHERE copyed_id = ?',
			array(
				$copyeditorSubmission->getArticleId(),
				$copyeditorSubmission->getCopyeditorId() === null ? 0 : $copyeditorSubmission->getCopyeditorId(),
				$copyeditorSubmission->getDateNotified(),
				$copyeditorSubmission->getDateUnderway(),
				$copyeditorSubmission->getDateCompleted(),
				$copyeditorSubmission->getDateAcknowledged(),
				$copyeditorSubmission->getDateAuthorNotified(),
				$copyeditorSubmission->getDateAuthorUnderway(),
				$copyeditorSubmission->getDateAuthorCompleted(),
				$copyeditorSubmission->getDateAuthorAcknowledged(),
				$copyeditorSubmission->getDateFinalNotified(),
				$copyeditorSubmission->getDateFinalUnderway(),
				$copyeditorSubmission->getDateFinalCompleted(),
				$copyeditorSubmission->getDateFinalAcknowledged(),
				$copyeditorSubmission->getInitialRevision(),
				$copyeditorSubmission->getEditorAuthorRevision(),
				$copyeditorSubmission->getFinalRevision(),
				$copyeditorSubmission->getCopyedId()
			)
		);
	}
	
	/**
	 * Get all submissions for a copyeditor of a journal.
	 * @param $copyeditorId int
	 * @param $journalId int
	 * @param $searchField int SUBMISSION_FIELD_... constant
	 * @param $searchMatch String 'is' or 'contains'
	 * @param $search String Search string
	 * @param $dateField int SUBMISSION_FIELD_DATE_... constant
	 * @param $dateFrom int Search from timestamp
	 * @param $dateTo int Search to timestamp
	 * @return array CopyeditorSubmissions
	 */
	function &getCopyeditorSubmissionsByCopyeditorId($copyeditorId, $journalId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $active = true, $rangeInfo = null) {
		$params = array($journalId, $copyeditorId);

		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND (a.title = ? OR a.title_alt1 = ? OR a.title_alt2 = ?)';
				} else {
					$searchSql = ' AND (LOWER(a.title) LIKE LOWER(?) OR LOWER(a.title_alt1) LIKE LOWER(?) OR LOWER(a.title_alt2) LIKE LOWER(?))';
					$search = '%' . $search . '%';
				}
				$params[] = $params[] = $params[] = $search;
				break;
			case SUBMISSION_FIELD_AUTHOR:
				$first_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.last_name');
				$first_middle_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.middle_name', '\' \'', 'aa.last_name');
				$last_comma_first = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name', '\' \'', 'aa.middle_name');

				if ($searchMatch === 'is') {
					$searchSql = " AND (aa.last_name = ? OR $first_last = ? OR $first_middle_last = ? OR $last_comma_first = ? OR $last_comma_first_middle = ?)";
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
					$searchSql = " AND (ed.last_name = ? OR $first_last = ? OR $first_middle_last = ? OR $last_comma_first = ? OR $last_comma_first_middle = ?)";
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
					$searchSql .= ' AND a.date_submitted >= ?';
					$params[] = $dateFrom;
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND a.date_submitted <= ?';
					$params[] = $dateTo;
				}
				break;
			case SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND c.date_final_completed >= ?';
					$params[] = $dateFrom;
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND c.date_final_completed <= ?';
					$params[] = $dateTo;
				}
				break;
			case SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND l.date_completed >= ?';
					$params[] = $dateFrom;
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND l.date_completed <= ?';
					$params[] = $dateTo;
				}
				break;
			case SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND p.date_proofreader_completed >= ?';
					$params[] = $dateFrom;
				}
				if (!empty($dateTo)) {
					$searchSql .= 'AND p.date_proofreader_completed <= ?';
					$params[] = $dateTo;
				}
				break;
		}

		$sql = 'SELECT DISTINCT
				a.*,
				c.*,
				s.abbrev as section_abbrev,
				s.title as section_title
			FROM
				articles a
			JOIN article_authors aa ON (aa.article_id = a.article_id)
			LEFT JOIN sections s ON (s.section_id = a.section_id)
			LEFT JOIN copyed_assignments c ON (c.article_id = a.article_id)
			LEFT JOIN edit_assignments e ON (e.article_id = a.article_id)
			LEFT JOIN users ed ON (e.editor_id = ed.user_id)
			LEFT JOIN layouted_assignments l ON (l.article_id = a.article_id)
			LEFT JOIN proof_assignments p ON (p.article_id = a.article_id)
			WHERE
				a.journal_id = ? AND
				c.copyeditor_id = ? AND
				c.date_notified IS NOT NULL AND
				c.date_final_completed ';
		$sql .= $active ? 'IS NULL' : 'IS NOT NULL';

		$result = &$this->retrieveRange($sql . ' ' . $searchSql . ' ORDER BY a.article_id ASC', $params, $rangeInfo);

		$returner = &new DAOResultFactory($result, $this, '_returnCopyeditorSubmissionFromRow');
		return $returner;
	}
	
	/**
	 * Get the ID of the last inserted copyeditor assignment.
	 * @return int
	 */
	function getInsertCopyedId() {
		return $this->getInsertId('copyed_assignments', 'copyed_id');
	}

	/**
	 * Get count of active and complete assignments
	 * @param copyeditorId int
	 * @param journalId int
	 */
	function getSubmissionsCount($copyeditorId, $journalId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = 'SELECT c.date_final_completed FROM articles a LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (c.article_id = a.article_id) WHERE a.journal_id = ? AND c.copyeditor_id = ? AND c.date_notified IS NOT NULL';

		$result = &$this->retrieve($sql, array($journalId, $copyeditorId));

		while (!$result->EOF) {
			if ($result->fields['date_final_completed'] == null) {
				$submissionsCount[0] += 1;
			} else {
				$submissionsCount[1] += 1;
			}
			$result->moveNext();
		}

		return $submissionsCount;
	}
	
}

?>
