<?php

/**
 * @file classes/security/Role.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Role
 * @ingroup security
 * @see RoleDAO
 *
 * @brief Describes user roles within the system and the associated permissions.
 */

import('lib.pkp.classes.security.PKPRole');

/** ID codes for all user roles */
define('ROLE_ID_EDITOR',		0x00000100);
define('ROLE_ID_SECTION_EDITOR',	0x00000011);
define('ROLE_ID_SUBSCRIPTION_MANAGER',	0x00200000);
define('ROLE_ID_GUEST_EDITOR',		0x00000201);

/** Fill in the blanks for roles used in PKP lib */
define('ROLE_ID_SUB_EDITOR',		ROLE_ID_SECTION_EDITOR);

class Role extends PKPRole {

	/**
	 * Constructor.
	 * @param $roleId for this role.  Default to null for backwards
	 * 	compatibility
	 */
	function Role($roleId = null) {
		parent::PKPRole($roleId);
	}
}

?>
