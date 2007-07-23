<?php

/**
 * @file Role.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package security
 * @class Role
 *
 * Role class.
 * Describes user roles within the system and the associated permissions.
 *
 * $Id$
 */

/** ID codes for all user roles */
define('ROLE_ID_SITE_ADMIN',		0x00000001);
define('ROLE_ID_JOURNAL_MANAGER',	0x00000010);
define('ROLE_ID_EDITOR',		0x00000100);
define('ROLE_ID_SECTION_EDITOR',	0x00000200);
define('ROLE_ID_LAYOUT_EDITOR',		0x00000300);
define('ROLE_ID_REVIEWER',		0x00001000);
define('ROLE_ID_COPYEDITOR',		0x00002000);
define('ROLE_ID_PROOFREADER',		0x00003000);
define('ROLE_ID_AUTHOR',		0x00010000);
define('ROLE_ID_READER',		0x00100000);
define('ROLE_ID_SUBSCRIPTION_MANAGER',	0x00200000);

class Role extends DataObject {

	/**
	 * Constructor.
	 */
	function Role() {
		parent::DataObject();
	}
	
	/**
	 * Get the i18n key name associated with this role.
	 * @return String the key
	 */
	function getRoleName() {
		return RoleDAO::getRoleName($this->getData('roleId'));
	}
	
	/**
	 * Get the URL path associated with this role's operations.
	 * @return String the path
	 */
	function getRolePath() {
		return RoleDAO::getRolePath($this->getData('roleId'));
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get journal ID associated with role.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}
	
	/**
	 * Set journal ID associated with role.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}
	
	/**
	 * Get user ID associated with role.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}
	
	/**
	 * Set user ID associated with role.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}
	
	/**
	 * Get role ID of this role.
	 * @return int
	 */
	function getRoleId() {
		return $this->getData('roleId');
	}
	
	/**
	 * Set role ID of this role.
	 * @param $roleId int
	 */
	function setRoleId($roleId) {
		return $this->setData('roleId', $roleId);
	}
}

?>
