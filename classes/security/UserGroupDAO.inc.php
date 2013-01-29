<?php

/**
 * @file classes/security/UserGroupDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserGroupDAO
 * @ingroup security
 * @see PKPUserGroupDAO
 *
 * @brief Operations for retrieving and modifying User Groups and user group assignments
 * FIXME: Some of the context-specific features of this class will have
 * to be changed for zero- or double-context applications when user groups
 * are ported over to them.
 */

import('lib.pkp.classes.security.PKPUserGroupDAO');

class UserGroupDAO extends PKPUserGroupDAO {
	/**
	 * Constructor.
	 */
	function UserGroupDAO() {
		parent::PKPUserGroupDAO();
	}
}

?>
