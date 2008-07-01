<?php

/**
 * @file classes/security/AccessKeyManager.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AccessKeyManager
 * @ingroup security
 * @see AccessKey
 *
 * @brief Class defining operations for AccessKey management.
 */

// $Id$


class AccessKeyManager {
	var $accessKeyDao;

	/**
	 * Constructor.
	 * Create a manager for access keys.
	 */
	function AccessKeyManager() {
		$this->accessKeyDao =& DAORegistry::getDAO('AccessKeyDAO');
		$this->_performPeriodicCleanup();
	}

	/**
	 * Generate a key hash from a key.
	 * @param $key string
	 * @return string
	 */
	function generateKeyHash($key) {
		return md5($key);
	}

	/**
	 * Validate an access key based on the supplied credentials.
	 * If $assocId is specified, it must match the associated ID of the
	 * key exactly.
	 * @param $context string The context of the access key
	 * @param $key string The access key "passcode"
	 * @param $assocId string optional assoc ID to check against the keys in the database
	 */
	function &validateKey($context, $userId, $keyHash, $assocId = null) {
		$accessKey =& $this->accessKeyDao->getAccessKeyByKeyHash($context, $userId, $keyHash, $assocId);
		return $accessKey;
	}

	/**
	 * Create an access key with the given information.
	 * @param $context string The context of the access key
	 * @param $userId int The ID of the effective user for this access key
	 * @param $assocId int The associated ID of the key
	 * @param $expiryDays int The number of days before this key expires
	 * @return accessKey string The generated passkey
	 */
	function createKey($context, $userId, $assocId, $expiryDays) {
		$accessKey =& new AccessKey();
		$accessKey->setContext($context);
		$accessKey->setUserId($userId);
		$accessKey->setAssocId($assocId);
		$accessKey->setExpiryDate(Core::getCurrentDate(time() + (60 * 60 * 24 * $expiryDays)));

		$key = Validation::generatePassword();
		$accessKey->setKeyHash($this->generateKeyHash($key));

		$this->accessKeyDao->insertAccessKey($accessKey);

		return $key;
	}

	/**
	 * Periodically clean up expired keys.
	 */
	function _performPeriodicCleanup() {
		if (time() % 100 == 0) {
			$accessKeyDao = &DAORegistry::getDAO('AccessKeyDAO');
			$accessKeyDao->deleteExpiredKeys();
		}
	}
}

?>
