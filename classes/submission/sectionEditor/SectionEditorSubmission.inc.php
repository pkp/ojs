<?php

/**
 * @file SectionEditorSubmission.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 * @class SectionEditorSubmission
 *
 * SectionEditorSubmission class.
 *
 * $Id$
 */

import('article.Article');

class SectionEditorSubmission extends Article {

	/** @var array ReviewAssignments of this article */
	var $reviewAssignments;

	/** @var array IDs of ReviewAssignments removed from this article */
	var $removedReviewAssignments;

	/** @var array the editor decisions of this article */
	var $editorDecisions;

	/** @var array the revisions of the editor file */
	var $editorFileRevisions;

	/** @var array the revisions of the author file */
	var $authorFileRevisions;

	/** @var array the revisions of the revised copyedit file */
	var $copyeditFileRevisions;

	/**
	 * Constructor.
	 */
	function SectionEditorSubmission() {
		parent::Article();
		$this->reviewAssignments = array();
		$this->removedReviewAssignments = array();
	}

	/**
	 * Add a review assignment for this article.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function addReviewAssignment($reviewAssignment) {
		if ($reviewAssignment->getArticleId() == null) {
			$reviewAssignment->setArticleId($this->getArticleId());
		}

		if (isset($this->reviewAssignments[$reviewAssignment->getRound()])) {
			$roundReviewAssignments = $this->reviewAssignments[$reviewAssignment->getRound()];
		} else {
			$roundReviewAssignments = Array();
		}
		array_push($roundReviewAssignments, $reviewAssignment);

		return $this->reviewAssignments[$reviewAssignment->getRound()] = $roundReviewAssignments;
	}

	/**
	 * Add an editorial decision for this article.
	 * @param $editorDecision array
	 * @param $round int
	 */
	function addDecision($editorDecision, $round) {
		if (isset($this->editorDecisions[$round]) && is_array($this->editorDecisions[$round])) {
			array_push($this->editorDecisions[$round], $editorDecision);
		}
		else $this->editorDecisions[$round] = Array($editorDecision);
	}

	/**
	 * Remove a review assignment.
	 * @param $reviewId ID of the review assignment to remove
	 * @return boolean review assignment was removed
	 */
	function removeReviewAssignment($reviewId) {
		$found = false;

		if ($reviewId != 0) {
			// FIXME maintain a hash of ID to author for quicker get/remove
			$reviewAssignments = array();
			$empty = array();
			for ($i=1, $outerCount=count($this->reviewAssignments); $i <= $outerCount; $i++) {
				$roundReviewAssignments = $this->reviewAssignments[$i];
				for ($j=0, $innerCount=count($roundReviewAssignments); $j < $innerCount; $j++) {
					if ($roundReviewAssignments[$j]->getReviewId() == $reviewId) {
						array_push($this->removedReviewAssignments, $reviewId);
						$found = true;
					} else {
						array_push($reviewAssignments, $roundReviewAssignments[$j]);
					}
				}
				$this->reviewAssignments[$i] = $reviewAssignments;
				$reviewAssignments = $empty;
			}
		}
		return $found;
	}

