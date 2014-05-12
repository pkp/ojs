<?php

/**
 * @file classes/security/RoleDAO.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleDAO
 * @ingroup security
 * @see Role
 *
 * @brief Operations for retrieving and modifying Role objects.
 */

import('lib.pkp.classes.security.Role');
import('lib.pkp.classes.security.PKPRoleDAO');

/** ID codes for all user roles */
define('ROLE_ID_EDITOR',		0x00000100);
define('ROLE_ID_SECTION_EDITOR',	0x00000011);
define('ROLE_ID_SUBSCRIPTION_MANAGER',	0x00200000);
define('ROLE_ID_GUEST_EDITOR',		0x00000201);

/** Fill in the blanks for roles used in PKP lib */
define('ROLE_ID_SUB_EDITOR',		ROLE_ID_SECTION_EDITOR);

class RoleDAO extends PKPRoleDAO {
	/**
	 * Constructor.
	 */
	function RoleDAO() {
		parent::PKPRoleDAO();
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
}

?>
