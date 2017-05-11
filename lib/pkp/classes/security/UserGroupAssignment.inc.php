<?php

/**
 * @file classes/security/UserGroupAssignment.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Role
 * @ingroup security
 * @see RoleDAO
 *
 * @brief Describes user roles within the system and the associated permissions.
 */

import('lib.pkp.classes.security.UserGroup');

class UserGroupAssignment extends DataObject {
	/** @var the UserGroup object associated with this assignment **/
	var $userGroup;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * Get user ID associated with a user group assignment.
	 * @return int
	 */
	function getUserGroupId() {
		return $this->getData('userGroupId');
	}

	/**
	 * Set user ID associated with a user group assignment.
	 * also sets the $userGroup
	 * @param $userId int
	 */
	function setUserGroupId($userGroupId) {
		$this->setData('userGroupId', $userGroupId);
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$this->userGroup = $userGroupDao->getById($userGroupId);
		return ($this->userGroup)?true:false;
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
		$this->setData('userId', $userId);
	}
}

?>
