<?php

/**
 * @file classes/user/User.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class User
 * @ingroup user
 * @see UserDAO
 *
 * @brief Basic class describing users existing in the system.
 */

// $Id$


import('user.PKPUser');

class User extends PKPUser {

	function User() {
		parent::PKPUser();
	}

	//
	// Get/set methods
	//

	/**
	 * Get date the user membership expires 
	 * @return datestamp (YYYY-MM-DD HH:MM:SS)
	 */
	function getDateEndMembership() {
		return $this->getData('dateEndMembership');
	}

	/**
	 * Set date the user membership expires
	 * @param $dateLastEmail datestamp (YYYY-MM-DD HH:MM:SS)
	 */
	function setDateEndMembership($dateEndMembership) {
		return $this->setData('dateEndMembership', $dateEndMembership);
	}
	
	/**
	 * Get implicit auth ID string.
	 * @return String
	 */
	function getAuthStr() {
		return $this->getData('authStr');
	}

	/**
	 * Set Shib ID string for this user.
	 * @param $authStr string
	 */
	function setAuthStr($authStr) {
		return $this->setData('authStr', $authStr);
	}

	/**
	 * Retrieve array of user settings.
	 * @param journalId int
	 * @return array
	 */
	function &getSettings($journalId = null) {
		$userSettingsDao = &DAORegistry::getDAO('UserSettingsDAO');
		$settings = &$userSettingsDao->getSettingsByJournal($this->getData('userId'), $journalId);
		return $settings;
	}

	/**
	 * Retrieve a user setting value.
	 * @param $name
	 * @param $journalId int
	 * @return mixed
	 */
	function &getSetting($name, $journalId = null) {
		$userSettingsDao = &DAORegistry::getDAO('UserSettingsDAO');
		$setting = &$userSettingsDao->getSetting($this->getData('userId'), $name, $journalId);
		return $setting;
	}

	/**
	 * Set a user setting value.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string optional
	 */
	function updateSetting($name, $value, $type = null, $journalId = null) {
		$userSettingsDao = &DAORegistry::getDAO('UserSettingsDAO');
		return $userSettingsDao->updateSetting($this->getData('userId'), $name, $value, $type, $journalId);
	}
}

?>
