<?php

/**
 * Role.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package security
 *
 * Role class.
 * Describes user roles within the system and the associated permissions.
 *
 * $Id$
 */

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

	/**
	 * Get the flag indicating whether or not users want to receive updates
	 * about this journal.
	 */
	function getReceivesUpdates() {
		return $this->getData('receivesUpdates')==1?1:0;
	}

	/**
	 * Set the flag indicating whether or not users want to receive updates
	 * about this journal.
	 * @param $receivesUpdates boolean
	 */
	function setReceivesUpdates($receivesUpdates) {
		return $this->setData('receivesUpdates', $receivesUpdates);
	}
}

?>
