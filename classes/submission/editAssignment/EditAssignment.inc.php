<?php

/**
 * EditAssignment.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * EditAssignment class.
 * Describes edit assignment properties.
 *
 * $Id$
 */

class EditAssignment extends DataObject {

	/**
	 * Constructor.
	 */
	function EditAssignment() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get ID of edit assignment.
	 * @return int
	 */
	function getEditId() {
		return $this->getData('editId');
	}
	
	/**
	 * Set ID of edit assignment
	 * @param $editId int
	 */
	function setEditId($editId) {
		return $this->setData('editId', $editId);
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
	 * Get ID of editor.
	 * @return int
	 */
	function getEditorId() {
		return $this->getData('editorId');
	}
	
	/**
	 * Set ID of editor.
	 * @param $editorId int
	 */
	function setEditorId($editorId) {
		return $this->setData('editorId', $editorId);
	}
	
	/**
	 * Get full name of editor.
	 * @return string
	 */
	function getEditorFullName() {
		return $this->getData('editorFullName');
	}
	
	/**
	 * Set full name of editor.
	 * @param $editorFullName string
	 */
	function setEditorFullName($editorFullName) {
		return $this->setData('editorFullName', $editorFullName);
	}
	
	/**
	 * Get email of editor.
	 * @return string
	 */
	function getEditorEmail() {
		return $this->getData('editorEmail');
	}
	
	/**
	 * Set full name of editor.
	 * @param $editorEmail string
	 */
	function setEditorEmail($editorEmail) {
		return $this->setData('editorEmail', $editorEmail);
	}	

	/**
	 * Get editor comments.
	 * @return string
	 */
	function getComments() {
		return $this->getData('comments');
	}
	
	/**
	 * Set editor comments.
	 * @param $comments string
	 */
	function setComments($comments) {
		return $this->setData('comments', $comments);
	}
	
	/**
	 * Get the editors's notified date.
	 * @return string
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}
	
	/**
	 * Set the editors's notified date.
	 * @param $dateNotified string
	 */
	function setDateNotified($dateNotified) {
		return $this->setData('dateNotified', $dateNotified);
	}
	
	/**
	 * Get the editors's completed date.
	 * @return string
	 */
	function getDateCompleted() {
		return $this->getData('dateCompleted');
	}
	
	/**
	 * Set the editor's completed date.
	 * @param $dateCompleted string
	 */
	function setDateCompleted($dateCompleted) {
		return $this->setData('dateCompleted', $dateCompleted);
	}
	
	/**
	 * Get the reviewer's acknowledged date.
	 * @return string
	 */
	function getDateAcknowledged() {
		return $this->getData('dateAcknowledged');
	}
	
	/**
	 * Set the reviewer's acknowledged date.
	 * @param $dateAcknowledged string
	 */
	function setDateAcknowledged($dateAcknowledged) {
		return $this->setData('dateAcknowledged', $dateAcknowledged);
	}
	
	/**
	 * Get the replaced value.
	 * @return boolean
	 */
	function getReplaced() {
		return $this->getData('replaced');
	}
	
	/**
	 * Set the reviewer's replaced value.
	 * @param $replaced boolean
	 */
	function setReplaced($replaced) {
		return $this->setData('replaced', $replaced);
	}
}

?>
