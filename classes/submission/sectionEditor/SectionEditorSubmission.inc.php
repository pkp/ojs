<?php

/**
 * SectionEditorSubmission.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * SectionEditorSubmission class.
 *
 * $Id$
 */

class SectionEditorSubmission extends Article {

	/** @var array ReviewAssignments of this article */
	var $reviewAssignments;

	/** @var array IDs of ReviewAssignments removed from this article */
	var $removedReviewAssignments;

	/** @var array the editor decisions of this article */
	var $editorDecisions;
	
	/** @var array the replaced editors of this article */
	var $replacedEditors;

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
		$this->replacedEditors = array();
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
		
		$roundReviewAssignments = $this->reviewAssignments[$reviewAssignment->getRound()];
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
	}
	
	/**
	 * Add a replaced editor.
	 * @param $replacedEditor array
	 */
	function addReplacedEditor($replacedEditor) {
		array_push($this->replacedEditors, $replacedEditor);
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
	 * Get/Set Methods.
	 */
	 
	/**
	 * Get editor of this article.
	 * @return User
	 */
	function &getEditor() {
		return $this->getData('editor');
	}
	
	/**
	 * Set editor of this article.
	 * @param $editor User
	 */
	function setEditor($editor) {
		return $this->setData('editor', $editor);
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
			return $this->editorDecisions[$round];
		}
	}
	
	/**
	 * Set editor decisions.
	 * @param $editorDecisions array
	 * @param $round int
	 */
	function setDecisions($editorDecisions, $round) {
		$this->stampStatusModified();
		return $this->editorDecisions[$round] = $editorDecisions;
	}

	//
	// Replaced Editors
	//
	
	/**
	 * Get replaced editors.
	 * @return array
	 */
	function getReplacedEditors() {
		return $this->replacedEditors;
	}
	
	/**
	 * Set replacedEditors.
	 * @param $replacedEditors array
	 */
	function setReplacedEditors($replacedEditors) {
		return $this->replacedEditors = $replacedEditors;
	}
	
	// 
	// Files
	//	

	/**
	 * Get submission file for this article.
	 * @return ArticleFile
	 */
	function getSubmissionFile() {
		return $this->getData('submissionFile');
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
	function getRevisedFile() {
		return $this->getData('revisedFile');
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
	function getSuppFiles() {
		return $this->getData('suppFiles');
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
	function getReviewFile() {
		return $this->getData('reviewFile');
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
	function getEditorFile() {
		return $this->getData('editorFile');
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
	function getCopyeditFile() {
		return $this->getData('copyeditFile');
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
	function getInitialCopyeditFile() {
		return $this->getData('initialCopyeditFile');
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
	function getEditorAuthorCopyeditFile() {
		return $this->getData('editorAuthorCopyeditFile');
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
	function getFinalCopyeditFile() {
		return $this->getData('finalCopyeditFile');
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
	// Logs
	//
	
	/**
	 * Get email logs.
	 * @return array ArticleEmailLogEntrys
	 */
	function getEmailLogs() {
		return $this->getData('emailLogs');
	}
	
	/**
	 * Set email logs.
	 * @param $logs array ArticleEmailLogEntrys
	 */
	function setEmailLogs($emailLogs) {
		return $this->setData('emailLogs', $emailLogs);
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
		return $this->getData('copyeditor');
	}
	
	/**
	 * Set copyeditor of this article.
	 * @param $copyeditor User
	 */
	function setCopyeditor($copyeditor) {
		return $this->setData('copyeditor', $copyeditor);
	}	
	
	/**
	 * Get copyeditor comments.
	 * @return string
	 */
	function getCopyeditorComments() {
		return $this->getData('copyeditorComments');
	}
	
	/**
	 * Set copyeditor comments.
	 * @param $copyeditorComments string
	 */
	function setCopyeditorComments($copyeditorComments) {
		return $this->setData('copyeditorComments', $copyeditorComments);
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
		return $this->getData('layoutAssignment');
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
		return $this->getData('galleys');
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
	function getProofAssignment() {
		return $this->getData('proofAssignment');
	}
	
	/**
	 * Set the proof assignment for an article.
	 * @param $proofAssignment ProofAssignment
	 */
	function setProofAssignment($proofAssignment) {
		return $this->setData('proofAssignment', $proofAssignment);
	}

}

?>
