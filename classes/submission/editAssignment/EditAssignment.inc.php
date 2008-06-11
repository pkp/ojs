<?php

/**
 * @file EditAssignment.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 * @class EditAssignment
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
	 * Get flag indicating whether this section editor can review this article. (Irrelevant if this is an editor.)
	 * @return boolean
	 */
	function getCanReview() {
		return $this->getData('canReview');
	}

	/**
	 * Set flag indicating whether this section editor can review this article. (Irrelevant if this is an editor.)
	 * @param $canReview boolean
	 */
	function setCanReview($canReview) {
		return $this->setData('canReview', $canReview);
	}

	/**
	 * Get flag indicating whether this section editor can edit this article. (Irrelevant if this is an editor.)
	 * @return boolean
	 */
	function getCanEdit() {
		return $this->getData('canEdit');
	}

	/**
	 * Set flag indicating whether this section editor can edit this article. (Irrelevant if this is an editor.)
	 * @param $canEdit boolean
	 */
	function setCanEdit($canEdit) {
		return $this->setData('canEdit', $canEdit);
	}

	/**
	 * Get flag indicating whether this entry is for an editor or a section editor.
	 * @return boolean
	 */
	function getIsEditor() {
		return $this->getData('isEditor');
	}

	/**
	 * Set flag indicating whether this entry is for an editor or a section editor.
	 * @param $isEditor boolean
	 */
	function setIsEditor($isEditor) {
		return $this->setData('isEditor', $isEditor);
	}

	/**
	 * Get date editor notified.
	 * @return timestamp
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}

	/**
	 * Set date editor notified.
	 * @param $dateNotified timestamp
	 */
	function setDateNotified($dateNotified) {
		return $this->setData('dateNotified', $dateNotified);
	}

	/**
	 * Get date editor underway.
	 * @return timestamp
	 */
	function getDateUnderway() {
		return $this->getData('dateUnderway');
	}

	/**
	 * Set date editor underway.
	 * @param $dateUnderway timestamp
	 */
	function setDateUnderway($dateUnderway) {
		return $this->setData('dateUnderway', $dateUnderway);
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
	 * Get first name of editor.
	 * @return string
	 */
	function getEditorFirstName() {
		return $this->getData('editorFirstName');
	}

	/**
	 * Set first name of editor.
	 * @param $editorFirstName string
	 */
	function setEditorFirstName($editorFirstName) {
		return $this->setData('editorFirstName', $editorFirstName);
	}

	/**
	 * Get last name of editor.
	 * @return string
	 */
	function getEditorLastName() {
		return $this->getData('editorLastName');
	}

	/**
	 * Set last name of editor.
	 * @param $editorLastName string
	 */
	function setEditorLastName($editorLastName) {
		return $this->setData('editorLastName', $editorLastName);
	}

	/**
	 * Get initials of editor.
	 * @return string
	 */
	function getEditorInitials() {
		if ($this->getData('editorInitials')) {
			return $this->getData('editorInitials');
		} else {
			return substr($this->getEditorFirstName(), 0, 1) . substr($this->getEditorLastName(), 0, 1);
		}
	}

	/**
	 * Set initials of editor.
	 * @param $editorInitials string
	 */
	function setEditorInitials($editorInitials) {
		return $this->setData('editorInitials', $editorInitials);
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
}

?>
