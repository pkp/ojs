<?php

/**
 * @file UserSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user
 * @class UserSettingsDAO
 *
 * Class for User Settings DAO.
 * Operations for retrieving and modifying user settings.
 *
 * $Id$
 */

class UserSettingsDAO extends DAO {
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
			$returner = $this->convertFromDB($row['setting_value'], $row['setting_type']);
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
		$userDao = &DAORegistry::getDAO('UserDAO');

		$value = $this->convertToDB($value, $type);
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
				$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
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
		if ($journalId == null) {		
			$result = $this->retrieve(
				'SELECT COUNT(*) FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id IS NULL', array($userId, $name)
			);
		} else {
			$result = $this->retrieve(
				'SELECT COUNT(*) FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id = ?', array($userId, $name, $journalId)
			);
		}

		$value = $this->convertToDB($value, $type);
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
