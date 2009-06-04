<?php
/**
 * @file classes/handler/HandlerValidatorRoles.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HandlerValidatorRoles
 * @ingroup handler_validation
 *
 * @brief Class to validate if current user belongs to a set of roles
 */

import('handler.validation.HandlerValidator');

class HandlerValidatorRoles extends HandlerValidator {
	var $roles;
	
	var $all;

	/**
	 * Constructor.
	 * @param $handler Handler the associated form
	 * @param $redirectToLogin bool Send to login screen on validation fail if true
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $additionalArgs Array URL arguments to include in request
	 * @param $roles array of role id's 
	 * @param $all bool flag for whether all roles must exist or just 1
	 */	 
	function HandlerValidatorRoles(&$handler, $redirectLogin = true, $message = null, $additionalArgs = array(), $roles, $all = false) {
		parent::HandlerValidator($handler, $redirectLogin, $message, $additionalArgs);
		$this->roles = $roles;
		$this->all = $all;
	}

	/**
	 * Check if field value is valid.
	 * Value is valid if it is empty and optional or validated by user-supplied function.
	 * @return boolean
	 */
	function isValid() {
		$journal =& Request::getJournal();
		$journalId = ($journal)?$journal->getJournalId():0;
		
		$user = Request::getUser();
		if ( !$user ) return false;

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$returner = true;
		foreach ( $this->roles as $roleId ) {
			if ( $roleId == ROLE_ID_SITE_ADMIN ) {
				$exists = $roleDao->roleExists(0, $user->getId(), $roleId);
			} else { 
				$exists = $roleDao->roleExists($journalId, $user->getId(), $roleId);
			}
			if ( !$this->all && $exists) return true;
			$returner = $returner && $exists;
		} 
		
		return $returner;
	}
}

?>
