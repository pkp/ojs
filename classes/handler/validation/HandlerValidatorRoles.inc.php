<?php
/**
 * @file classes/handler/HandlerValidatorConference.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HandlerValidator
 * @ingroup security
 *
 * @brief Class to represent a page validation check.
 */

import('handler.validation.HandlerValidator');

class HandlerValidatorRoles extends HandlerValidator {
	var $roles;
	
	var $all;

	/**
	 * Constructor.
	 * @param $handler Handler the associated form
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

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$returner = true;
		foreach ( $this->roles as $roleId ) {
			if ( $roleId == ROLE_ID_SITE_ADMIN ) {
				$exists = $roleDao->roleExists(0, $user->getUserId(), $roleId);
			} else { 
				$exists = $roleDao->roleExists($journalId, $user->getUserId(), $roleId);
			}
			if ( !$this->all && $exists) return true;
			$returner = $returner && $exists;
		} 
		
		return $returner;
	}
}

?>
