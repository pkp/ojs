<?php

/**
 * @file AccessKeyDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package security
 * @class AccessKeyDAO
 *
 * Class for AccessKey DAO.
 * Operations for retrieving and modifying AccessKey objects.
 *
 * $Id$
 */

import('security.AccessKey');

class AccessKeyDAO extends DAO {
	/**
	 * Retrieve an accessKey by ID.
	 * @param $accessKeyId int
	 * @return AccessKey
	 */
	function &getAccessKey($accessKeyId) {
		$result = &$this->retrieve(
			sprintf(
				'SELECT * FROM access_keys WHERE access_key_id = ? AND expiry_date > %s',
				$this->datetimeToDB(Core::getCurrentDate())
			),
			$accessKeyId
		);

		$accessKey = null;
		if ($result->RecordCount() != 0) {
			$accessKey = &$this->_returnAccessKeyFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $accessKey;
	}

	/**
	 * Retrieve a accessKey object by key.
	 * @param $userId int
	 * @param $keyHash string
	 * @param $assocId int
	 * @return AccessKey
	 */
	function &getAccessKeyByKeyHash($context, $userId, $keyHash, $assocId = null) {
		$paramArray = array($context, $keyHash, $userId);
		if (isset($assocId)) $paramArray[] = $assocId;
		$result = &$this->retrieve(
			sprintf(
				'SELECT * FROM access_keys WHERE context = ? AND key_hash = ? AND user_id = ? AND expiry_date > %s' . (isset($assocId)?' AND assoc_id = ?':''), 
				$this->datetimeToDB(Core::getCurrentDate())
			),
			$paramArray
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnAccessKeyFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Internal function to return an AccessKey object from a row.
	 * @param $row array
	 * @return AccessKey
	 */
	function &_returnAccessKeyFromRow(&$row) {
		$accessKey = &new AccessKey();
		$accessKey->setAccessKeyId($row['access_key_id']);
		$accessKey->setKeyHash($row['key_hash']);
		$accessKey->setExpiryDate($this->datetimeFromDB($row['expiry_date']));
		$accessKey->setContext($row['context']);
		$accessKey->setAssocId($row['assoc_id']);
		$accessKey->setUserId($row['user_id']);

		HookRegistry::call('AccessKeyDAO::_returnAccessKeyFromRow', array(&$accessKey, &$row));

		return $accessKey;
	}

	/**
	 * Insert a new accessKey.
	 * @param $accessKey AccessKey
	 */
	function insertAccessKey(&$accessKey) {
		$this->update(
			sprintf('INSERT INTO access_keys
				(key_hash, expiry_date, context, assoc_id, user_id)
				VALUES
				(?, %s, ?, ?, ?)',
				$this->datetimeToDB($accessKey->getExpiryDate())),
			array(
				$accessKey->getKeyHash(),
				$accessKey->getContext(),
				$accessKey->getAssocId(),
				$accessKey->getUserId()
			)
		);

		$accessKey->setAccessKeyId($this->getInsertAccessKeyId());
		return $accessKey->getAccessKeyId();
	}

	/**
	 * Update an existing accessKey.
	 * @param $accessKey AccessKey
	 */
	function updateAccessKey(&$accessKey) {
		return $this->update(
			sprintf('UPDATE accessKeys
				SET
					key_hash = ?,
					expiry_date = %s,
					context = ?,
					assoc_id = ?,
					user_id = ?
				WHERE access_key_id = ?',
				$this->datetimeToDB($accessKey->getExpiryDate())),
			array(
				$accessKey->getKeyHash(),
				$accessKey->getContext(),
				$accessKey->getAssocId(),
				$accessKey->getUserId(),
				$accessKey->getAccessKeyId()
			)
		);
	}

	/**
	 * Delete an accessKey.
	 * @param $accessKey AccessKey
	 */
	function deleteAccessKey(&$accessKey) {
		return $this->deleteAccessKeyById($accessKey->getAccessKeyId());
	}

	/**
	 * Delete an accessKey by ID.
	 * @param $accessKeyId int
	 */
	function deleteAccessKeyById($accessKeyId) {
		return $this->update(
			'DELETE FROM access_keys WHERE access_key_id = ?', $accessKeyId
		);
	}

	/**
	 * Transfer access keys to another user ID.
	 * @param $oldUserId int
	 * @param $newUserId int
	 */
	function transferAccessKeys($oldUserId, $newUserId) {
		return $this->update(
			'UPDATE access_keys SET user_id = ? WHERE user_id = ?',
			array($newUserId, $oldUserId)
		);
	}

	/**
	 * Delete expired access keys.
	 */
	function deleteExpiredKeys() {
		return $this->update(
			sprintf(
				'DELETE FROM access_keys WHERE expiry_date <= %s',
				$this->datetimeToDB(Core::getCurrentDate())
			)
		);
	}

	/**
	 * Get the ID of the last inserted accessKey.
	 * @return int
	 */
	function getInsertAccessKeyId() {
		return $this->getInsertId('access_keys', 'access_key_id');
	}
}

?>
