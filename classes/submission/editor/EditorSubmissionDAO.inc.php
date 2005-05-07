<?php

/**
 * EditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
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

	var $authorDao;
	var $userDao;
	var $editAssignmentDao;

	/**
	 * Constructor.
	 */
	function EditorSubmissionDAO() {
		parent::DAO();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
	}
	
	/**
	 * Retrieve an editor submission by article ID.
	 * @param $articleId int
	 * @return EditorSubmission
	 */
	function &getEditorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, s.abbrev as section_abbrev, s.title as section_title from articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.article_id = ?', $articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return an EditorSubmission object from a row.
	 * @param $row array
	 * @return EditorSubmission
	 */
	function &_returnEditorSubmissionFromRow(&$row) {
		$editorSubmission = &new EditorSubmission();

		// Article attributes
		$editorSubmission->setArticleId($row['article_id']);
		$editorSubmission->setUserId($row['user_id']);
		$editorSubmission->setJournalId($row['journal_id']);
		$editorSubmission->setSectionId($row['section_id']);
		$editorSubmission->setSectionTitle($row['section_title']);
		$editorSubmission->setSectionAbbrev($row['section_abbrev']);
		$editorSubmission->setTitle($row['title']);
		$editorSubmission->setAbstract($row['abstract']);
		$editorSubmission->setDiscipline($row['discipline']);
		$editorSubmission->setSubjectClass($row['subject_class']);
		$editorSubmission->setSubject($row['subject']);
		$editorSubmission->setCoverageGeo($row['coverage_geo']);
		$editorSubmission->setCoverageChron($row['coverage_chron']);
		$editorSubmission->setCoverageSample($row['coverage_sample']);
		$editorSubmission->setType($row['type']);
		$editorSubmission->setLanguage($row['language']);
		$editorSubmission->setSponsor($row['sponsor']);
		$editorSubmission->setCommentsToEditor($row['comments_to_ed']);
		$editorSubmission->setDateSubmitted($row['date_submitted']);
		$editorSubmission->setDateStatusModified($row['date_status_modified']);
		$editorSubmission->setLastModified($row['last_modified']);
		$editorSubmission->setStatus($row['status']);
		$editorSubmission->setSubmissionProgress($row['submission_progress']);
		$editorSubmission->setCurrentRound($row['current_round']);
		$editorSubmission->setSubmissionFileId($row['submission_file_id']);
		$editorSubmission->setRevisedFileId($row['revised_file_id']);
		$editorSubmission->setReviewFileId($row['review_file_id']);
		$editorSubmission->setCopyeditFileId($row['copyedit_file_id']);
		$editorSubmission->setEditorFileId($row['editor_file_id']);
				
		$editorSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));	
		
		// Editor Assignment
		$editorSubmission->setEditor($this->editAssignmentDao->getEditAssignmentByArticleId($row['article_id']));
		
		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$editorSubmission->setDecisions($this->getEditorDecisions($row['article_id'], $i), $i);
		}
		
		return $editorSubmission;
	}

	/**
	 * Insert a new EditorSubmission.
	 * @param $editorSubmission EditorSubmission
	 */	
	function insertEditorSubmission(&$editorSubmission) {
		$this->update(
			'INSERT INTO edit_assignments
				(article_id, editor_id, date_notified, date_completed, date_acknowledged)
				VALUES
				(?, ?, ?, ?, ?)',
			array(
				$editorSubmission->getArticleId(),
				$editorSubmission->getEditorId(),
				$editorSubmission->getDateNotified(),
				$editorSubmission->getDateCompleted(),
				$editorSubmission->getDateAcknowledged()
			)
		);
		
		$editorSubmission->setEditId($this->getInsertEditId());
		
		// Insert review assignments.
		$reviewAssignments = &$editorSubmission->getReviewAssignments();
		for ($i=0, $count=count($reviewAssignments); $i < $count; $i++) {
			$reviewAssignments[$i]->setArticleId($editorSubmission->getArticleId());
			$this->reviewAssignmentDao->insertReviewAssignment(&$reviewAssignments[$i]);
		}
	}
	
	/**
	 * Update an existing article.
	 * @param $article Article
	 */
	function updateEditorSubmission(&$editorSubmission) {
		// update edit assignment
		$editAssignment = $editorSubmission->getEditor();
		if ($editAssignment->getEditId() > 0) {
			$this->editAssignmentDao->updateEditAssignment(&$editAssignment);
		} else {
			$this->editAssignmentDao->insertEditAssignment(&$editAssignment);
		}
	}
	
	/**
	 * Get all submissions for a journal.
	 * @param $journalId int
	 * @param $status boolean true if queued, false if archived.
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissions($journalId, $status = true, $sectionId = 0, $sort = 'article_id', $order = 'ASC', $rangeInfo = null) {
		if (!$sectionId) {
			$result = &$this->retrieveRange(
					'SELECT a.*, s.abbrev as section_abbrev, s.title as section_title from articles a LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ? AND a.status = ? ORDER BY ' . $sort . ' ' . $order,
					array($journalId, $status),
					$rangeInfo
			);
		} else {
			$result = &$this->retrieveRange(
					'SELECT a.*, s.abbrev as section_abbrev, s.title as section_title from articles a LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ? AND a.status = ? AND a.section_id = ? ORDER BY ' . $sort . ' ' . $order,
					array($journalId, $status, $sectionId),
					$rangeInfo
			);	
		}
		return new DAOResultFactory(&$result, $this, '_returnEditorSubmissionFromRow');
	}

	/**
	 * Get all unfiltered submissions for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $sort string
	 * @param $order string
	 * @param $rangeInfo object
	 * @return array result
	 */
	function &getUnfilteredEditorSubmissions($journalId, $sectionId = 0, $sort = 'article_id', $order = 'ASC', $status = true, $rangeInfo = null) {
		$sql = 'SELECT a.*, s.abbrev as section_abbrev, s.title as section_title from articles a LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ?';
		if ($status) {
			$sql .= ' AND a.status = 1';
		} else {
			$sql .= ' AND a.status <> 1';		
		}
		if (!$sectionId) {
			$result = &$this->retrieveRange($sql . " ORDER BY $sort $order", $journalId, $rangeInfo);
		} else {
			$result = &$this->retrieveRange($sql . " AND a.section_id = ? ORDER BY $sort $order", array($journalId, $sectionId), $rangeInfo);
		}
		return $result;		
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
	 * @param $sort string
	 * @param $order string
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissionsUnassigned($journalId, $sectionId, $sort, $order, $rangeInfo = null) {
		$editorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredEditorSubmissions($journalId, $sectionId, $sort, $order, true);

		while (!$result->EOF) {
			$editorSubmission = $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));

			// used to check if editor exists for this submission
			$editor = $editorSubmission->getEditor();

			if (!isset($editor) && !$editorSubmission->getSubmissionProgress()) {
				$editorSubmissions[] = $editorSubmission;
			}
			$result->MoveNext();
		}
		$result->Close();

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			return new ArrayItemIterator(&$editorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			return new ArrayItemIterator(&$editorSubmissions);
		}
	}

	/**
	 * Get all submissions in review for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $sort string
	 * @param $order string
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissionsInReview($journalId, $sectionId, $sort, $order, $rangeInfo = null) {
		$editorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredEditorSubmissions($journalId, $sectionId, $sort, $order, true);

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		while (!$result->EOF) {
			$editorSubmission = $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
			$articleId = $editorSubmission->getArticleId();
			for ($i = 1; $i <= $editorSubmission->getCurrentRound(); $i++) {
				$reviewAssignment = $reviewAssignmentDao->getReviewAssignmentsByArticleId($articleId, $i);
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
			$editor = $editorSubmission->getEditor();

			if (isset($editor) && $inReview && !$editorSubmission->getSubmissionProgress()) {
				$editorSubmissions[] = $editorSubmission;
			}
			$result->MoveNext();
		}
		$result->Close();
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			return new ArrayItemIterator(&$editorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			return new ArrayItemIterator(&$editorSubmissions);
		}
	}

	/**
	 * Get all submissions in editing for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $sort string
	 * @param $order string
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissionsInEditing($journalId, $sectionId, $sort, $order, $rangeInfo = null) {
		$editorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredEditorSubmissions($journalId, $sectionId, $sort, $order, true);

		while (!$result->EOF) {
			$editorSubmission = $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
			$articleId = $editorSubmission->getArticleId();

			// get copyedit final data
			$copyedAssignment = $this->getCopyedAssignment($articleId);
			$row = $copyedAssignment->GetRowAssoc(false);
			$editorSubmission->setCopyeditorDateFinalCompleted($row['date_final_completed']);

			// get layout assignment data
			$layoutAssignmentDao = DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment = $layoutAssignmentDao->getLayoutAssignmentByArticleId($articleId);
			$editorSubmission->setLayoutAssignment($layoutAssignment);

			// get proof assignment data
			$proofAssignmentDao = DAORegistry::getDAO('ProofAssignmentDAO');
			$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
			$editorSubmission->setProofAssignment($proofAssignment);

			// check if submission is still in review
			$inEditing = false;
			$decisions = $editorSubmission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == 1) {
					$inEditing = true;	
				}
			}

			// used to check if editor exists for this submission
			$editor = $editorSubmission->getEditor();

			if ($inEditing && isset($editor) && !$editorSubmission->getSubmissionProgress()) {
				$editorSubmissions[] = $editorSubmission;
			}
			$result->MoveNext();
		}
		$result->Close();
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			return new ArrayItemIterator(&$editorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			return new ArrayItemIterator(&$editorSubmissions);
		}
	}

	/**
	 * Get all submissions archived for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $sort string
	 * @param $order string
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissionsArchives($journalId, $sectionId, $sort, $order, $rangeInfo = null) {
		$editorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredEditorSubmissions($journalId, $sectionId, $sort, $order, false);
		while (!$result->EOF) {
			$editorSubmission = $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
			$articleId = $editorSubmission->getArticleId();

			// get copyedit final data
			$copyedAssignment = $this->getCopyedAssignment($articleId);
			$row = $copyedAssignment->GetRowAssoc(false);
			$editorSubmission->setCopyeditorDateFinalCompleted($row['date_final_completed']);

			// get layout assignment data
			$layoutAssignmentDao = DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment = $layoutAssignmentDao->getLayoutAssignmentByArticleId($articleId);
			$editorSubmission->setLayoutAssignment($layoutAssignment);

			// get proof assignment data
			$proofAssignmentDao = DAORegistry::getDAO('ProofAssignmentDAO');
			$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
			$editorSubmission->setProofAssignment($proofAssignment);

			if (!$editorSubmission->getSubmissionProgress()) {
				$editorSubmissions[] = $editorSubmission;
			}
			$result->MoveNext();
		}
		$result->Close();
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			return new ArrayItemIterator(&$editorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			return new ArrayItemIterator(&$editorSubmissions);
		}
	}

	/**
	 * Function used for counting purposes for right nav bar
	 */
	function &getEditorSubmissionsCount($journalId) {

		$submissionsCount = array();
		for($i = 0; $i < 4; $i++) {
			$submissionsCount[$i] = 0;
		}

		$sql = 'SELECT a.*, s.abbrev as section_abbrev, s.title as section_title from articles a LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ? AND (a.status = ' . STATUS_QUEUED . ' OR a.status = ' . STATUS_SCHEDULED . ') ORDER BY article_id ASC';
		$result = &$this->retrieve($sql, $journalId);

		while (!$result->EOF) {
			$editorSubmission = $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));

			// check if submission is still in review
			$inReview = true;
			$notDeclined = true;
			$decisions = $editorSubmission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == 1) {
					$inReview = false;
				} elseif ($latestDecision['decision'] == 4) {
					$notDeclined = false;
				}
			}

			// used to check if editor exists for this submission
			$editor = $editorSubmission->getEditor();

			if (!$editorSubmission->getSubmissionProgress()) {
				if (!isset($editor)) {
					// unassigned submissions
					$submissionsCount[0] += 1;
				} elseif ($editorSubmission->getStatus() == STATUS_SCHEDULED) {
					// scheduled submissions
					$submissionsCount[3] += 1;			
				} elseif ($editorSubmission->getStatus() == STATUS_QUEUED) {
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
			}

			$result->MoveNext();
		}
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
			$result = &$this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ?', $articleId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? AND round = ?',
				array($articleId, $round)
			);
		}
		
		while (!$result->EOF) {
			$decisions[] = array('editDecisionId' => $result->fields[0], 'editorId' => $result->fields[1], 'decision' => $result->fields[2], 'dateDecided' => $result->fields[3]);
			$result->moveNext();
		}
		$result->Close();
	
		return $decisions;
	}
	
	/**
	 * Retrieve a list of all section editors not assigned to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @return DAOResultFactory containing matching Users
	 */
	function &getSectionEditorsNotAssignedToArticle($journalId, $articleId, $searchType=null, $search=null, $searchMatch=null, $rangeInfo = null) {
		$users = array();
		
		$paramArray = array($articleId, $journalId, RoleDAO::getRoleIdFromPath('sectionEditor'));
		$searchSql = '';

		if (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND ' . ($searchMatch=='is'?'first_name=?':'LOWER(first_name) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND ' . ($searchMatch=='is'?'last_name=?':'LOWER(last_name) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND ' . ($searchMatch=='is'?'username=?':'LOWER(username) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND ' . ($searchMatch=='is'?'email=?':'LOWER(email) LIKE LOWER(?)');
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND ' . ($searchMatch=='is'?'interests=?':'LOWER(interests) LIKE LOWER(?)');
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
		
		return new DAOResultFactory(&$result, $this->userDao, '_returnUserFromRow');
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