	/**
	 * Updates an existing review assignment.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function updateReviewAssignment($reviewAssignment) {
		$reviewAssignments = array();
		$roundReviewAssignments = $this->reviewAssignments[$reviewAssignment->getRound()];
		for ($i=0, $count=count($roundReviewAssignments); $i < $count; $i++) {
			if ($roundReviewAssignments[$i]->getReviewId() == $reviewAssignment->getReviewId()) {
				array_push($reviewAssignments, $reviewAssignment);
			} else {
				array_push($reviewAssignments, $roundReviewAssignments[$i]);
			}
		}
		$this->reviewAssignments[$reviewAssignment->getRound()] = $reviewAssignments;
	}

	/**
	 * Get the submission status. Returns one of the defined constants
	 * (STATUS_INCOMPLETE, STATUS_ARCHIVED, STATUS_PUBLISHED,
	 * STATUS_DECLINED, STATUS_QUEUED_UNASSIGNED, STATUS_QUEUED_REVIEW,
	 * or STATUS_QUEUED_EDITING). Note that this function never returns
	 * a value of STATUS_QUEUED -- the three STATUS_QUEUED_... constants
	 * indicate a queued submission.
	 * NOTE that this code is similar to getSubmissionStatus in
	 * the AuthorSubmission class and changes should be made there as well.
	 */
	function getSubmissionStatus() {
		$status = $this->getStatus();
		if ($status == STATUS_ARCHIVED || $status == STATUS_PUBLISHED ||
		    $status == STATUS_DECLINED) return $status;

		// The submission is STATUS_QUEUED or the author's submission was STATUS_INCOMPLETE.
		if ($this->getSubmissionProgress()) return (STATUS_INCOMPLETE);

		// The submission is STATUS_QUEUED. Find out where it's queued.
		$editAssignments = $this->getEditAssignments();
		if (empty($editAssignments))
			return (STATUS_QUEUED_UNASSIGNED);

		$decisions = $this->getDecisions();
		$decision = array_pop($decisions);
		if (!empty($decision)) {
			$latestDecision = array_pop($decision);
			if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT) {
				return STATUS_QUEUED_EDITING;
			}
		}
		return STATUS_QUEUED_REVIEW;
	}

	/**
	 * Get/Set Methods.
	 */

	/**
	 * Get edit assignments for this article.
	 * @return array
	 */
	function &getEditAssignments() {
		$editAssignments = &$this->getData('editAssignments');
		return $editAssignments;
	}

	/**
	 * Set edit assignments for this article.
	 * @param $editAssignments array
	 */
	function setEditAssignments($editAssignments) {
		return $this->setData('editAssignments', $editAssignments);
	}

	//
	// Review Assignments
	//

	/**
	 * Get review assignments for this article.
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignments($round = null) {
		if ($round == null) {
			return $this->reviewAssignments;
		} else {
			return $this->reviewAssignments[$round];
		}
	}

	/**
	 * Set review assignments for this article.
	 * @param $reviewAssignments array ReviewAssignments
	 */
	function setReviewAssignments($reviewAssignments, $round) {
		return $this->reviewAssignments[$round] = $reviewAssignments;
	}

	/**
	 * Get the IDs of all review assignments removed..
	 * @return array int
	 */
	function &getRemovedReviewAssignments() {
		return $this->removedReviewAssignments;
	}

	//
	// Editor Decisions
	//

	/**
	 * Get editor decisions.
	 * @return array
	 */
	function getDecisions($round = null) {
		if ($round == null) {
			return $this->editorDecisions;
		} else {
			if (isset($this->editorDecisions[$round])) return $this->editorDecisions[$round];
			else return null;
		}
	}

	/**
	 * Set editor decisions.
	 * @param $editorDecisions array
	 * @param $round int
	 */
	function setDecisions($editorDecisions, $round) {
		return $this->editorDecisions[$round] = $editorDecisions;
	}

	// 
	// Files
	//	

	/**
	 * Get submission file for this article.
	 * @return ArticleFile
	 */
	function &getSubmissionFile() {
		$returner =& $this->getData('submissionFile');
		return $returner;
	}

	/**
	 * Set submission file for this article.
	 * @param $submissionFile ArticleFile
	 */
	function setSubmissionFile($submissionFile) {
		return $this->setData('submissionFile', $submissionFile);
	}

	/**
	 * Get revised file for this article.
	 * @return ArticleFile
	 */
	function &getRevisedFile() {
		$returner =& $this->getData('revisedFile');
		return $returner;
	}

	/**
	 * Set revised file for this article.
	 * @param $submissionFile ArticleFile
	 */
	function setRevisedFile($revisedFile) {
		return $this->setData('revisedFile', $revisedFile);
	}

	/**
	 * Get supplementary files for this article.
	 * @return array SuppFiles
	 */
	function &getSuppFiles() {
		$returner =& $this->getData('suppFiles');
		return $returner;
	}

	/**
	 * Set supplementary file for this article.
	 * @param $suppFiles array SuppFiles
	 */
	function setSuppFiles($suppFiles) {
		return $this->setData('suppFiles', $suppFiles);
	}

	/**
	 * Get review file.
	 * @return ArticleFile
	 */
	function &getReviewFile() {
		$returner =& $this->getData('reviewFile');
		return $returner;
	}

	/**
	 * Set review file.
	 * @param $reviewFile ArticleFile
	 */
	function setReviewFile($reviewFile) {
		return $this->setData('reviewFile', $reviewFile);
	}

	/**
	 * Get all copyedit file revisions.
	 * @return array ArticleFiles
	 */
	function getCopyeditFileRevisions() {
		return $this->copyeditFileRevisions;
	}

	/**
	 * Set all copyedit file revisions.
	 * @param $copyeditFileRevisions array ArticleFiles
	 */
	function setCopyeditFileRevisions($copyeditFileRevisions) {
		return $this->copyeditFileRevisions = $copyeditFileRevisions;
	}

	/**
	 * Get all editor file revisions.
	 * @return array ArticleFiles
	 */
	function getEditorFileRevisions($round = null) {
		if ($round == null) {
			return $this->editorFileRevisions;
		} else {
			return $this->editorFileRevisions[$round];
		}
	}

	/**
	 * Set all editor file revisions.
	 * @param $editorFileRevisions array ArticleFiles
	 */
	function setEditorFileRevisions($editorFileRevisions, $round) {
		return $this->editorFileRevisions[$round] = $editorFileRevisions;
	}

	/**
	 * Get all author file revisions.
	 * @return array ArticleFiles
	 */
	function getAuthorFileRevisions($round = null) {
		if ($round == null) {
			return $this->authorFileRevisions;
		} else {
			return $this->authorFileRevisions[$round];
		}
	}

	/**
	 * Set all author file revisions.
	 * @param $authorFileRevisions array ArticleFiles
	 */
	function setAuthorFileRevisions($authorFileRevisions, $round) {
		return $this->authorFileRevisions[$round] = $authorFileRevisions;
	}

	/**
	 * Get post-review file.
	 * @return ArticleFile
	 */
	function &getEditorFile() {
		$returner =& $this->getData('editorFile');
		return $returner;
	}

	/**
	 * Set post-review file.
	 * @param $editorFile ArticleFile
	 */
	function setEditorFile($editorFile) {
		return $this->setData('editorFile', $editorFile);
	}

	/**
	 * Get copyedit file.
	 * @return ArticleFile
	 */
	function &getCopyeditFile() {
		$returner =& $this->getData('copyeditFile');
		return $returner;
	}


	/**
	 * Set copyedit file.
	 * @param $copyeditFile ArticleFile
	 */
	function setCopyeditFile($copyeditFile) {
		return $this->setData('copyeditFile', $copyeditFile);
	}

	/**
	 * Get initial copyedit file.
	 * @return ArticleFile
	 */
	function &getInitialCopyeditFile() {
		$returner =& $this->getData('initialCopyeditFile');
		return $returner;
	}


	/**
	 * Set initial copyedit file.
	 * @param $initialCopyeditFile ArticleFile
	 */
	function setInitialCopyeditFile($initialCopyeditFile) {
		return $this->setData('initialCopyeditFile', $initialCopyeditFile);
	}

	/**
	 * Get editor author copyedit file.
	 * @return ArticleFile
	 */
	function &getEditorAuthorCopyeditFile() {
		$returner =& $this->getData('editorAuthorCopyeditFile');
		return $returner;
	}


	/**
	 * Set editor author copyedit file.
	 * @param $editorAuthorCopyeditFile ArticleFile
	 */
	function setEditorAuthorCopyeditFile($editorAuthorCopyeditFile) {
		return $this->setData('editorAuthorCopyeditFile', $editorAuthorCopyeditFile);
	}

	/**
	 * Get final copyedit file.
	 * @return ArticleFile
	 */
	function &getFinalCopyeditFile() {
		$returner =& $this->getData('finalCopyeditFile');
		return $returner;
	}


	/**
	 * Set final copyedit file.
	 * @param $finalCopyeditFile ArticleFile
	 */
	function setFinalCopyeditFile($finalCopyeditFile) {
		return $this->setData('finalCopyeditFile', $finalCopyeditFile);
	}


	//
	// Review Rounds
	//

	/**
	 * Get review file revision.
	 * @return int
	 */
	function getReviewRevision() {
		return $this->getData('reviewRevision');
	}

	/**
	 * Set review file revision.
	 * @param $reviewRevision int
	 */
	function setReviewRevision($reviewRevision) {
		return $this->setData('reviewRevision', $reviewRevision);
	}

	//
	// Comments
	//

	/**
	 * Get most recent editor decision comment.
	 * @return ArticleComment
	 */
	function getMostRecentEditorDecisionComment() {
		return $this->getData('mostRecentEditorDecisionComment');
	}

	/**
	 * Set most recent editor decision comment.
	 * @param $mostRecentEditorDecisionComment ArticleComment
	 */
	function setMostRecentEditorDecisionComment($mostRecentEditorDecisionComment) {
		return $this->setData('mostRecentEditorDecisionComment', $mostRecentEditorDecisionComment);
	}

	/**
	 * Get most recent copyedit comment.
	 * @return ArticleComment
	 */
	function getMostRecentCopyeditComment() {
		return $this->getData('mostRecentCopyeditComment');
	}

	/**
	 * Set most recent copyedit comment.
	 * @param $mostRecentCopyeditComment ArticleComment
	 */
	function setMostRecentCopyeditComment($mostRecentCopyeditComment) {
		return $this->setData('mostRecentCopyeditComment', $mostRecentCopyeditComment);
	}

	/**
	 * Get most recent layout comment.
	 * @return ArticleComment
	 */
	function getMostRecentLayoutComment() {
		return $this->getData('mostRecentLayoutComment');
	}

	/**
	 * Set most recent layout comment.
	 * @param $mostRecentLayoutComment ArticleComment
	 */
	function setMostRecentLayoutComment($mostRecentLayoutComment) {
		return $this->setData('mostRecentLayoutComment', $mostRecentLayoutComment);
	}

	/**
	 * Get most recent proofread comment.
	 * @return ArticleComment
	 */
	function getMostRecentProofreadComment() {
		return $this->getData('mostRecentProofreadComment');
	}

	/**
	 * Set most recent proofread comment.
	 * @param $mostRecentProofreadComment ArticleComment
	 */
	function setMostRecentProofreadComment($mostRecentProofreadComment) {
		return $this->setData('mostRecentProofreadComment', $mostRecentProofreadComment);
	}

	//
	// Copyeditor Assignment
	//

	/**
	 * Get copyed id.
	 * @return int
	 */
	function getCopyedId() {
		return $this->getData('copyedId');
	}

	/**
	 * Set copyed id.
	 * @param $copyedId int
	 */
	function setCopyedId($copyedId) {
		return $this->setData('copyedId', $copyedId);
	}

	/**
	 * Get copyeditor id.
	 * @return int
	 */
	function getCopyeditorId() {
		return $this->getData('copyeditorId');
	}

	/**
	 * Set copyeditor id.
	 * @param $copyeditorId int
	 */
	function setCopyeditorId($copyeditorId) {
		return $this->setData('copyeditorId', $copyeditorId);
	}

	/**
	 * Get copyeditor of this article.
	 * @return User
	 */
	function &getCopyeditor() {
		$copyEditor = &$this->getData('copyeditor');
		return $copyEditor;
	}

	/**
	 * Set copyeditor of this article.
	 * @param $copyeditor User
	 */
	function setCopyeditor($copyeditor) {
		return $this->setData('copyeditor', $copyeditor);
	}

	/**
	 * Get copyeditor date notified.
	 * @return string
	 */
	function getCopyeditorDateNotified() {
		return $this->getData('copyeditorDateNotified');
	}

	/**
	 * Set copyeditor date notified.
	 * @param $copyeditorDateNotified string
	 */
	function setCopyeditorDateNotified($copyeditorDateNotified) {
		return $this->setData('copyeditorDateNotified', $copyeditorDateNotified);
	}

	/**
	 * Get copyeditor date underway.
	 * @return string
	 */
	function getCopyeditorDateUnderway() {
		return $this->getData('copyeditorDateUnderway');
	}

	/**
	 * Set copyeditor date underway.
	 * @param $copyeditorDateUnderway string
	 */
	function setCopyeditorDateUnderway($copyeditorDateUnderway) {
		return $this->setData('copyeditorDateUnderway', $copyeditorDateUnderway);
	}

	/**
	 * Get copyeditor date completed.
	 * @return string
	 */
	function getCopyeditorDateCompleted() {
		return $this->getData('copyeditorDateCompleted');
	}

	/**
	 * Set copyeditor date completed.
	 * @param $copyeditorDateCompleted string
	 */
	function setCopyeditorDateCompleted($copyeditorDateCompleted) {
		return $this->setData('copyeditorDateCompleted', $copyeditorDateCompleted);
	}

	/**
	 * Get copyeditor date acknowledged.
	 * @return string
	 */
	function getCopyeditorDateAcknowledged() {
		return $this->getData('copyeditorDateAcknowledged');
	}

	/**
	 * Set copyeditor date acknowledged.
	 * @param $copyeditorDateAcknowledged string
	 */
	function setCopyeditorDateAcknowledged($copyeditorDateAcknowledged) {
		return $this->setData('copyeditorDateAcknowledged', $copyeditorDateAcknowledged);
	}

	/**
	 * Get copyeditor date author notified.
	 * @return string
	 */
	function getCopyeditorDateAuthorNotified() {
		return $this->getData('copyeditorDateAuthorNotified');
	}

	/**
	 * Set copyeditor date author notified.
	 * @param $copyeditorDateAuthorNotified string
	 */
	function setCopyeditorDateAuthorNotified($copyeditorDateAuthorNotified) {
		return $this->setData('copyeditorDateAuthorNotified', $copyeditorDateAuthorNotified);
	}

	/**
	 * Get copyeditor date authorunderway.
	 * @return string
	 */
	function getCopyeditorDateAuthorUnderway() {
		return $this->getData('copyeditorDateAuthorUnderway');
	}

	/**
	 * Set copyeditor date author underway.
	 * @param $copyeditorDateAuthorUnderway string
	 */
	function setCopyeditorDateAuthorUnderway($copyeditorDateAuthorUnderway) {
		return $this->setData('copyeditorDateAuthorUnderway', $copyeditorDateAuthorUnderway);
	}	

	/**
	 * Get copyeditor date author completed.
	 * @return string
	 */
	function getCopyeditorDateAuthorCompleted() {
		return $this->getData('copyeditorDateAuthorCompleted');
	}

	/**
	 * Set copyeditor date author completed.
	 * @param $copyeditorDateAuthorCompleted string
	 */
	function setCopyeditorDateAuthorCompleted($copyeditorDateAuthorCompleted) {
		return $this->setData('copyeditorDateAuthorCompleted', $copyeditorDateAuthorCompleted);
	}

	/**
	 * Get copyeditor date author acknowledged.
	 * @return string
	 */
	function getCopyeditorDateAuthorAcknowledged() {
		return $this->getData('copyeditorDateAuthorAcknowledged');
	}

	/**
	 * Set copyeditor date author acknowledged.
	 * @param $copyeditorDateAuthorAcknowledged string
	 */
	function setCopyeditorDateAuthorAcknowledged($copyeditorDateAuthorAcknowledged) {
		return $this->setData('copyeditorDateAuthorAcknowledged', $copyeditorDateAuthorAcknowledged);
	}

	/**
	 * Get copyeditor date final notified.
	 * @return string
	 */
	function getCopyeditorDateFinalNotified() {
		return $this->getData('copyeditorDateFinalNotified');
	}

	/**
	 * Set copyeditor date final notified.
	 * @param $copyeditorDateFinalNotified string
	 */
	function setCopyeditorDateFinalNotified($copyeditorDateFinalNotified) {
		return $this->setData('copyeditorDateFinalNotified', $copyeditorDateFinalNotified);
	}

	/**
	 * Get copyeditor date final underway.
	 * @return string
	 */
	function getCopyeditorDateFinalUnderway() {
		return $this->getData('copyeditorDateFinalUnderway');
	}

	/**
	 * Set copyeditor date final underway.
	 * @param $copyeditorDateFinalUnderway string
	 */
	function setCopyeditorDateFinalUnderway($copyeditorDateFinalUnderway) {
		return $this->setData('copyeditorDateFinalUnderway', $copyeditorDateFinalUnderway);
	}

	/**
	 * Get copyeditor date final completed.
	 * @return string
	 */
	function getCopyeditorDateFinalCompleted() {
		return $this->getData('copyeditorDateFinalCompleted');
	}

	/**
	 * Set copyeditor date final completed.
	 * @param $copyeditorDateFinalCompleted string
	 */
	function setCopyeditorDateFinalCompleted($copyeditorDateFinalCompleted) {
		return $this->setData('copyeditorDateFinalCompleted', $copyeditorDateFinalCompleted);
	}

	/**
	 * Get copyeditor date author acknowledged.
	 * @return string
	 */
	function getCopyeditorDateFinalAcknowledged() {
		return $this->getData('copyeditorDateFinalAcknowledged');
	}

	/**
	 * Set copyeditor date final acknowledged.
	 * @param $copyeditorDateFinalAcknowledged string
	 */
	function setCopyeditorDateFinalAcknowledged($copyeditorDateFinalAcknowledged) {
		return $this->setData('copyeditorDateFinalAcknowledged', $copyeditorDateFinalAcknowledged);
	}

	/**
	 * Get copyeditor initial revision.
	 * @return int
	 */
	function getCopyeditorInitialRevision() {
		return $this->getData('copyeditorInitialRevision');
	}

	/**
	 * Set copyeditor initial revision.
	 * @param $copyeditorInitialRevision int
	 */
	function setCopyeditorInitialRevision($copyeditorInitialRevision)	{
		return $this->setData('copyeditorInitialRevision', $copyeditorInitialRevision);
	}

	/**
	 * Get copyeditor editor/author revision.
	 * @return int
	 */
	function getCopyeditorEditorAuthorRevision() {
		return $this->getData('copyeditorEditorAuthorRevision');
	}

	/**
	 * Set copyeditor editor/author revision.
	 * @param $editorAuthorRevision int
	 */
	function setCopyeditorEditorAuthorRevision($copyeditorEditorAuthorRevision)	{
		return $this->setData('copyeditorEditorAuthorRevision', $copyeditorEditorAuthorRevision);
	}

	/**
	 * Get copyeditor final revision.
	 * @return int
	 */
	function getCopyeditorFinalRevision() {
		return $this->getData('copyeditorFinalRevision');
	}

	/**
	 * Set copyeditor final revision.
	 * @param $copyeditorFinalRevision int
	 */
	function setCopyeditorFinalRevision($copyeditorFinalRevision)	{
		return $this->setData('copyeditorFinalRevision', $copyeditorFinalRevision);
	}

	/**
	 * Get the layout assignment for an article.
	 * @return LayoutAssignment
	 */
	function &getLayoutAssignment() {
		$layoutAssignment = &$this->getData('layoutAssignment');
		return $layoutAssignment;
	}

	/**
	 * Set the layout assignment for an article.
	 * @param $layoutAssignment LayoutAssignment
	 */
	function setLayoutAssignment(&$layoutAssignment) {
		return $this->setData('layoutAssignment', $layoutAssignment);
	}

	/**
	 * Get the galleys for an article.
	 * @return array ArticleGalley
	 */
	function &getGalleys() {
		$galleys = &$this->getData('galleys');
		return $galleys;
	}

	/**
	 * Set the galleys for an article.
	 * @param $galleys array ArticleGalley
	 */
	function setGalleys(&$galleys) {
		return $this->setData('galleys', $galleys);
	}

	/**
	 * Get the proof assignment for an article.
	 * @return ProofAssignment
	 */
	function &getProofAssignment() {
		$proofAssignment = &$this->getData('proofAssignment');
		return $proofAssignment;
	}

	/**
	 * Set the proof assignment for an article.
	 * @param $proofAssignment ProofAssignment
	 */
	function setProofAssignment($proofAssignment) {
		return $this->setData('proofAssignment', $proofAssignment);
	}

	/**
	 * Return array mapping editor decision constants to their locale strings.
	 * (Includes default mapping '' => "Choose One".)
	 * @return array decision => localeString
	 */
	function &getEditorDecisionOptions() {
		static $editorDecisionOptions = array(
			'' => 'common.chooseOne',
			SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
			SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
			SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
		);
		return $editorDecisionOptions;
	}

	/**
	 * Get the CSS class for highlighting this submission in a list, based on status.
	 * @return string
	 */
	function getHighlightClass() {
		$highlightClass = 'highlight';
		$overdueSeconds = 60 * 60 * 24 * 14; // Two weeks

		// Submissions that are not still queued (i.e. published) are not highlighted.
		if ($this->getStatus() != STATUS_QUEUED) return null;

		// Awaiting assignment.
		$editAssignments = $this->getEditAssignments();
		if (empty($editAssignments)) return $highlightClass;

		$journal =& Request::getJournal();
		// Sanity check
		if (!$journal || $journal->getJournalId() != $this->getJournalId()) return null;

		// Check whether it's in review or editing.
		$inEditing = false;
		$decisionsEmpty = true;
		$lastDecisionDate = null;
		$decisions = $this->getDecisions();
		$decision = array_pop($decisions);
		if (!empty($decision)) {
			$latestDecision = array_pop($decision);
			if (is_array($latestDecision)) {
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT) $inEditing = true;
				$decisionsEmpty = false;
				$lastDecisionDate = strtotime($latestDecision['dateDecided']);
			}
		}

		if ($inEditing) {
			// ---
			// --- Highlighting conditions for submissions in editing
			// ---

			// COPYEDITING

			// First round of copyediting
			$dateCopyeditorNotified = $this->getCopyeditorDateNotified() ?
				strtotime($this->getCopyeditorDateNotified()) : 0;
			$dateCopyeditorUnderway = $this->getCopyeditorDateUnderway() ?
				strtotime($this->getCopyeditorDateUnderway()) : 0;
			$dateCopyeditorCompleted = $this->getCopyeditorDateCompleted() ?
				strtotime($this->getCopyeditorDateCompleted()) : 0;
			$dateCopyeditorAcknowledged = $this->getCopyeditorDateAcknowledged() ?
				strtotime($this->getCopyeditorDateAcknowledged()) : 0;
			$dateLastCopyeditor = max($dateCopyeditorNotified, $dateCopyeditorUnderway);

			// Check if the copyeditor is overdue on round 1
			if (	$dateLastCopyeditor &&
				!$dateCopyeditorCompleted &&
				$dateLastCopyeditor + $overdueSeconds < time()
			) return $highlightClass;

			// Check if acknowledgement is overdue for CE round 1
			if ($dateCopyeditorCompleted && !$dateCopyeditorAcknowledged) return $highlightClass;

			// Second round of copyediting
			$dateCopyeditorAuthorNotified = $this->getCopyeditorDateAuthorNotified() ?
				strtotime($this->getCopyeditorDateAuthorNotified()) : 0;
			$dateCopyeditorAuthorUnderway = $this->getCopyeditorDateAuthorUnderway() ?
				strtotime($this->getCopyeditorDateAuthorUnderway()) : 0;
			$dateCopyeditorAuthorCompleted = $this->getCopyeditorDateAuthorCompleted() ?
				strtotime($this->getCopyeditorDateAuthorCompleted()) : 0;
			$dateCopyeditorAuthorAcknowledged = $this->getCopyeditorDateAuthorAcknowledged() ?
				strtotime($this->getCopyeditorDateAuthorAcknowledged()) : 0;
			$dateLastCopyeditorAuthor = max($dateCopyeditorAuthorNotified, $dateCopyeditorAuthorUnderway);

			// Check if acknowledgement is overdue for CE round 2
			if ($dateCopyeditorAuthorCompleted && !$dateCopyeditorAuthorAcknowledged) return $highlightClass;

			// Check if author is overdue on CE round 2
			if (	$dateLastCopyeditorAuthor &&
				!$dateCopyeditorAuthorCompleted &&
				$dateLastCopyeditorAuthor + $overdueSeconds < time()
			) return $highlightClass;

			// Third round of copyediting
			$dateCopyeditorFinalNotified = $this->getCopyeditorDateFinalNotified() ?
				strtotime($this->getCopyeditorDateFinalNotified()) : 0;
			$dateCopyeditorFinalUnderway = $this->getCopyeditorDateFinalUnderway() ?
				strtotime($this->getCopyeditorDateFinalUnderway()) : 0;
			$dateCopyeditorFinalCompleted = $this->getCopyeditorDateFinalCompleted() ?
				strtotime($this->getCopyeditorDateFinalCompleted()) : 0;
			$dateLastCopyeditorFinal = max($dateCopyeditorFinalNotified, $dateCopyeditorUnderway);

			// Check if copyeditor is overdue on round 3
			if (	$dateLastCopyeditorFinal &&
				!$dateCopyeditorFinalCompleted &&
				$dateLastCopyeditorFinal + $overdueSeconds < time()
			) return $highlightClass;

			// Check if acknowledgement is overdue for CE round 3
			if ($dateCopyeditorFinalCompleted && !$dateCopyeditorFinalAcknowledged) return $highlightClass;

			// LAYOUT EDITING
			$layoutAssignment =& $this->getLayoutAssignment();

			$dateLayoutNotified = $layoutAssignment->getDateNotified() ?
				strtotime($layoutAssignment->getDateNotified()) : 0;
			$dateLayoutUnderway = $layoutAssignment->getDateUnderway() ?
				strtotime($layoutAssignment->getDateUnderway()) : 0;
			$dateLayoutCompleted = $layoutAssignment->getDateCompleted() ?
				strtotime($layoutAssignment->getDateCompleted()) : 0;
			$dateLayoutAcknowledged = $layoutAssignment->getDateAcknowledged() ?
				strtotime($layoutAssignment->getDateAcknowledged()) : 0;
			$dateLastLayout = max($dateLayoutNotified, $dateLayoutUnderway);

			// Check if layout editor is overdue
			if (	$dateLastLayout &&
				!$dateLayoutCompleted &&
				$dateLastLayout + $overdueSeconds < time()
			) return $highlightClass;

			// Check if acknowledgement is overdue for layout
			if ($dateLayoutCompleted && !$dateLayoutAcknowledged) return $highlightClass;

			// PROOFREADING
			$proofAssignment =& $this->getProofAssignment();

			// First round of proofreading
			$dateAuthorNotified = $proofAssignment->getDateAuthorNotified() ?
				strtotime($proofAssignment->getDateAuthorNotified()) : 0;
			$dateAuthorUnderway = $proofAssignment->getDateAuthorUnderway() ?
				strtotime($proofAssignment->getDateAuthorUnderway()) : 0;
			$dateAuthorCompleted = $proofAssignment->getDateAuthorCompleted() ?
				strtotime($proofAssignment->getDateAuthorCompleted()) : 0;
			$dateAuthorAcknowledged = $proofAssignment->getDateAuthorAcknowledged() ?
				strtotime($proofAssignment->getDateAuthorAcknowledged()) : 0;
			$dateLastAuthor = max($dateAuthorNotified, $dateAuthorUnderway);

			// Check if the author is overdue on round 1 of proofreading
			if (	$dateLastAuthor &&
				!$dateAuthorCompleted &&
				$dateLastAuthor + $overdueSeconds < time()
			) return $highlightClass;

			// Check if acknowledgement is overdue for proofreading round 1
			if ($dateAuthorCompleted && !$dateAuthorAcknowledged) return $highlightClass;

			// Second round of proofreading
			$dateProofreaderNotified = $proofAssignment->getDateProofreaderNotified() ?
				strtotime($proofAssignment->getDateProofreaderNotified()) : 0;
			$dateProofreaderUnderway = $proofAssignment->getDateProofreaderUnderway() ?
				strtotime($proofAssignment->getDateProofreaderUnderway()) : 0;
			$dateProofreaderCompleted = $proofAssignment->getDateProofreaderCompleted() ?
				strtotime($proofAssignment->getDateProofreaderCompleted()) : 0;
			$dateProofreaderAcknowledged = $proofAssignment->getDateProofreaderAcknowledged() ?
				strtotime($proofAssignment->getDateProofreaderAcknowledged()) : 0;
			$dateLastProofreader = max($dateProofreaderNotified, $dateProofreaderUnderway);

			// Check if acknowledgement is overdue for proofreading round 2
			if ($dateProofreaderCompleted && !$dateProofreaderAcknowledged) return $highlightClass;

			// Check if proofreader is overdue on proofreading round 2
			if (	$dateLastProofreader &&
				!$dateProofreaderCompleted &&
				$dateLastProofreader + $overdueSeconds < time()
			) return $highlightClass;

			// Third round of proofreading
			$dateLayoutEditorNotified = $proofAssignment->getDateLayoutEditorNotified() ?
				strtotime($proofAssignment->getDateLayoutEditorNotified()) : 0;
			$dateLayoutEditorUnderway = $proofAssignment->getDateLayoutEditorUnderway() ?
				strtotime($proofAssignment->getDateLayoutEditorUnderway()) : 0;
			$dateLayoutEditorCompleted = $proofAssignment->getDateLayoutEditorCompleted() ?
				strtotime($proofAssignment->getDateLayoutEditorCompleted()) : 0;
			$dateLastLayoutEditor = max($dateLayoutEditorNotified, $dateCopyeditorUnderway);

			// Check if proofreader is overdue on round 3 of proofreading
			if (	$dateLastLayoutEditor &&
				!$dateLayoutEditorCompleted &&
				$dateLastLayoutEditor + $overdueSeconds < time()
			) return $highlightClass;

			// Check if acknowledgement is overdue for proofreading round 3
			if ($dateLayoutEditorCompleted && !$dateLayoutEditorAcknowledged) return $highlightClass;
		} else {
			// ---
			// --- Highlighting conditions for submissions in review
			// ---
			$reviewAssignments =& $this->getReviewAssignments($this->getCurrentRound());
			if (is_array($reviewAssignments) && !empty($reviewAssignments)) {
				$allReviewsComplete = true;
				foreach ($reviewAssignments as $i => $junk) {
					$reviewAssignment =& $reviewAssignments[$i];

					// If the reviewer has not been notified, highlight.
					if ($reviewAssignment->getDateNotified() === null) return $highlightClass;

					// Check review status.
					if (!$reviewAssignment->getCancelled() && !$reviewAssignment->getDeclined()) {
						if (!$reviewAssignment->getDateCompleted()) $allReviewsComplete = false;

						$dateReminded = $reviewAssignment->getDateReminded() ?
							strtotime($reviewAssignment->getDateReminded()) : 0;
						$dateNotified = $reviewAssignment->getDateNotified() ?
							strtotime($reviewAssignment->getDateNotified()) : 0;
						$dateConfirmed = $reviewAssignment->getDateConfirmed() ?
							strtotime($reviewAssignment->getDateConfirmed()) : 0;

						// Check whether a reviewer is overdue to confirm invitation
						if (	!$reviewAssignment->getDateCompleted() &&
							!$dateConfirmed &&
							!$journal->getSetting('remindForInvite') &&
							max($dateReminded, $dateNotified) + $overdueSeconds < time()
						) return $highlightClass;

						// Check whether a reviewer is overdue to complete review
						if (	!$reviewAssignment->getDateCompleted() &&
							$dateConfirmed &&
							!$journal->getSetting('remindForSubmit') &&
							max($dateReminded, $dateConfirmed) + $overdueSeconds < time()
						) return $highlightClass;
					}

					unset($reviewAssignment);
				}
				// If all reviews are complete but no decision is recorded, highlight.
				if ($allReviewsComplete && $decisionsEmpty) return $highlightClass;

				// If the author's last file upload hasn't been taken into account in
				// the most recent decision or author/editor correspondence, highlight.
				$comment = $this->getMostRecentEditorDecisionComment();
				$commentDate = $comment ? strtotime($comment->getDatePosted()) : 0;
				$authorFileRevisions = $this->getAuthorFileRevisions($this->getCurrentRound());
				$authorFileDate = null;
				if (is_array($authorFileRevisions) && !empty($authorFileRevisions)) {
					$authorFile = array_pop($authorFileRevisions);
					$authorFileDate = strtotime($authorFile->getDateUploaded());
				}
				if (	($lastDecisionDate || $commentDate) &&
					$authorFileDate &&
					$authorFileDate > max($lastDecisionDate, $commentDate)
				) return $highlightClass;
			}
		}
		return null;
	}
}

?>
