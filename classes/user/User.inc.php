<?php

/**
 * @file classes/user/User.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class User
 * @ingroup user
 * @see UserDAO
 *
 * @brief Basic class describing users existing in the system.
 */

import('lib.pkp.classes.user.PKPUser');

class User extends PKPUser {

	/**
	 * Retrieve array of user settings.
	 * @param journalId int
	 * @return array
	 */
	function &getSettings($journalId = null) {
		$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
		$settings =& $userSettingsDao->getSettingsByJournal($this->getId(), $journalId);
		return $settings;
	}

	/**
	 * Retrieve a user setting value.
	 * @param $name
	 * @param $journalId int
	 * @return mixed
	 */
	function &getSetting($name, $journalId = null) {
		$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
		$setting =& $userSettingsDao->getSetting($this->getId(), $name, $journalId);
		return $setting;
	}

	/**
	 * Set a user setting value.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string optional
	 */
	function updateSetting($name, $value, $type = null, $journalId = null) {
		$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
		return $userSettingsDao->updateSetting($this->getId(), $name, $value, $type, $journalId);
	}
}


