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
		
		array_push($this->reviewAssignments, $reviewAssignment);
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
			for ($i=0, $count=count($this->reviewAssignments); $i < $count; $i++) {
				if ($this->reviewAssignments[$i]->getReviewId() == $reviewId) {
					array_push($this->removedReviewAssignments, $reviewId);
					$found = true;
				} else {
					array_push($reviewAssignments, $this->reviewAssignments[$i]);
				}
			}
			$this->reviewAssignments = $reviewAssignments;
		}
		return $found;
	}
	
	/**
	 * Updates an existing review assignment.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function updateReviewAssignment($reviewAssignment) {
		$reviewAssignments = array();
		for ($i=0, $count=count($this->reviewAssignments); $i < $count; $i++) {
			if ($this->reviewAssignments[$i]->getReviewId() == $reviewAssignment->getReviewId()) {
				array_push($reviewAssignments, $reviewAssignment);
			} else {
				array_push($reviewAssignments, $this->reviewAssignments[$i]);
			}
		}
		$this->reviewAssignments = $reviewAssignments;
	}
	
	/**
	 * Get/Set Methods.
	 */
	 
	/**
	 * Get edit id.
	 * @return int
	 */
	function getEditId() {
		return $this->getData('editId');
	}
	
	/**
	 * Set edit id.
	 * @param $editId int
	 */
	function setEditId($editId)
	{
		return $this->setData('editId', $editId);
	}
	
	/**
	 * Get editor id.
	 * @return int
	 */
	function getEditorId() {
		return $this->getData('editorId');
	}
	
	/**
	 * Set editor id.
	 * @param $editorId int
	 */
	function setEditorId($editorId)
	{
		return $this->setData('editorId', $editorId);
	}
	
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
	 * Get review assignments for this article.
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignments() {
		return $this->reviewAssignments;
	}
	
	/**
	 * Set review assignments for this article.
	 * @param $reviewAssignments array ReviewAssignments
	 */
	function setReviewAssignments($reviewAssignments) {
		return $this->reviewAssignments = $reviewAssignments;
	}
	
	/**
	 * Get the IDs of all review assignments removed..
	 * @return array int
	 */
	function &getRemovedReviewAssignments() {
		return $this->removedReviewAssignments;
	}
	
	/**
	 * Get comments.
	 * @return string
	 */
	function getComments() {
		return $this->getData('comments');
	}
	
	/**
	 * Set comments.
	 * @param $comments string
	 */
	function setComments($comments)
	{
		return $this->setData('comments', $comments);
	}
	
	/**
	 * Get recommendation.
	 * @return User
	 */
	function getRecommendation() {
		return $this->getData('recommendation');
	}
	
	/**
	 * Set recommendation.
	 * @param $recommendation int
	 */
	function setRecommendation($recommendation)
	{
		return $this->setData('recommendation', $recommendation);
	}
	
	/**
	 * Get date notified.
	 * @return string
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}
	
	/**
	 * Set date notified.
	 * @param $dateNotified string
	 */
	function setDateNotified($dateNotified)
	{
		return $this->setData('dateNotified', $dateNotified);
	}
	
	/**
	 * Get date completed.
	 * @return string
	 */
	function getDateCompleted() {
		return $this->getData('dateCompleted');
	}
	
	/**
	 * Set date completed.
	 * @param $dateCompleted string
	 */
	function setDateCompleted($dateCompleted)
	{
		return $this->setData('dateCompleted', $dateCompleted);
	}
	
	/**
	 * Get date acknowledged.
	 * @return string
	 */
	function getDateAcknowledged() {
		return $this->getData('dateAcknowledged');
	}
	
	/**
	 * Set date acknowledged.
	 * @param $dateAcknowledged string
	 */
	function setDateAcknowledged($dateAcknowledged)
	{
		return $this->setData('dateAcknowledged', $dateAcknowledged);
	}
	
	
	/**
	 * Get post-review file id.
	 * @return int
	 */
	function getPostReviewFileId() {
		return $this->getData('postReviewFileId');
	}
	
	/**
	 * Set post-review file id.
	 * @param $postReviewFileId int
	 */
	function setPostReviewFileId($postReviewFileId) {
		return $this->setData('postReviewFileId', $postReviewFileId);
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
	 * Get post-review file.
	 * @return ArticleFile
	 */
	function getPostReviewFile() {
		return $this->getData('postReviewFile');
	}
	
	/**
	 * Set post-review file.
	 * @param $postReviewFile ArticleFile
	 */
	function setPostReviewFile($postReviewFile) {
		return $this->setData('postReviewFile', $postReviewFile);
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
	function setCopyeditorDateFinalCompleted($copyeditorDateFinalCompleted)
	{
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
	function setCopyeditorDateFinalAcknowledged($copyeditorDateFinalAcknowledged)
	{
		return $this->setData('copyeditorDateFinalAcknowledged', $copyeditorDateFinalAcknowledged);
	}
}

?>
