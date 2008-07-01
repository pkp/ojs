<?php

/**
 * @defgroup security
 */
 

/**
 * @file classes/security/AccessKey.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AccessKey
 * @ingroup security
 * @see AccessKeyDAO
 *
 * @brief AccessKey class.
 */

// $Id$


class AccessKey extends DataObject {

	function AccessKey() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the key.
	 * @return int
	 */
	function getAccessKeyId() {
		return $this->getData('accessKeyId');
	}

	/**
	 * Set the ID of the access key.
	 * @param $accessKeyId int
	 */
	function setAccessKeyId($accessKeyId) {
		return $this->setData('accessKeyId', $accessKeyId);
	}

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
		return $this->setData('context', $context);
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
		return $this->setData('keyHash', $keyHash);
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
		return $this->setData('userId', $userId);
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
		return $this->setData('assocId', $assocId);
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
		return $this->setData('expiryDate', $expiryDate);
	}
}

?>
