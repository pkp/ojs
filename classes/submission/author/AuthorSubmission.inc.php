<?php

/**
 * AuthorSubmission.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * AuthorSubmission class.
 *
 * $Id$
 */

class AuthorSubmission extends Article {

	/** @var array ReviewAssignments of this article */
	var $reviewAssignments;

	/** @var array the editor decisions of this article */
	var $editorDecisions;
	
	/** @var array the revisions of the author file */
	var $authorFileRevisions;

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
	 * Get editor/author revision file.
	 * (The file to be reviewed in the Editor/Author Review stage.)
	 * @return ArticleFile
	 */
	function getEditorAuthorRevisionFile() {
		return $this->getData('editorAuthorRevisionFile');
	}
	
	/**
	 * Set editor/author revision file.
	 * (The file to be reviewed in the Editor/Author Review stage.)
	 * @param $editorAuthorRevisionFile ArticleFile
	 */
	function setEditorAuthorRevisionFile($editorAuthorRevisionFile) {
		return $this->setData('editorAuthorRevisionFile', $editorAuthorRevisionFile);
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
	 * Get copyedit revision.
	 * @return int
	 */
	function getCopyeditRevision() {
		return $this->getData('copyeditRevision');
	}
	
	/**
	 * Set copyedit revision.
	 * @param $copyeditorRevision int
	 */
	function setCopyeditRevision($copyeditRevision)
	{
		return $this->setData('copyeditRevision', $copyeditRevision);
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
	function setCopyeditorComments($copyeditorComments)
	{
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
	function setCopyeditorDateNotified($copyeditorDateNotified)
	{
		return $this->setData('copyeditorDateNotified', $copyeditorDateNotified);
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
	function setCopyeditorDateAuthorNotified($copyeditorDateAuthorNotified)
	{
		return $this->setData('copyeditorDateAuthorNotified', $copyeditorDateAuthorNotified);
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
	function setCopyeditorDateFinalNotified($copyeditorDateFinalNotified)
	{
		return $this->setData('copyeditorDateFinalNotified', $copyeditorDateFinalNotified);
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
	 * Get copyeditor replaced.
	 * @return boolean
	 */
	function getCopyeditorReplaced() {
		return $this->getData('copyeditorReplaced');
	}
	
	/**
	 * Set copyeditor replaced.
	 * @param $copyeditorReplaced boolean
	 */
	function setCopyeditorReplaced($copyeditorReplaced)
	{
		return $this->setData('copyeditorReplaced', $copyeditorReplaced);
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
}

?>
