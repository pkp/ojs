<?php

/**
 * LayoutEditorSubmission.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutEditor
 *
 * LayoutEditorSubmission class.
 * Describes a layout editor's view of a submission
 *
 * $Id$
 */

class LayoutEditorSubmission extends Article {

	/**
	 * Constructor.
	 */
	function LayoutEditorSubmission() {
		parent::Article();
	}
	
	//
	// Get/set methods
	//
	
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
	
	
	// FIXME These should probably be in an abstract "Submission" base class
	
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
