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

import('pages.rtadmin.RTSetupHandler');
import('pages.rtadmin.RTVersionHandler');
import('pages.rtadmin.RTContextHandler');

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
	 * Ensure that this page is available to the user.
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
		RTSetupHandler::settings();
	}
	
	function saveSettings() {
		RTSetupHandler::saveSettings();
	}
	
	//
	// Versions
	//
	
	function exportVersion() {
		RTVersionHandler::exportVersion();
	}
	
	function importVersion() {
		RTVersionHandler::importVersion();
	}
	
	function restoreVersions() {
		RTVersionHandler::restoreVersions();
	}
	
	function versions() {
		RTVersionHandler::versions();
	}
	
	function editVersion($args) {
		RTVersionHandler::editVersion($args);
	}

	function deleteVersion($args) {
		RTVersionHandler::deleteVersion($args);
	}
	
	function saveVersion($args) {
		RTVersionHandler::saveVersion($args);
	}
	
	
	//
	// Contexts
	//
	
	function contexts($args) {
		RTContextHandler::contexts($args);
	}
	
	function editContext($args) {
		RTContextHandler::editContext($args);
	}
	
	function saveContext($args) {
		RTContextHandler::saveContext($args);
	}
	
	function deleteContext($args) {
		RTContextHandler::deleteContext($args);
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
