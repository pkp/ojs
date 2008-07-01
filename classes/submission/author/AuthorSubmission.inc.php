<?php

/**
 * @file classes/submission/author/AuthorSubmission.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmission
 * @ingroup submission
 * @see AuthorSubmissionDAO
 *
 * @brief AuthorSubmission class.
 */

// $Id$


import('article.Article');

class AuthorSubmission extends Article {

	/** @var array ReviewAssignments of this article */
	var $reviewAssignments;

	/** @var array the editor decisions of this article */
	var $editorDecisions;

	/** @var array the revisions of the author file */
	var $authorFileRevisions;

	/** @var array the revisions of the editor file */
	var $editorFileRevisions;

	/** @var array the revisions of the author copyedit file */
	var $copyeditFileRevisions;

	/**
	 * Constructor.
	 */
	function AuthorSubmission() {
		parent::Article();
		$this->reviewAssignments = array();
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

	/**
	 * Add a review assignment for this article.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function addReviewAssignment($reviewAssignment) {
		if ($reviewAssignment->getArticleId() == null) {
			$reviewAssignment->setArticleId($this->getArticleId());
		}

		array_push($this->reviewAssignments, $reviewAssignment);
	}

	/**
	 * Remove a review assignment.
	 * @param $reviewId ID of the review assignment to remove
	 * @return boolean review assignment was removed
	 */
	function removeReviewAssignment($reviewId) {
		$reviewAssignments = array();
		$found = false;
		for ($i=0, $count=count($this->reviewAssignments); $i < $count; $i++) {
			if ($this->reviewAssignments[$i]->getReviewId() == $reviewId) {
				$found = true;
			} else {
				array_push($reviewAssignments, $this->reviewAssignments[$i]);
			}
		}
		$this->reviewAssignments = $reviewAssignments;

		return $found;
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
			// Return an array of arrays of review assignments
			return $this->reviewAssignments;
		} else {
			// Return an array of review assignments for the specified round
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
			return $this->editorDecisions[$round];
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

	/**
	 * Get the submission status. Returns one of the defined constants
	 * (STATUS_INCOMPLETE, STATUS_ARCHIVED, STATUS_PUBLISHED,
	 * STATUS_DECLINED, STATUS_QUEUED_UNASSIGNED, STATUS_QUEUED_REVIEW,
	 * or STATUS_QUEUED_EDITING). Note that this function never returns
	 * a value of STATUS_QUEUED -- the three STATUS_QUEUED_... constants
	 * indicate a queued submission. NOTE that this code is similar to
	 * getSubmissionStatus in the SectionEditorSubmission class and
	 * changes here should be propagated.
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
			if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
				return STATUS_QUEUED_EDITING;
			}
		}
		return STATUS_QUEUED_REVIEW;
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
	function setCopyedId($copyedId)
	{
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
	function setCopyeditorId($copyeditorId)
	{
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
	function setCopyeditorDateNotified($copyeditorDateNotified)
	{
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
	function setCopyeditorDateCompleted($copyeditorDateCompleted)
	{
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
	function setCopyeditorDateAcknowledged($copyeditorDateAcknowledged)
	{
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
	function setCopyeditorDateAuthorCompleted($copyeditorDateAuthorCompleted)
	{
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
	function setCopyeditorDateAuthorAcknowledged($copyeditorDateAuthorAcknowledged)
	{
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
	 * Get copyeditor date finak completed.
	 * @return string
	 */
	function getCopyeditorDateFinalCompleted() {
		return $this->getData('copyeditorDateFinalCompleted');
	}

	/**
	 * Set copyeditor date final completed.
	 * @param $copyeditorDateFinalCompleted string
	 */
	function setCopyeditorDateFinalCompleted($copyeditorDateFinalCompleted)
	{
		return $this->setData('copyeditorDateFinalCompleted', $copyeditorDateFinalCompleted);
	}

	/**
	 * Get copyeditor date final acknowledged.
	 * @return string
	 */
	function getCopyeditorDateFinalAcknowledged() {
		return $this->getData('copyeditorDateFinalAcknowledged');
	}

	/**
	 * Set copyeditor date final acknowledged.
	 * @param $copyeditorDateFinalAcknowledged string
	 */
	function setCopyeditorDateFinalAcknowledged($copyeditorDateFinalAcknowledged)
	{
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
	 * Get layout assignment.
	 * @return layoutAssignment object
	 */
	function &getLayoutAssignment() {
		$layoutAssignment = &$this->getData('layoutAssignment');
		return $layoutAssignment;
	}

	/**
	 * Set layout assignment.
	 * @param $layoutAssignment
	 */
	function setLayoutAssignment($layoutAssignment) {
		return $this->setData('layoutAssignment', $layoutAssignment);
	}

	/**
	 * Get proof assignment.
	 * @return proofAssignment object
	 */
	function &getProofAssignment() {
		$proofAssignment = &$this->getData('proofAssignment');
		return $proofAssignment;
	}

	/**
	 * Set proof assignment.
	 * @param $proofAssignment
	 */
	function setProofAssignment($proofAssignment) {
		return $this->setData('proofAssignment', $proofAssignment);
	}
}

?>
