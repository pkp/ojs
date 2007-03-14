<?php

/**
 * EditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for EditorSubmission DAO.
 * Operations for retrieving and modifying EditorSubmission objects.
 *
 * $Id$
 */

import('submission.editor.EditorSubmission');
import('submission.author.AuthorSubmission'); // Bring in editor decision constants

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
		$this->articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->authorDao = &DAORegistry::getDAO('AuthorDAO');
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
	}
	
	/**
	 * Retrieve an editor submission by article ID.
	 * @param $articleId int
	 * @return EditorSubmission
	 */
	function &getEditorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2 FROM articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.article_id = ?', $articleId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
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
		$editorSubmission = &new EditorSubmission();

		// Article attributes
		$this->articleDao->_articleFromRow($editorSubmission, $row);
		
		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByArticleId($row['article_id']);
		$editorSubmission->setEditAssignments($editAssignments->toArray());
		
		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$editorSubmission->setDecisions($this->getEditorDecisions($row['article_id'], $i), $i);
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
				$editorSubmission->getArticleId(),
				$editorSubmission->getEditorId()
			)
		);
		
		$editorSubmission->setEditId($this->getInsertEditId());
		
		// Insert review assignments.
		$reviewAssignments = &$editorSubmission->getReviewAssignments();
		for ($i=0, $count=count($reviewAssignments); $i < $count; $i++) {
			$reviewAssignments[$i]->setArticleId($editorSubmission->getArticleId());
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
	 * Get all submissions for a journal.
	 * @param $journalId int
	 * @param $status boolean true if queued, false if archived.
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissions($journalId, $status = true, $sectionId = 0, $rangeInfo = null) {
		if (!$sectionId) {
			$result = &$this->retrieveRange(
					'SELECT a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2 from articles a LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ? AND a.status = ? ORDER BY article_id ASC',
					array($journalId, $status),
					$rangeInfo
			);
		} else {
			$result = &$this->retrieveRange(
					'SELECT a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2 from articles a LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ? AND a.status = ? AND a.section_id = ? ORDER BY article_id ASC',
					array($journalId, $status, $sectionId),
					$rangeInfo
			);	
		}
		$returner = &new DAOResultFactory($result, $this, '_returnEditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all unfiltered submissions for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $status boolean whether to return active or not
	 * @param $rangeInfo object
	 * @return array result
	 */
	function &getUnfilteredEditorSubmissions($journalId, $sectionId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $status = true, $rangeInfo = null) {
		$params = array($journalId);
		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND (LOWER(a.title) = LOWER(?) OR LOWER(a.title_alt1) = LOWER(?) OR LOWER(a.title_alt2) = LOWER(?))';
				} else {
					$searchSql = ' AND (LOWER(a.title) LIKE LOWER(?) OR LOWER(a.title_alt1) LIKE LOWER(?) OR LOWER(a.title_alt2) LIKE LOWER(?))';
					$search = '%' . $search . '%';
				}
				$params[] = $params[] = $params[] = $search;
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
					$searchSql .= ' AND p.date_proofreader_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
		}

		$sql = 'SELECT DISTINCT
				a.*,
				s.title AS section_title,
				s.title_alt1 AS section_title_alt1,
				s.title_alt2 AS section_title_alt2,
				s.abbrev AS section_abbrev,
				s.abbrev_alt1 AS section_abbrev_alt1,
				s.abbrev_alt2 AS section_abbrev_alt2
			FROM
				articles a
			INNER JOIN article_authors aa ON (aa.article_id = a.article_id)
			LEFT JOIN sections s ON (s.section_id = a.section_id)
			LEFT JOIN edit_assignments e ON (e.article_id = a.article_id)
			LEFT JOIN users ed ON (e.editor_id = ed.user_id)
			LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id)
			LEFT JOIN users ce ON (c.copyeditor_id = ce.user_id)
			LEFT JOIN proof_assignments p ON (p.article_id = a.article_id)
			LEFT JOIN users pe ON (pe.user_id = p.proofreader_id)
			LEFT JOIN layouted_assignments l ON (l.article_id = a.article_id)
			LEFT JOIN users le ON (le.user_id = l.editor_id)
			LEFT JOIN review_assignments r ON (r.article_id = a.article_id)
			LEFT JOIN users re ON (re.user_id = r.reviewer_id AND cancelled = 0)
			WHERE
				a.journal_id = ? AND a.submission_progress = 0';

		// "Active" submissions have a status of STATUS_QUEUED and
		// the layout editor has not yet been acknowledged.
		if ($status === true) $sql .= ' AND (a.status = ' . STATUS_QUEUED . ' OR (a.status = ' . STATUS_PUBLISHED . ' AND p.date_layouteditor_acknowledged IS NULL))';
		else $sql .= ' AND ((a.status <> ' . STATUS_QUEUED . ' AND a.status <> ' . STATUS_PUBLISHED . ') OR (a.status = ' . STATUS_PUBLISHED . ' AND p.date_layouteditor_acknowledged IS NOT NULL))';

		if ($sectionId) {
			$searchSql .= ' AND a.section_id = ?';
			$params[] = $sectionId;
		}

		$result = &$this->retrieveRange(
			$sql . ' ' . $searchSql . ' ORDER BY article_id ASC',
			count($params)===1?array_shift($params):$params,
			$rangeInfo
		);
		return $result;
	}
	
	/**
	 * FIXME Move this into somewhere common (SubmissionDAO?) as this is used in several classes.
	 */
	function _generateUserNameSearchSQL($search, $searchMatch, $prefix, &$params) {
		$first_last = $this->_dataSource->Concat($prefix.'first_name', '\' \'', $prefix.'last_name');
		$first_middle_last = $this->_dataSource->Concat($prefix.'first_name', '\' \'', $prefix.'middle_name', '\' \'', $prefix.'last_name');
		$last_comma_first = $this->_dataSource->Concat($prefix.'last_name', '\', \'', $prefix.'first_name');
		$last_comma_first_middle = $this->_dataSource->Concat($prefix.'last_name', '\', \'', $prefix.'first_name', '\' \'', $prefix.'middle_name');
		if ($searchMatch === 'is') {
			$searchSql = " AND (LOWER({$prefix}last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
		} else {
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$search = '%' . $search . '%';
		}
		$params[] = $params[] = $params[] = $params[] = $params[] = $search;
		return $searchSql;
	}

	/**
	 * Helper function to retrieve copyed assignment
	 * @param articleId int
	 * @return result array
	 */
	function &getCopyedAssignment($articleId) {
		$result = &$this->retrieve(
				'SELECT * from copyed_assignments where article_id = ? ', $articleId
		);
		return $result;
	}

	/**
	 * Get all submissions unassigned for a journal.
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
	function &getEditorSubmissionsUnassigned($journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$editorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredEditorSubmissions($journalId, $sectionId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		while (!$result->EOF) {
			$editorSubmission = &$this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));

			// used to check if editor exists for this submission
			$editAssignments =& $editorSubmission->getEditAssignments();

			if (empty($editAssignments)) {
				$editorSubmissions[] =& $editorSubmission;
			}
			unset($editorSubmission);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($editorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($editorSubmissions);
		}
		return $returner;
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
	function &getEditorSubmissionsInReview($journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$editorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredEditorSubmissions($journalId, $sectionId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		while (!$result->EOF) {
			$editorSubmission = &$this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
			$articleId = $editorSubmission->getArticleId();
			for ($i = 1; $i <= $editorSubmission->getCurrentRound(); $i++) {
				$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentsByArticleId($articleId, $i);
				if (!empty($reviewAssignment)) {
					$editorSubmission->setReviewAssignments($reviewAssignment, $i);
				}
			}

			// check if submission is still in review
			$inReview = true;
			$decisions = $editorSubmission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
					$inReview = false;			
				}
			}

			// used to check if editor exists for this submission
			$editAssignments =& $editorSubmission->getEditAssignments();

			if (!empty($editAssignments) && $inReview) {
				$editorSubmissions[] =& $editorSubmission;
			}
			unset($editorSubmission);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($editorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($editorSubmissions);
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
	function &getEditorSubmissionsInEditing($journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$editorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredEditorSubmissions($journalId, $sectionId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		while (!$result->EOF) {
			$editorSubmission = &$this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
			$articleId = $editorSubmission->getArticleId();

			// get copyedit final data
			$copyedAssignment = $this->getCopyedAssignment($articleId);
			$row = $copyedAssignment->GetRowAssoc(false);
			$editorSubmission->setCopyeditorDateFinalCompleted($this->datetimeFromDB($row['date_final_completed']));

			// get layout assignment data
			$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment =& $layoutAssignmentDao->getLayoutAssignmentByArticleId($articleId);
			$editorSubmission->setLayoutAssignment($layoutAssignment);

			// get proof assignment data
			$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
			$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
			$editorSubmission->setProofAssignment($proofAssignment);

			// check if submission is still in review
			$inEditing = false;
			$decisions = $editorSubmission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
					$inEditing = true;	
				}
			}

			// used to check if editor exists for this submission
			$editAssignments = $editorSubmission->getEditAssignments();

			if ($inEditing && !empty($editAssignments)) {
				$editorSubmissions[] =& $editorSubmission;
			}
			unset($editorSubmission);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($editorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($editorSubmissions);
		}
		return $returner;
	}

	/**
	 * Get all submissions archived for a journal.
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
	function &getEditorSubmissionsArchives($journalId, $sectionId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$editorSubmissions = array();
	
		$result = $this->getUnfilteredEditorSubmissions($journalId, $sectionId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, false, $rangeInfo);
		while (!$result->EOF) {
			$editorSubmission = &$this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
			$articleId = $editorSubmission->getArticleId();

			// get copyedit final data
			$copyedAssignment = $this->getCopyedAssignment($articleId);
			$row = $copyedAssignment->GetRowAssoc(false);
			$editorSubmission->setCopyeditorDateFinalCompleted($this->datetimeFromDB($row['date_final_completed']));

			// get layout assignment data
			$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment =& $layoutAssignmentDao->getLayoutAssignmentByArticleId($articleId);
			$editorSubmission->setLayoutAssignment($layoutAssignment);

			// get proof assignment data
			$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
			$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
			$editorSubmission->setProofAssignment($proofAssignment);

			$editorSubmissions[] =& $editorSubmission;
			unset($editorSubmission);
			$result->MoveNext();
		}
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new VirtualArrayIterator($editorSubmissions, $result->MaxRecordCount(), $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($editorSubmissions);
		}

		$result->Close();
		unset($result);

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

		$result =& $this->getUnfilteredEditorSubmissions($journalId);

		while (!$result->EOF) {
			$editorSubmission = &$this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));

			// check if submission is still in review
			$inReview = true;
			$notDeclined = true;
			$decisions = $editorSubmission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
					$inReview = false;
				}
			}

			// used to check if editor exists for this submission
			$editAssignments = $editorSubmission->getEditAssignments();

			if (empty($editAssignments)) {
				// unassigned submissions
				$submissionsCount[0] += 1;
			} else {
				if ($inReview) {
					if ($notDeclined) {
						// in review submissions
						$submissionsCount[1] += 1;
					}
				} else {
					// in editing submissions
					$submissionsCount[2] += 1;					
				}
			}
			unset($editorSubmission);
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
		
		$paramArray = array($articleId, $journalId, $roleId);
		$searchSql = '';

		if (isset($search)) switch ($searchType) {
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
				$searchSql = 'AND LOWER(interests) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(last_name) LIKE LOWER(?) OR LOWER(username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}
		
		$result = &$this->retrieveRange(
			'SELECT DISTINCT u.* FROM users u NATURAL JOIN roles r LEFT JOIN edit_assignments e ON (e.editor_id = u.user_id AND e.article_id = ?) WHERE r.journal_id = ? AND r.role_id = ? AND (e.article_id IS NULL) ' . $searchSql . ' ORDER BY last_name, first_name',
			$paramArray, $rangeInfo
		);
		
		$returner = &new DAOResultFactory($result, $this->userDao, '_returnUserFromRow');
		return $returner;
	}
	
	/**
	 * Get the ID of the last inserted editor assignment.
	 * @return int
	 */
	function getInsertEditId() {
		return $this->getInsertId('edit_assignments', 'edit_id');
	}
}

?>
