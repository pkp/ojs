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
}

?>
