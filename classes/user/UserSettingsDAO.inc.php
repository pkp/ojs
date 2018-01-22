<?php

/**
 * @file classes/user/UserSettingsDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserSettingsDAO
 * @ingroup user
 * @see User
 *
 * @brief Operations for retrieving and modifying user settings.
 */

import('lib.pkp.classes.user.PKPUserSettingsDAO');

class UserSettingsDAO extends PKPUserSettingsDAO {
	/**
	 * Retrieve a user setting value.
	 * @param $userId int
	 * @param $name
	 * @param $journalId int
	 * @return mixed
	 */
	function &getSetting($userId, $name, $journalId = null) {
		return parent::getSetting($userId, $name, ASSOC_TYPE_JOURNAL, $journalId);
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
		return parent::getUsersBySetting($name, $value, $type, ASSOC_TYPE_JOURNAL, $journalId);
	}

	/**
	 * Retrieve all settings for a user for a journal.
	 * @param $userId int
	 * @param $journalId int
	 * @return array 
	 */
	function &getSettingsByJournal($userId, $journalId = null) {
		return parent::getSettingsByAssoc($userId, ASSOC_TYPE_JOURNAL, $journalId);
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
		return parent::updateSetting($userId, $name, $value, $type, ASSOC_TYPE_JOURNAL, $journalId);
	}

	/**
	 * Delete a user setting.
	 * @param $userId int
	 * @param $name string
	 * @param $journalId int
	 */
	function deleteSetting($userId, $name, $journalId = null) {
		return parent::deleteSetting($userId, $name, ASSOC_TYPE_JOURNAL, $journalId);
	}
}

?>
