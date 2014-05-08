<?php

/**
 * @file plugins/generic/objectsForReview/classes/ReviewObjectType.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewObjectType
 * @ingroup plugins_generic_objectsForReview
 * @see ReviewObjectTypeDAO
 *
 * @brief Basic class describing a review object type.
 *
 */


class ReviewObjectType extends DataObject {
	/**
	 * Constructor.
	 */
	function ReviewObjectType() {
		parent::DataObject();
	}

	/**
	 * Get localized type name.
	 * @return string
	 */
	function getLocalizedName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get localized description.
	 * @return string
	 */
	function getLocalizedDescription() {
		return $this->getLocalizedData('description');
	}

	//
	// Get/set methods
	//
	/**
	 * Get context ID.
	 * @return int
	 */
	function getContextId() {
		return $this->getData('contextId');
	}

	/**
	 * Set context ID.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setData('contextId', $contextId);
	}

	/**
	 * Get active flag.
	 * @return int
	 */
	function getActive() {
		return $this->getData('active');
	}

	/**
	 * Set active flag.
	 * @param $active int
	 */
	function setActive($active) {
		return $this->setData('active', $active);
	}

	/**
	 * Get key.
	 * @return string
	 */
	function getKey() {
		return $this->getData('key');
	}

	/**
	 * Set key.
	 * @param $key string
	 */
	function setKey($key) {
		return $this->setData('key', $key);
	}

	/**
	 * Get name.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set name.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get description.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set description.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}

}

?>
