<?php

/**
 * @file classes/user/UserSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserSettingsDAO
 * @ingroup user
 * @see User
 *
 * @brief Operations for retrieving and modifying user settings.
 */

// $Id$


class UserSettingsDAO extends DAO {
	/**
	 * Retrieve a user setting value.
	 * @param $userId int
	 * @param $name
	 * @param $journalId int
	 * @return mixed
	 */
	function &getSetting($userId, $name, $journalId = null) {
		$result =& $this->retrieve(
			'SELECT setting_value, setting_type FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id = ?',
			array((int) $userId, $name, (int) $journalId)
		);

		if ($result->RecordCount() != 0) {
			$row =& $result->getRowAssoc(false);
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
		$userDao =& DAORegistry::getDAO('UserDAO');

		$value = $this->convertToDB($value, $type);
		$result =& $this->retrieve(
			'SELECT u.* FROM users u, user_settings s WHERE u.user_id = s.user_id AND s.setting_name = ? AND s.setting_value = ? AND s.journal_id = ?',
			array($name, $value, (int) $journalId)
		);

		$returner =& new DAOResultFactory($result, $userDao, '_returnUserFromRow');
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

		$result =& $this->retrieve(
			'SELECT setting_name, setting_value, setting_type FROM user_settings WHERE user_id = ? and journal_id = ?',
			array((int) $userId, (int) $journalId)
		);

		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
			$userSettings[$row['setting_name']] = $value;
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return $userSettings;
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
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id = ?',
			array($userId, $name, (int) $journalId)
		);

		$value = $this->convertToDB($value, $type);
		if ($result->fields[0] == 0) {
			$returner = $this->update(
				'INSERT INTO user_settings
					(user_id, setting_name, journal_id, setting_value, setting_type)
					VALUES
					(?, ?, ?, ?, ?)',
				array((int) $userId, $name, (int) $journalId, $value, $type)
			);
		} else {
			$returner = $this->update(
				'UPDATE user_settings SET
					setting_value = ?,
					setting_type = ?
					WHERE user_id = ? AND setting_name = ? AND journal_id = ?',
				array($value, $type, (int) $userId, $name, (int) $journalId)
			);
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
		return $this->update(
			'DELETE FROM user_settings WHERE user_id = ? AND setting_name = ? AND journal_id = ?',
			array((int) $userId, $name, (int) $journalId)
		);
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
