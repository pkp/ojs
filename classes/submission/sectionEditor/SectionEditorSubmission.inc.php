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
	
}

?>
