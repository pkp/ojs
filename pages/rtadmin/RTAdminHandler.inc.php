<?php

/**
 * RTAdminHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rtadmin
 *
 * Handle Reading Tools administration requests. 
 *
 * $Id$
 */

import('rt.ojs.JournalRTAdmin');

class RTAdminHandler extends Handler {

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 */
	function index() {
		$journal = Request::getJournal();
		$user = Request::getUser();
		if ($user && $journal) {
			// Display the administration menu for this journal.

			RTAdminHandler::setupTemplate(false);
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->display('rtadmin/index.tpl');
		} elseif ($user) {
			// Display a list of journals.
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$roleDao = &DAORegistry::getDAO('RoleDAO');

			$journals = array();

			foreach ($journalDao->getJournals() as $journal) {
				if ($roleDao->roleExists($journal->getJournalId(), $user->getUserId(), ROLE_ID_JOURNAL_MANAGER)) {
					$journals[] = $journal;
				}
			}

			RTAdminHandler::setupTemplate(false);
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('journals', &$journals);
			$templateMgr->display('rtadmin/journals.tpl');
		} else {
			// Not logged in.
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Redirect to index if system has already been installed.
	 */
	function validate() {
		parent::validate(true);
		if (!Validation::isJournalManager()) {
			Validation::redirectLogin();
		}
	}
	
	
	//
	// General
	//
	
	function settings() {
		RTAdminHandler::validate();

		RTAdminHandler::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('journals', &$journals);
		$templateMgr->display('rtadmin/settings.tpl');
	}
	
	function saveSettings() {
		RTAdminHandler::validate();
	}
	
	function exportVersion() {
		RTAdminHandler::validate();
	}
	
	function importVersion() {
		RTAdminHandler::validate();
	}
	
	function restoreVersions() {
		RTAdminHandler::validate();
		
		$journal = &Request::getJournal();
		$rtAdmin = &new JournalRTAdmin($journal->getJournalId());
		$rtAdmin->restoreVersions();
		
		print "DONE";
	}
	
	
	//
	// Versions
	//
	
	function versions() {
		RTAdminHandler::validate();
	}
	
	function editVersion() {
		RTAdminHandler::validate();
	}
	
	function saveVersion() {
		RTAdminHandler::validate();
	}
	
	
	//
	// Contexts
	//
	
	function contexts() {
		RTAdminHandler::validate();
	}
	
	function editContext() {
		RTAdminHandler::validate();
	}
	
	function saveContext() {
		RTAdminHandler::validate();
	}
	
	
	//
	// Searches
	//
	
	function searches() {
		RTAdminHandler::validate();
	}
	
	function editSearch() {
		RTAdminHandler::validate();
	}
	
	function saveSearch() {
		RTAdminHandler::validate();
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler
in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('manager', 'manager.journalManagement'), array('rtadmin', 'rt.researchTools'))
				: array(array('user', 'navigation.user'), array('manager', 'manager.journalManagement'))
		);
		$templateMgr->assign('pagePath', '/user/rtadmin');
	}
}

?>
