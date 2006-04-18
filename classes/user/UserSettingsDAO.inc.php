<?php

/**
 * UserSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user
 *
 * Class for User Settings DAO.
 * Operations for retrieving and modifying user settings.
 *
 * $Id$
 */

class UserSettingsDAO extends DAO {
	/**
	 * Constructor.
	 */
	function UserSettingsDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a user setting value.
	 * @param $userId int
	 * @param $name
	 * @param $journalId int
	 * @return mixed
	 */
	function &getSetting($userId, $name, $journalId = null) {

		if ($journalId == null) {
			$result = &$this->retrieve(
				'SELECT setting_value, setting_type FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id IS NULL', array($userId, $name)
			);
		} else {
			$result = &$this->retrieve(
				'SELECT setting_value, setting_type FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id = ?', array($userId, $name, $journalId)
			);
		}
		
		if ($result->RecordCount() != 0) {
			$row = &$result->getRowAssoc(false);
			switch ($row['setting_type']) {
				case 'bool':
					$returner = (bool) $row['setting_value'];
					break;
				case 'int':
					$returner = (int) $row['setting_value'];
					break;
				case 'float':
					$returner = (float) $row['setting_value'];
					break;
				case 'object':
					$returner = unserialize($row['setting_value']);
					break;
				case 'string':
				default:
					$returner = $row['setting_value'];
					break;
			}
		} else {
			$returner = null;
		}

		return $returner;
			
	}

	/**
	 * Retrieve all users by setting name and value.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string
	 * @param $journalId int
	 * @return DAOResultFactory matching Users
	 */
	function &getUsersBySetting($name, $value, $type = null, $journalId = null) {

		if ($type == null) {
			switch (gettype($value)) {
				case 'boolean':
				case 'bool':
					$type = 'bool';
					break;
				case 'integer':
				case 'int':
					$type = 'int';
					break;
				case 'double':
				case 'float':
					$type = 'float';
					break;
				case 'array':
				case 'object':
					$type = 'object';
					break;
				case 'string':
				default:
					$type = 'string';
					break;
			}
		}
		
		if ($type == 'object') {
			$value = serialize($value);
			
		} else if ($type == 'bool') {
			$value = isset($value) && $value ? 1 : 0;
		}

		$userDao = &DAORegistry::getDAO('UserDAO');

		if ($journalId == null) {
			$result = &$this->retrieve(
				'SELECT u.* FROM users u, user_settings s WHERE u.user_id = s.user_id AND s.setting_name = ? AND s.setting_value = ? AND s.journal_id IS NULL',
				array($name, $value)
			);
		} else {				
			$result = &$this->retrieve(
				'SELECT u.* FROM users u, user_settings s WHERE u.user_id = s.user_id AND s.setting_name = ? AND s.setting_value = ? AND s.journal_id = ?',
				array($name, $value, $journalId)
			);
		}

		$returner = &new DAOResultFactory($result, $userDao, '_returnUserFromRow');
		return $returner;
	}

	/**
	 * Retrieve all settings for a user for a journal.
	 * @param $userId int
	 * @param $journalId int
	 * @return array 
	 */
	function &getSettingsByJournal($userId, $journalId = null) {

		$userSettings = array();

		if ($journalId == null) {
			$result = &$this->retrieve(
				'SELECT setting_name, setting_value, setting_type FROM user_settings WHERE user_id = ? AND journal_id IS NULL', $userId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT setting_name, setting_value, setting_type FROM user_settings WHERE user_id = ? and journal_id = ?', array($userId, $journalId)
			);
		}
		
		if ($result->RecordCount() == 0) {
			$returner = null;
			$result->Close();
			return $returner;
			
		} else {
			while (!$result->EOF) {
				$row = &$result->getRowAssoc(false);
				switch ($row['setting_type']) {
					case 'bool':
						$value = (bool) $row['setting_value'];
						break;
					case 'int':
						$value = (int) $row['setting_value'];
						break;
					case 'float':
						$value = (float) $row['setting_value'];
						break;
					case 'object':
						$value = unserialize($row['setting_value']);
						break;
					case 'string':
					default:
						$value = $row['setting_value'];
						break;
				}
				$userSettings[$row['setting_name']] = $value;
				$result->MoveNext();
			}
			$result->close();
			unset($result);

			return $userSettings;
		}
	}
	
	/**
	 * Add/update a user setting.
	 * @param $userId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @param $journalId int
	 */
	function updateSetting($userId, $name, $value, $type = null, $journalId = null) {
		
		if ($type == null) {
			switch (gettype($value)) {
				case 'boolean':
				case 'bool':
					$type = 'bool';
					break;
				case 'integer':
				case 'int':
					$type = 'int';
					break;
				case 'double':
				case 'float':
					$type = 'float';
					break;
				case 'array':
				case 'object':
					$type = 'object';
					break;
				case 'string':
				default:
					$type = 'string';
					break;
			}
		}
		
		if ($type == 'object') {
			$value = serialize($value);
			
		} else if ($type == 'bool') {
			$value = isset($value) && $value ? 1 : 0;
		}

		if ($journalId == null) {		
			$result = $this->retrieve(
				'SELECT COUNT(*) FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id IS NULL', array($userId, $name)
			);
		} else {
			$result = $this->retrieve(
				'SELECT COUNT(*) FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id = ?', array($userId, $name, $journalId)
			);
		}
		
		if ($result->fields[0] == 0) {
			$returner = $this->update(
				'INSERT INTO user_settings
					(user_id, setting_name, journal_id, setting_value, setting_type)
					VALUES
					(?, ?, ?, ?, ?)',
				array($userId, $name, $journalId, $value, $type)
			);
		} else {
			if ($journalId == null) {
				$returner = $this->update(
					'UPDATE user_settings SET
						setting_value = ?,
						setting_type = ?
						WHERE user_id = ? AND setting_name = ? AND journal_id IS NULL',
					array($value, $type, $userId, $name)
				);
			} else {
				$returner = $this->update(
					'UPDATE user_settings SET
						setting_value = ?,
						setting_type = ?
						WHERE user_id = ? AND setting_name = ? AND journal_id = ?',
					array($value, $type, $userId, $name, $journalId)
				);
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Delete a user setting.
	 * @param $userId int
	 * @param $name string
	 * @param $journalId int
	 */
	function deleteSetting($userId, $name, $journalId = null) {
		if ($journalId == null) {
			return $this->update(
				'DELETE FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id IS NULL',
				array($userId, $name)
			);
		} else {		
			return $this->update(
				'DELETE FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id = ?',
				array($userId, $name, $journalId)
			);
		}
	}
	
	/**
	 * Delete all settings for a user.
	 * @param $userId int
	 */
	function deleteSettings($userId) {
		return $this->update(
				'DELETE FROM user_settings WHERE user_id = ?', $userId
		);
	}

}

?>
