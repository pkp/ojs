<?php

/**
 * @defgroup security Security
 * Concerns related to security, such as access keys, user groups, and roles.
 */

/**
 * @file classes/security/AccessKey.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AccessKey
 * @ingroup security
 * @see AccessKeyDAO
 *
 * @brief AccessKey class.
 */

class AccessKey extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//
	/**
	 * Get context.
	 * @return string
	 */
	function getContext() {
		return $this->getData('context');
	}

	/**
	 * Set context.
	 * @param $context string
	 */
	function setContext($context) {
		$this->setData('context', $context);
	}

	/**
	 * Get key hash.
	 * @return string
	 */
	function getKeyHash() {
		return $this->getData('keyHash');
	}

	/**
	 * Set key hash.
	 * @param $keyHash string
	 */
	function setKeyHash($keyHash) {
		$this->setData('keyHash', $keyHash);
	}

	/**
	 * Get user ID.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set user ID.
	 * @param $userId int
	 */
	function setUserId($userId)
	{
		$this->setData('userId', $userId);
	}

	/**
	 * Get associated ID.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set associated ID.
	 * @param $assocId int
	 */
	function setAssocId($assocId)
	{
		$this->setData('assocId', $assocId);
	}

	/**
	 * Get expiry date.
	 * @return string
	 */
	function getExpiryDate() {
		return $this->getData('expiryDate');
	}

	/**
	 * Set expiry date.
	 * @param $expiryDate string
	 */
	function setExpiryDate($expiryDate) {
		$this->setData('expiryDate', $expiryDate);
	}
}

?>
