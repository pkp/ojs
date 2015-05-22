<?php

/**
 * @file classes/core/PageRouter.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PageRouter
 * @ingroup core
 *
 * @brief Class providing OJS-specific page routing.
 */

import('lib.pkp.classes.core.PKPPageRouter');

class PageRouter extends PKPPageRouter {
	/**
	 * get the cacheable pages
	 * @return array
	 */
	function getCacheablePages() {
		return array('about', 'announcement', 'help', 'index', 'information', 'rt', 'issue', '');
	}

	/**
	 * Redirect to user home page (or the role home page if the user has one role).
	 * @param $request PKPRequest the request to be routed
	 */
	function redirectHome(&$request) {
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$user = $request->getUser();
		$userId = $user->getId();

		if ($journal =& $this->getContext($request, 1)) {
			// The user is in the journal context, see if they have one role only
			$roles =& $roleDao->getRolesByUserId($userId, $journal->getId());
			if(count($roles) == 1) {
				$role = array_shift($roles);
				if ($role->getRoleId() == ROLE_ID_READER) $request->redirect(null, 'index');
				$request->redirect(null, $role->getRolePath());
			} else {
				$request->redirect(null, 'user');
			}
		} else {
			// The user is at the site context, check to see if they are
			// only registered in one place w/ one role
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journals =& $journalDao->getJournals();
			$roles = $roleDao->getRolesByUserId($userId);

			if(count($roles) == 1) {
				$role = array_shift($roles);
				$journal = $journalDao->getById($role->getJournalId());
				if (!isset($journal)) $request->redirect('index', 'user');;
				if ($role->getRoleId() == ROLE_ID_READER) $request->redirect(null, 'index');
				$request->redirect($journal->getPath(), $role->getRolePath());
			} else $request->redirect('index', 'user');
		}
	}
}

?>
