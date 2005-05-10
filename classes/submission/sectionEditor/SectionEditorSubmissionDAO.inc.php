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
		$result = &$this->retrieve(
			'SELECT a.*, s.abbrev as section_abbrev, s.title as section_title, c.copyed_id, c.copyeditor_id, c.copyedit_revision, c.comments AS copyeditor_comments, c.date_notified AS copyeditor_date_notified, c.date_underway AS copyeditor_date_underway, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS copyeditor_date_author_notified, c.date_author_underway AS copyeditor_date_author_underway, c.date_author_completed AS copyeditor_date_author_completed,
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
		$sectionEditorSubmission->setSectionAbbrev($row['section_abbrev']);
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
		$sectionEditorSubmission->setDateStatusModified($row['date_status_modified']);
		$sectionEditorSubmission->setLastModified($row['last_modified']);
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

		// Copyeditor Assignment
		$sectionEditorSubmission->setCopyedId($row['copyed_id']);
		$sectionEditorSubmission->setCopyeditorId($row['copyeditor_id']);
		$sectionEditorSubmission->setCopyeditor($this->userDao->getUser($row['copyeditor_id']), true);
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
			$article->setDateStatusModified($sectionEditorSubmission->getDateStatusModified());
			$article->setLastModified($sectionEditorSubmission->getLastModified());

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
			'SELECT a.*, s.abbrev as section_abbrev, s.title as section_title, c.copyed_id, c.copyeditor_id, c.copyedit_revision, c.comments AS copyeditor_comments, c.date_notified AS copyeditor_date_notified, c.date_underway AS copyeditor_date_underway, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS copyeditor_date_author_notified, c.date_author_underway AS copyeditor_date_author_underway, c.date_author_completed AS copyeditor_date_author_completed,
				c.date_author_acknowledged AS copyeditor_date_author_acknowledged, c.date_final_notified AS copyeditor_date_final_notified, c.date_final_underway AS copyeditor_date_final_underway, c.date_final_completed AS copyeditor_date_final_completed, c.date_final_acknowledged AS copyeditor_date_final_acknowledged, c.initial_revision AS copyeditor_initial_revision, c.editor_author_revision AS copyeditor_editor_author_revision,
				c.final_revision AS copyeditor_final_revision, r2.review_revision
				FROM articles a LEFT JOIN edit_assignments e ON (e.article_id = a.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id) LEFT JOIN review_rounds r2 ON (a.article_id = r2.article_id and a.current_round = r2.round) WHERE a.journal_id = ? AND e.editor_id = ? AND a.status = ?',
			array($journalId, $sectionEditorId, $status)
		);
		
		while (!$result->EOF) {
			$sectionEditorSubmissions[] = $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $sectionEditorSubmissions;
	}

	/**
	 * Retrieve unfiltered section editor submissions
	 */
	function &getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId, $sectionId = 0, $sort = 'article_id', $order = 'ASC', $status = true, $rangeInfo = null) {
		$sql = 'SELECT a.*, s.abbrev as section_abbrev, s.title as section_title, c.copyed_id, c.copyeditor_id, c.copyedit_revision, c.comments AS copyeditor_comments, c.date_notified AS copyeditor_date_notified, c.date_underway AS copyeditor_date_underway, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS copyeditor_date_author_notified, c.date_author_underway AS copyeditor_date_author_underway, c.date_author_completed AS copyeditor_date_author_completed, c.date_author_acknowledged AS copyeditor_date_author_acknowledged, c.date_final_notified AS copyeditor_date_final_notified, c.date_final_underway AS copyeditor_date_final_underway, c.date_final_completed AS copyeditor_date_final_completed, c.date_final_acknowledged AS copyeditor_date_final_acknowledged, c.initial_revision AS copyeditor_initial_revision, c.editor_author_revision AS copyeditor_editor_author_revision, c.final_revision AS copyeditor_final_revision, r2.review_revision FROM articles a LEFT JOIN edit_assignments e ON (e.article_id = a.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id) LEFT JOIN review_rounds r2 ON (a.article_id = r2.article_id and a.current_round = r2.round) WHERE a.journal_id = ? AND e.editor_id = ?';

		if ($status) {
			$sql .= ' AND a.status = 1';
		} else {
			$sql .= ' AND a.status <> 1';
		}

		if (!$sectionId) {
			$result = &$this->retrieveRange($sql . " ORDER BY $sort $order", 
				array($journalId, $sectionEditorId),
				$rangeInfo
			);
		} else {
			$result = &$this->retrieveRange($sql . " AND a.section_id = ? ORDER BY $sort $order", 
				array($journalId, $sectionEditorId, $sectionId),
				$rangeInfo
			);	
		}

		return $result;	
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
	function &getSectionEditorSubmissionsInReview($sectionEditorId, $journalId, $sectionId, $sort, $order, $rangeInfo = null) {
		$submissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId, $sectionId, $sort, $order, true);

		while (!$result->EOF) {
			$submission = $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
			$articleId = $submission->getArticleId();

			// check if submission is still in review
			$inReview = true;
			$decisions = $submission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
					$inReview = false;			
				}
			}

			// used to check if editor exists for this submission
			$editor = $submission->getEditor();

			if (isset($editor) && $inReview && !$submission->getSubmissionProgress()) {
				$submissions[] = $submission;
			}
			$result->MoveNext();
		}
		$result->Close();

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			return new ArrayItemIterator(&$submissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			return new ArrayItemIterator(&$submissions);
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
	function &getSectionEditorSubmissionsInEditing($sectionEditorId, $journalId, $sectionId, $sort, $order, $rangeInfo = null) {
		$submissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId, $sectionId, $sort, $order, true);

		while (!$result->EOF) {
			$submission = $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));

			// check if submission is still in review
			$notInReview = false;
			$decisions = $submission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
					$notInReview = true;	
				}
			}

			// used to check if editor exists for this submission
			$editor = $submission->getEditor();

			if ($notInReview && isset($editor) && !$submission->getSubmissionProgress()) {
				$submissions[] = $submission;
			}
			$result->MoveNext();
		}
		$result->Close();
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			return new ArrayItemIterator(&$submissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			return new ArrayItemIterator(&$submissions);
		}
	}

	/**
	 * Get all submissions in archives for a journal.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $sort string
	 * @param $order string
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getSectionEditorSubmissionsArchives($sectionEditorId, $journalId, $sectionId, $sort, $order, $rangeInfo = null) {
		$submissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredSectionEditorSubmissions($sectionEditorId, $journalId, $sectionId, $sort, $order, false);

		while (!$result->EOF) {
			$submission = $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));

			if (!$submission->getSubmissionProgress()) {
				$submissions[] = $submission;
			}
			$result->MoveNext();
		}
		$result->Close();
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			return new ArrayItemIterator(&$submissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			return new ArrayItemIterator(&$submissions);
		}
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
			$sectionEditorSubmission = $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));

			// check if submission is still in review
			$inReview = true;
			$notDeclined = true;
			$decisions = $sectionEditorSubmission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT) {
					$inReview = false;
				} elseif ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
					$notDeclined = false;
				}
			}

			if (!$sectionEditorSubmission->getSubmissionProgress()) {
				if ($inReview) {
					if ($notDeclined) {
						// in review submissions
						$submissionsCount[0] += 1;
					}
				} else {
					// in editing submissions
					$submissionsCount[1] += 1;					
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
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? ORDER BY edit_decision_id ASC', $articleId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? AND round = ? ORDER BY edit_decision_id ASC',
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
			'SELECT COUNT(*) FROM review_assignments WHERE article_id = ? AND reviewer_id = ? AND round = ? AND cancelled = 0', array($articleId, $reviewerId, $round)
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
	/**
	 * Retrieve a list of all reviewers along with information about their current status with respect to an article's current round.
	 * @param $journalId int
	 * @param $articleId int
	 * @return DAOResultFactory containing matching Users
	 */
	function &getReviewersForArticle($journalId, $articleId, $round, $searchType = null, $search = null, $searchMatch = null, $rangeInfo = null) {
		$paramArray = array($articleId, $round, $journalId, RoleDAO::getRoleIdFromPath('reviewer'));
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
			'SELECT DISTINCT u.*, a.review_id as review_id, a.cancelled as cancelled FROM users u NATURAL JOIN roles r LEFT JOIN review_assignments a ON (a.reviewer_id = u.user_id AND a.article_id = ? AND a.round = ?) WHERE u.user_id = r.user_id AND r.journal_id = ? AND r.role_id = ? ' . $searchSql . ' ORDER BY last_name, first_name',
			$paramArray, $rangeInfo
		);
		
		return new DAOResultFactory(&$result, $this, '_returnReviewerUserFromRow');
	}
	
	function &_returnReviewerUserFromRow(&$row) { // FIXME
		$user = $this->userDao->_returnUserFromRow($row);
		$user->review_id = $row['review_id'];
		$user->cancelled = $row['cancelled'];
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
			'SELECT u.* FROM users u NATURAL JOIN roles r LEFT JOIN review_assignments a ON (a.reviewer_id = u.user_id AND a.article_id = ?) WHERE r.journal_id = ? AND r.role_id = ? AND a.article_id IS NULL ORDER BY last_name, first_name',
			array($articleId, $journalId, RoleDAO::getRoleIdFromPath('reviewer'))
		);
		
		while (!$result->EOF) {
			$users[] = $this->userDao->_returnUserFromRow($result->GetRowAssoc(false));
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
	function &getCopyeditorsNotAssignedToArticle($journalId, $articleId, $searchType = null, $search = null, $searchMatch = null) {
		$users = array();
		
		$paramArray = array($articleId, $journalId, RoleDAO::getRoleIdFromPath('copyeditor'));
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
		
		$result = &$this->retrieve(
			'SELECT u.* FROM users u NATURAL JOIN roles r LEFT JOIN copyed_assignments a ON (a.copyeditor_id = u.user_id AND a.article_id = ?) WHERE r.journal_id = ? AND r.role_id = ? AND a.article_id IS NULL ' . $searchSql . ' ORDER BY last_name, first_name',
			$paramArray
		);
		
		while (!$result->EOF) {
			$users[] = $this->userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
	}

	/**
	 * Get the assignment counts and last assigned date for all layout editors of the given journal.
	 * @return array
	 */
	function getLayoutEditorStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('select la.editor_id as editor_id, count(la.article_id) as complete from layouted_assignments la, articles a where la.article_id=a.article_id and la.date_completed is not null and a.journal_id=? group by la.editor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}
		$result->Close();

		// Get counts of incomplete submissions
		$result = &$this->retrieve('select la.editor_id as editor_id, count(la.article_id) as incomplete from layouted_assignments la, articles a where la.article_id=a.article_id and la.date_completed is null and a.journal_id=? group by la.editor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}
		$result->Close();

		// Get last assignment date
		$result = &$this->retrieve('select la.editor_id as editor_id, max(la.date_notified) as last_assigned from layouted_assignments la, articles a where la.article_id=a.article_id and a.journal_id=? group by la.editor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_assigned'] = $row['last_assigned'];
			$result->MoveNext();
		}
		$result->Close();

		return $statistics;
	}

	/**
	 * Get the last assigned and last completed dates for all reviewers of the given journal.
	 * @return array
	 */
	function getReviewerStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('select ra.reviewer_id as editor_id, max(ra.date_notified) as last_notified from review_assignments ra, articles a where ra.article_id=a.article_id and a.journal_id=? group by ra.reviewer_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_notified'] = $row['last_notified'];
			$result->MoveNext();
		}
		$result->Close();

		// Get completion status
		$result = &$this->retrieve('select r.reviewer_id as reviewer_id from review_assignments r, articles a where r.article_id=a.article_id and r.date_notified is not null and r.date_completed is null and r.cancelled = 0 and a.journal_id = ? group by r.reviewer_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();
			$statistics[$row['reviewer_id']]['incomplete'] = 1;
			$result->MoveNext();
		}
		$result->Close();

		// Calculate time taken for completed reviews
		$result = &$this->retrieve('select r.reviewer_id as reviewer_id, r.date_notified as date_notified, r.date_completed as date_completed from review_assignments r, articles a where r.article_id=a.article_id and r.date_notified is not null and r.date_completed is not null and a.journal_id = ?', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();

			$completed = strtotime($row['date_completed']);
			$notified = strtotime($row['date_notified']);
			if (isset($statistics[$row['reviewer_id']]['total_span'])) {
				$statistics[$row['reviewer_id']]['total_span'] += $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] += 1;
			} else {
				$statistics[$row['reviewer_id']]['total_span'] = $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] = 1;
			}

			// Calculate the average length of review in days.
			$statistics[$row['reviewer_id']]['average_span'] = (($statistics[$row['reviewer_id']]['total_span'] / $statistics[$row['reviewer_id']]['completed_review_count']) / 60 / 60 / 24);
			$result->MoveNext();
		}
		$result->Close();

		return $statistics;
	}

	/**
	 * Get the assignment counts and last assigned date for all copyeditors of the given journal.
	 * @return array
	 */
	function getCopyeditorStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('select ca.copyeditor_id as editor_id, count(ca.article_id) as complete from copyed_assignments ca, articles a where ca.article_id=a.article_id and ca.date_completed is not null and a.journal_id=? group by ca.copyeditor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}
		$result->Close();

		// Get counts of incomplete submissions
		$result = &$this->retrieve('select ca.copyeditor_id as editor_id, count(ca.article_id) as incomplete from copyed_assignments ca, articles a where ca.article_id=a.article_id and ca.date_completed is null and a.journal_id=? group by ca.copyeditor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}
		$result->Close();

		// Get last assignment date
		$result = &$this->retrieve('select ca.copyeditor_id as editor_id, max(ca.date_notified) as last_assigned from copyed_assignments ca, articles a where ca.article_id=a.article_id and a.journal_id=? group by ca.copyeditor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_assigned'] = $row['last_assigned'];
			$result->MoveNext();
		}
		$result->Close();

		return $statistics;
	}

	/**
	 * Get the assignment counts and last assigned date for all proofreaders of the given journal.
	 * @return array
	 */
	function getProofreaderStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('select pa.proofreader_id as editor_id, count(pa.article_id) as complete from proof_assignments pa, articles a where pa.article_id=a.article_id and pa.date_proofreader_completed is not null and a.journal_id=? group by pa.proofreader_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}
		$result->Close();

		// Get counts of incomplete submissions
		$result = &$this->retrieve('select pa.proofreader_id as editor_id, count(pa.article_id) as incomplete from proof_assignments pa, articles a where pa.article_id=a.article_id and pa.date_proofreader_completed is null and a.journal_id=? group by pa.proofreader_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}
		$result->Close();

		// Get last assignment date
		$result = &$this->retrieve('select pa.proofreader_id as editor_id, max(pa.date_proofreader_notified) as last_assigned from proof_assignments pa, articles a where pa.article_id=a.article_id and a.journal_id=? group by pa.proofreader_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_assigned'] = $row['last_assigned'];
			$result->MoveNext();
		}
		$result->Close();

		return $statistics;
	}
}

?>
