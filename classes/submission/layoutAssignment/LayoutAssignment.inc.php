<?php

/**
 * LayoutAssignment.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutAssignment
 *
 * LayoutAssignment class.
 * Describes layout editing assignments.
 *
 * $Id$
 */

class LayoutAssignment extends DataObject {

	/**
	 * Constructor.
	 */
	function LayoutAssignment() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get ID of layout assignment.
	 * @return int
	 */
	function getLayoutId() {
		return $this->getData('layoutId');
	}
	
	/**
	 * Set ID of layout assignment
	 * @param $layoutId int
	 */
	function setLayoutId($layoutId) {
		return $this->setData('layoutId', $layoutId);
	}
	
	/**
	 * Get ID of article.
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}
	
	/**
	 * Set ID of article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}
	
	/**
	 * Get user ID of layout editor.
	 * @return int
	 */
	function getEditorId() {
		return $this->getData('editorId');
	}
	
	/**
	 * Set user ID of layout editor.
	 * @param $editorId int
	 */
	function setEditorId($editorId) {
		return $this->setData('editorId', $editorId);
	}
	
	/**
	 * Get full name of layout editor.
	 * @return string
	 */
	function getEditorFullName() {
		return $this->getData('editorFullName');
	}
	
	/**
	 * Set full name of layout editor.
	 * @param $editorFullName string
	 */
	function setEditorFullName($editorFullName) {
		return $this->setData('editorFullName', $editorFullName);
	}
	
	/**
	 * Get email of layout editor.
	 * @return string
	 */
	function getEditorEmail() {
		return $this->getData('editorEmail');
	}
	
	/**
	 * Set email of layout editor.
	 * @param $editorEmail string
	 */
	function setEditorEmail($editorEmail) {
		return $this->setData('editorEmail', $editorEmail);
	}
	
	/**
	 * Get the assignment requested by date.
	 * @return string
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}
	
	/**
	 * Set the assignment requested by date.
	 * @param $dateNotified string
	 */
	function setDateNotified($dateNotified) {
		return $this->setData('dateNotified', $dateNotified);
	}
	
	/**
	 * Get the assignment underway date.
	 * @return string
	 */
	function getDateUnderway() {
		return $this->getData('dateUnderway');
	}
	
	/**
	 * Set the assignment underway date.
	 * @param $dateUnderway string
	 */
	function setDateUnderway($dateUnderway) {
		return $this->setData('dateUnderway', $dateUnderway);
	}
	
	/**
	 * Get the assignment completion date.
	 * @return string
	 */
	function getDateCompleted() {
		return $this->getData('dateCompleted');
	}
	
	/**
	 * Set the assignment completion date.
	 * @param $dateCompleted string
	 */
	function setDateCompleted($dateCompleted) {
		return $this->setData('dateCompleted', $dateCompleted);
	}
	
	/**
	 * Get the assignment acknowledgement date.
	 * @return string
	 */
	function getDateAcknowledged() {
		return $this->getData('dateAcknowledged');
	}
	
	/**
	 * Set the assignment acknowledgement date.
	 * @param $dateAcknowledged string
	 */
	function setDateAcknowledged($dateAcknowledged) {
		return $this->setData('dateAcknowledged', $dateAcknowledged);
	}
	
	/**
	 * Get ID of the layout file.
	 * @return int
	 */
	function getLayoutFileId() {
		return $this->getData('layoutFileId');
	}
	
	/**
	 * Set ID of the layout file.
	 * @param $layoutFileId int
	 */
	function setLayoutFileId($layoutFileId) {
		return $this->setData('layoutFileId', $layoutFileId);
	}
	
	/**
	 * Get layout file.
	 * @return ArticleFile
	 */
	function getLayoutFile() {
		return $this->getData('layoutFile');
	}
	
	/**
	 * Set layout file.
	 * @param $layoutFile ArticleFile
	 */
	function setLayoutFile($layoutFile) {
		return $this->setData('layoutFile', $layoutFile);
	}

}

?>
