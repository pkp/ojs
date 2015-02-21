<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReviewPerson.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewPerson
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReviewPersonDAO
 *
 * @brief Object for review person metadata class.
 */


class ObjectForReviewPerson extends DataObject {
	/**
	 * Constructor.
	 */
	function ObjectForReviewPerson() {
		parent::DataObject();
	}

	/**
	 * Get person's complete name.
	 * Includes first name, middle name, and last name (if applicable).
	 * @return string
	 */
	function getFullName() {
		return $this->getData('firstName') . ' ' . ($this->getData('middleName') != '' ? $this->getData('middleName') . ' ' : '') . $this->getData('lastName');
	}

	//
	// Get/set methods
	//
	/**
	 * Get object for review ID.
	 * @return int
	 */
	function getObjectId() {
		return $this->getData('objectId');
	}

	/**
	 * Set object for review ID.
	 * @param $objectId int
	 */
	function setObjectId($objectId) {
		return $this->setData('objectId', $objectId);
	}

	/**
	 * Get role.
	 * @return string
	 */
	function getRole() {
		return $this->getData('role');
	}

	/**
	 * Set role.
	 * @param $role int
	 */
	function setRole($role)	{
		return $this->setData('role', $role);
	}

	/**
	 * Get first name.
	 * @return string
	 */
	function getFirstName() {
		return $this->getData('firstName');
	}

	/**
	 * Set first name.
	 * @param $firstName string
	 */
	function setFirstName($firstName) {
		return $this->setData('firstName', $firstName);
	}

	/**
	 * Get middle name.
	 * @return string
	 */
	function getMiddleName() {
		return $this->getData('middleName');
	}

	/**
	 * Set middle name.
	 * @param $middleName string
	 */
	function setMiddleName($middleName) {
		return $this->setData('middleName', $middleName);
	}

	/**
	 * Get last name.
	 * @return string
	 */
	function getLastName() {
		return $this->getData('lastName');
	}

	/**
	 * Set last name.
	 * @param $lastName string
	 */
	function setLastName($lastName) {
		return $this->setData('lastName', $lastName);
	}

	/**
	 * Get sequence of the person in the object's for reivew person list.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of the person in the object's for review person list.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

}

?>
