<?php

/**
 * SectionEditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for SectionEditorSubmission DAO.
 * Operations for retrieving and modifying SectionEditorSubmission objects.
 *
 * $Id$
 */

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
		$result = &$this->retrieve(
			'SELECT a.*, s.title as section_title, c.copyed_id, c.copyeditor_id, c.copyedit_revision, c.comments AS copyeditor_comments, c.date_notified AS copyeditor_date_notified, c.date_underway AS copyeditor_date_underway, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS copyeditor_date_author_notified, c.date_author_underway AS copyeditor_date_author_underway, c.date_author_completed AS copyeditor_date_author_completed,
				c.date_author_acknowledged AS copyeditor_date_author_acknowledged, c.date_final_notified AS copyeditor_date_final_notified, c.date_final_underway AS copyeditor_date_final_underway, c.date_final_completed AS copyeditor_date_final_completed, c.date_final_acknowledged AS copyeditor_date_final_acknowledged, c.initial_revision AS copyeditor_initial_revision, c.editor_author_revision AS copyeditor_editor_author_revision,
				c.final_revision AS copyeditor_final_revision, r2.review_revision
				FROM articles a LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id) LEFT JOIN review_rounds r2 ON (a.article_id = r2.article_id AND a.current_round = r2.round) WHERE a.article_id = ?', $articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a SectionEditorSubmission object from a row.
	 * @param $row array
	 * @return SectionEditorSubmission
	 */
	function &_returnSectionEditorSubmissionFromRow(&$row) {
		$sectionEditorSubmission = &new SectionEditorSubmission();
		
		// Article attributes
		$sectionEditorSubmission->setArticleId($row['article_id']);
		$sectionEditorSubmission->setUserId($row['user_id']);
		$sectionEditorSubmission->setJournalId($row['journal_id']);
		$sectionEditorSubmission->setSectionId($row['section_id']);
		$sectionEditorSubmission->setSectionTitle($row['section_title']);
		$sectionEditorSubmission->setTitle($row['title']);
		$sectionEditorSubmission->setAbstract($row['abstract']);
		$sectionEditorSubmission->setDiscipline($row['discipline']);
		$sectionEditorSubmission->setSubjectClass($row['subject_class']);
		$sectionEditorSubmission->setSubject($row['subject']);
		$sectionEditorSubmission->setCoverageGeo($row['coverage_geo']);
		$sectionEditorSubmission->setCoverageChron($row['coverage_chron']);
		$sectionEditorSubmission->setCoverageSample($row['coverage_sample']);
		$sectionEditorSubmission->setType($row['type']);
		$sectionEditorSubmission->setLanguage($row['language']);
		$sectionEditorSubmission->setSponsor($row['sponsor']);
		$sectionEditorSubmission->setCommentsToEditor($row['comments_to_ed']);
		$sectionEditorSubmission->setDateSubmitted($row['date_submitted']);
		$sectionEditorSubmission->setStatus($row['status']);
		$sectionEditorSubmission->setSubmissionProgress($row['submission_progress']);
		$sectionEditorSubmission->setCurrentRound($row['current_round']);
		$sectionEditorSubmission->setSubmissionFileId($row['submission_file_id']);
		$sectionEditorSubmission->setRevisedFileId($row['revised_file_id']);
		$sectionEditorSubmission->setReviewFileId($row['review_file_id']);
		$sectionEditorSubmission->setEditorFileId($row['editor_file_id']);
		$sectionEditorSubmission->setCopyeditFileId($row['copyedit_file_id']);
		
		$sectionEditorSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));

		// Editor Assignment
		$sectionEditorSubmission->setEditor($this->editAssignmentDao->getEditAssignmentByArticleId($row['article_id']));
		
		// Replaced Editors
		$sectionEditorSubmission->setReplacedEditors($this->editAssignmentDao->getReplacedEditAssignmentsByArticleId($row['article_id']));
		
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
		if ($row['copyeditor_editor_author_revision'] != null) {
			$sectionEditorSubmission->setEditorAuthorCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['copyeditor_editor_author_revision']));
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
		
		// Logs
		$sectionEditorSubmission->setEmailLogs($this->articleEmailLogDao->getArticleLogEntries($row['article_id'], true));

		// Copyeditor Assignment
		$sectionEditorSubmission->setCopyedId($row['copyed_id']);
		$sectionEditorSubmission->setCopyeditorId($row['copyeditor_id']);
		$sectionEditorSubmission->setCopyeditor($this->userDao->getUser($row['copyeditor_id']));
		$sectionEditorSubmission->setCopyeditorComments($row['copyeditor_comments']);
		$sectionEditorSubmission->setCopyeditorDateNotified($row['copyeditor_date_notified']);
		$sectionEditorSubmission->setCopyeditorDateUnderway($row['copyeditor_date_underway']);
		$sectionEditorSubmission->setCopyeditorDateCompleted($row['copyeditor_date_completed']);
		$sectionEditorSubmission->setCopyeditorDateAcknowledged($row['copyeditor_date_acknowledged']);
		$sectionEditorSubmission->setCopyeditorDateAuthorNotified($row['copyeditor_date_author_notified']);
		$sectionEditorSubmission->setCopyeditorDateAuthorUnderway($row['copyeditor_date_author_underway']);
		$sectionEditorSubmission->setCopyeditorDateAuthorCompleted($row['copyeditor_date_author_completed']);
		$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged($row['copyeditor_date_author_acknowledged']);
		$sectionEditorSubmission->setCopyeditorDateFinalNotified($row['copyeditor_date_final_notified']);
		$sectionEditorSubmission->setCopyeditorDateFinalUnderway($row['copyeditor_date_final_underway']);
		$sectionEditorSubmission->setCopyeditorDateFinalCompleted($row['copyeditor_date_final_completed']);
		$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged($row['copyeditor_date_final_acknowledged']);
		$sectionEditorSubmission->setCopyeditorInitialRevision($row['copyeditor_initial_revision']);
		$sectionEditorSubmission->setCopyeditorEditorAuthorRevision($row['copyeditor_editor_author_revision']);
		$sectionEditorSubmission->setCopyeditorFinalRevision($row['copyeditor_final_revision']);

		// Layout Editing
		$sectionEditorSubmission->setLayoutAssignment($this->layoutAssignmentDao->getLayoutAssignmentByArticleId($row['article_id']));

		$sectionEditorSubmission->setGalleys($this->galleyDao->getGalleysByArticle($row['article_id']));

		// Proof Assignment
		$sectionEditorSubmission->setProofAssignment($this->proofAssignmentDao->getProofAssignmentByArticleId($row['article_id']));
			
		return $sectionEditorSubmission;
	}
	
	/**
	 * Update an existing section editor submission.
	 * @param $sectionEditorSubmission SectionEditorSubmission
	 */
	function updateSectionEditorSubmission(&$sectionEditorSubmission) {
		// update edit assignment
		$editAssignment = $sectionEditorSubmission->getEditor();
		if ($editAssignment != null) {
			if ($editAssignment->getEditId() > 0) {
				$this->editAssignmentDao->updateEditAssignment(&$editAssignment);
			} else {
				$this->editAssignmentDao->insertEditAssignment(&$editAssignment);
			}
		}
		
		// update replaced edit assignment
		foreach ($sectionEditorSubmission->getReplacedEditors() as $editAssignment) {
			if ($editAssignment->getEditId() > 0) {
				$this->editAssignmentDao->updateEditAssignment(&$editAssignment);
			} else {
				$this->editAssignmentDao->insertEditAssignment(&$editAssignment);
			}
		}
	
		// Update editor decisions
		for ($i = 1; $i <= $sectionEditorSubmission->getCurrentRound(); $i++) {
			$editorDecisions = $sectionEditorSubmission->getDecisions($i);
			if (is_array($editorDecisions)) {
				foreach ($editorDecisions as $editorDecision) {
					if ($editorDecision['editDecisionId'] == null) {
						$this->update(
							'INSERT INTO edit_decisions
								(article_id, round, editor_id, decision, date_decided)
								VALUES (?, ?, ?, ?, ?)',
							array($sectionEditorSubmission->getArticleId(), $sectionEditorSubmission->getCurrentRound(), $editorDecision['editorId'], $editorDecision['decision'], $editorDecision['dateDecided'])
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
		} else {
			$this->update(
				'INSERT INTO review_rounds
					(article_id, round, review_revision)
					VALUES
					(?, ?, ?)',
				array(
					$sectionEditorSubmission->getArticleId(),
					$sectionEditorSubmission->getCurrentRound() === null ? 1 : $sectionEditorSubmission->getCurrentRound(),
					$sectionEditorSubmission->getReviewRevision()
				)
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
		$copyeditorSubmission->setComments($sectionEditorSubmission->getCopyeditorComments());
		$copyeditorSubmission->setDateNotified($sectionEditorSubmission->getCopyeditorDateNotified());
		$copyeditorSubmission->setDateAcknowledged($sectionEditorSubmission->getCopyeditorDateAcknowledged());
		$copyeditorSubmission->setDateAuthorNotified($sectionEditorSubmission->getCopyeditorDateAuthorNotified());
		$copyeditorSubmission->setDateAuthorAcknowledged($sectionEditorSubmission->getCopyeditorDateAuthorAcknowledged());
		$copyeditorSubmission->setDateFinalNotified($sectionEditorSubmission->getCopyeditorDateFinalNotified());
		$copyeditorSubmission->setDateFinalCompleted($sectionEditorSubmission->getCopyeditorDateFinalCompleted());
		$copyeditorSubmission->setDateFinalAcknowledged($sectionEditorSubmission->getCopyeditorDateFinalAcknowledged());
		$copyeditorSubmission->setInitialRevision($sectionEditorSubmission->getCopyeditorInitialRevision());
		$copyeditorSubmission->setEditorAuthorRevision($sectionEditorSubmission->getCopyeditorEditorAuthorRevision());
		$copyeditorSubmission->setFinalRevision($sectionEditorSubmission->getCopyeditorFinalRevision());
			
		if ($copyeditorSubmission->getCopyedId() != null) {
			$this->copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
		} else {
			$this->copyeditorSubmissionDao->insertCopyeditorSubmission($copyeditorSubmission);
		}
		
		// update review assignments
		foreach ($sectionEditorSubmission->getReviewAssignments() as $roundReviewAssignments) {
			foreach ($roundReviewAssignments as $reviewAssignment) {
				if ($reviewAssignment->getReviewId() > 0) {
					$this->reviewAssignmentDao->updateReviewAssignment(&$reviewAssignment);
				} else {
					$this->reviewAssignmentDao->insertReviewAssignment(&$reviewAssignment);
				}
			}
		}
		
		// Remove deleted review assignments
		$removedReviewAssignments = $sectionEditorSubmission->getRemovedReviewAssignments();
		for ($i=0, $count=count($removedReviewAssignments); $i < $count; $i++) {
			$this->reviewAssignmentDao->deleteReviewAssignmentById($removedReviewAssignments[$i]);
		}
		
		// Update layout editing assignment
		$layoutAssignment = $sectionEditorSubmission->getLayoutAssignment();
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

			$this->articleDao->updateArticle($article);
		}
		
	}
	
	/**
	 * Get all section editor submissions for a section editor.
	 * @param $sectionEditorId int
	 * @param $status boolean true if active, false if completed.
	 * @return array SectionEditorSubmission
	 */
	function &getSectionEditorSubmissions($sectionEditorId, $journalId, $status = true) {
		$sectionEditorSubmissions = array();
		
		$result = &$this->retrieve(
			'SELECT a.*, s.title as section_title, c.copyed_id, c.copyeditor_id, c.copyedit_revision, c.comments AS copyeditor_comments, c.date_notified AS copyeditor_date_notified, c.date_underway AS copyeditor_date_underway, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS copyeditor_date_author_notified, c.date_author_underway AS copyeditor_date_author_underway, c.date_author_completed AS copyeditor_date_author_completed,
				c.date_author_acknowledged AS copyeditor_date_author_acknowledged, c.date_final_notified AS copyeditor_date_final_notified, c.date_final_underway AS copyeditor_date_final_underway, c.date_final_completed AS copyeditor_date_final_completed, c.date_final_acknowledged AS copyeditor_date_final_acknowledged, c.initial_revision AS copyeditor_initial_revision, c.editor_author_revision AS copyeditor_editor_author_revision,
				c.final_revision AS copyeditor_final_revision, r2.review_revision
				FROM articles a LEFT JOIN edit_assignments e ON (e.article_id = a.article_id AND e.replaced = 0) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id) LEFT JOIN review_rounds r2 ON (a.article_id = r2.article_id and a.current_round = r2.round) WHERE a.journal_id = ? AND e.editor_id = ? AND a.status = ?',
			array($journalId, $sectionEditorId, $status)
		);
		
		while (!$result->EOF) {
			$sectionEditorSubmissions[] = $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $sectionEditorSubmissions;
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
			$decisions[] = array(
				'editDecisionId' => $result->fields['edit_decision_id'],
				'editorId' => $result->fields['editor_id'],
				'decision' => $result->fields['decision'],
				'dateDecided' => $result->fields['date_decided']
			);
			$result->moveNext();
		}
		$result->Close();
	
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
		return isset($result->fields[0]) ? $result->fields[0] : 0;
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
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
	/**
	 * Check if a reviewer is assigned to a specified article.
	 * @param $articleId int
	 * @param $reviewerId int
	 * @return boolean
	 */
	function reviewerExists($articleId, $reviewerId, $round) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM review_assignments WHERE article_id = ? AND reviewer_id = ? AND round = ?', array($articleId, $reviewerId, $round)
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
	/**
	 * Retrieve a list of all reviewers not assigned to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @return array matching Users
	 */
	function &getReviewersNotAssignedToArticle($journalId, $articleId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT u.* FROM users u, roles r LEFT JOIN review_assignments a ON (a.reviewer_id = u.user_id AND a.article_id = ?) WHERE u.user_id = r.user_id AND r.journal_id = ? AND r.role_id = ? AND a.article_id IS NULL ORDER BY last_name, first_name',
			array($articleId, $journalId, RoleDAO::getRoleIdFromPath('reviewer'))
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
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
	function &getCopyeditorsNotAssignedToArticle($journalId, $articleId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$result = &$this->retrieve(
			'SELECT u.* FROM users u, roles r LEFT JOIN copyed_assignments a ON (a.copyeditor_id = u.user_id AND a.article_id = ?) WHERE u.user_id = r.user_id AND r.journal_id = ? AND r.role_id = ? AND a.article_id IS NULL ORDER BY last_name, first_name',
			array($articleId, $journalId, RoleDAO::getRoleIdFromPath('copyeditor'))
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
	}

}

?>
