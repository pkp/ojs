<?php

/**
 * ReviewerSubmission.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * ReviewerSubmission class.
 *
 * $Id$
 */

class ReviewerSubmission extends Article {

	/**
	 * Constructor.
	 */
	function ReviewerSubmission() {
		parent::Article();
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
	 * Get review assignment for this article.
	 * @return ReviewAssignment
	 */
	function &getReviewAssignment() {
		return $this->getData('reviewAssignment');
	}
	
	/**
	 * Set review assignment for this article.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function setReviewAssignment($reviewAssignment) {
		return $this->setData('reviewAssignment', $reviewAssignment);
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
