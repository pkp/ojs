<?php

/**
 * PeopleHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for people management functions. 
 *
 * $Id$
 */

class PeopleHandler extends ManagerHandler {

	/**
	 * Display list of people in the selected role.
	 * @param $args array first parameter is the role ID to display
	 */	
	function people($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
			
		if (isset($args[0]) && $args[0] != 'all' && preg_match('/^(\w+)s$/', $args[0], $matches)) {
			$roleId = $roleDao->getRoleIdFromPath($matches[1]);
			if ($roleId == null) {
				Request::redirect('manager/people/all');
			}
			$roleName = $roleDao->getRoleName($roleId, true);
			
		} else {
			$roleId = 0;
			$roleName = 'manager.people.allUsers';
		}
		
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		
		if ($roleId) {
			$users = &$roleDao->getUsersByRoleId($roleId, $journal->getJournalId());
			$templateMgr->assign('roleId', $roleId);
			
		} else {
			$users = &$roleDao->getUsersByJournalId($journal->getJournalId());
		}
		
		$templateMgr->assign('currentUrl', Request::getPageUrl() . '/manager/people/all');
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign('users', $users);
		$templateMgr->display('manager/people/enrollment.tpl');
	}
	
	/**
	 * Search for users to enroll in a specific role.
	 * @param $args array first parameter is the selected role ID
	 */
	function enrollSearch($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('roleId', $args[0]);
		$templateMgr->display('manager/people/searchUsers.tpl');
	}
	
	/**
	 * Enroll a user in a role.
	 */
	function enroll() {
		parent::validate();
		
		if (Request::getUserVar('enroll') != null) {
			$users = Request::getUserVar('users');
			
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = &$journalDao->getJournalByPath(Request::getRequestedJournalPath());
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			
			if ($users != null && is_array($users)) {
				for ($i=0; $i<count($users); $i++) {
					$role = &new Role();
					$role->setJournalId($journal->getJournalId());
					$role->setUserId($users[$i]);
					$role->setRoleId(Request::getUserVar('roleId'));
					
					$roleDao->insertRole($role);
				}
				
				Request::redirect('manager/people');
			}
			
		} else {
			parent::setupTemplate(true);
			
			$userDao = &DAORegistry::getDAO('UserDAO');
			$users = &$userDao->getUsersByField(Request::getUserVar('searchField'), Request::getUserVar('searchMatch'), Request::getUserVar('searchValue'));
		
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('roleId', Request::getUserVar('roleId'));
			$templateMgr->assign('users', $users);
			$templateMgr->display('manager/people/searchUsersResults.tpl');
		}
	}
	
	/**
	 * Unenroll a user from a role.
	 */
	function unEnroll() {
		parent::validate();
			
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journal = &$journalDao->getJournalByPath(Request::getRequestedJournalPath());
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleDao->deleteRoleByUserId(Request::getUserVar('userId'), $journal->getJournalId(), Request::getUserVar('roleId'));
						
		Request::redirect('manager/people');
	}
	
}
?>
