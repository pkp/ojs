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
	 * @copydoc PKPUserSettingsDAO::getSetting
	 */
	function &getSetting($userId, $name, $assocType = null, $journalId = null) {
		return parent::getSetting($userId, $name, ASSOC_TYPE_JOURNAL, $journalId);
	}

	/**
	 * @copydoc PKPUserSettingsDAO::getUsersBySetting
	 */
	function &getUsersBySetting($name, $value, $type = null, $assocType = null, $journalId = null) {
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
	 * @copydoc PKPUserSettingsDAO::updateSetting
	 */
	function updateSetting($userId, $name, $value, $type = null, $assocType = null, $journalId = null) {
		return parent::updateSetting($userId, $name, $value, $type, ASSOC_TYPE_JOURNAL, $journalId);
	}

	/**
	 * @copydoc PKPUserSettingsDAO::deleteSetting
	 */
	function deleteSetting($userId, $name, $assocType = null, $journalId = null) {
		return parent::deleteSetting($userId, $name, ASSOC_TYPE_JOURNAL, $journalId);
	}
}


