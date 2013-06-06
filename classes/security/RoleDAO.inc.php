<?php

/**
 * @file classes/security/RoleDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleDAO
 * @ingroup security
 * @see Role
 *
 * @brief Operations for retrieving and modifying Role objects.
 */


import('classes.security.Role');
import('lib.pkp.classes.security.PKPRoleDAO');


class RoleDAO extends PKPRoleDAO {
	/**
	 * Constructor.
	 */
	function RoleDAO() {
		parent::PKPRoleDAO();
		$this->userDao = DAORegistry::getDAO('UserDAO');
	}

	/**
	 * Create new data object.
	 * @return Role
	 */
	function newDataObject() {
		return new Role();
	}

	/**
	 * Get a mapping of role keys and i18n key names.
	 * @param boolean $contextOnly If false, also returns site-level roles (Site admin)
	 * @param array $roleIds Only return role names of these IDs
	 * @return array
	 */
	static function getRoleNames($contextOnly = false, $roleIds = null) {
		$parentRoleNames = parent::getRoleNames($contextOnly);

		$journalRoleNames = array(
			ROLE_ID_MANAGER => 'user.role.manager',
			ROLE_ID_EDITOR => 'user.role.editor',
			ROLE_ID_GUEST_EDITOR => 'user.role.guestEditor',
			ROLE_ID_ASSISTANT => 'user.role.journalAssistant',
			ROLE_ID_SECTION_EDITOR => 'user.role.sectionEditor',
			ROLE_ID_COPYEDITOR => 'user.role.copyeditor',
			ROLE_ID_PROOFREADER => 'user.role.proofreader',
			ROLE_ID_LAYOUT_EDITOR => 'user.role.layoutEditor',
			ROLE_ID_SUBSCRIPTION_MANAGER => 'user.role.subscriptionManager',
		);
		$roleNames = $parentRoleNames + $journalRoleNames;

		if(!empty($roleIds)) {
			$returner = array();
			foreach($roleIds as $roleId) {
				if(isset($roleNames[$roleId])) $returner[$roleId] = $roleNames[$roleId];
			}
			return $returner;
		} else {
			return $roleNames;
		}
	}

	/**
	 * Get a role's ID based on its path.
	 * @param $rolePath string
	 * @return int
	 */
	function getRoleIdFromPath($rolePath) {
		switch ($rolePath) {
			case 'editor':
				return ROLE_ID_EDITOR;
			case 'sectionEditor':
				return ROLE_ID_SECTION_EDITOR;
			case 'layoutEditor':
				return ROLE_ID_LAYOUT_EDITOR;
			case 'copyeditor':
				return ROLE_ID_COPYEDITOR;
			case 'proofreader':
				return ROLE_ID_PROOFREADER;
			case 'subscriptionManager':
				return ROLE_ID_SUBSCRIPTION_MANAGER;
			default:
				return parent::getRoleIdFromPath($rolePath);
		}
	}
}

?>
