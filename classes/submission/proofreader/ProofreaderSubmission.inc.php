<?php

/**
 * ProofreaderSubmission.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.proofreader
 *
 * ProofreaderSubmission class.
 * Describes a proofreader's view of a submission
 *
 * $Id$
 */

class ProofreaderSubmission extends Article {

	/**
	 * Constructor.
	 */
	function ProofreaderSubmission() {
		parent::Article();
	}
	
	//
	// Get/set methods
	//
	
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
	
	/**
	 * Get proof assignment.
	 * @return proofAssignment object
	 */
	function getProofAssignment() {
		return $this->getData('proofAssignment');
	}

	/**
	 * Set proof assignment.
	 * @param $proofAssignment
	 */
	function setProofAssignment($proofAssignment) {
		return $this->setData('proofAssignment', $proofAssignment);
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
}

?>
