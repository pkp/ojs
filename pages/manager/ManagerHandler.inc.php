<?php

/**
 * ManagerHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for journal management functions. 
 *
 * $Id$
 */

import('pages.manager.PeopleHandler');
import('pages.manager.SectionHandler');
import('pages.manager.SetupHandler');

class ManagerHandler extends Handler {

	/**
	 * Display journal management index page.
	 */
	function index() {
		ManagerHandler::validate();
		ManagerHandler::setupTemplate();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('manager/index.tpl');
	}
	
	/**
	 * Validate that user has permissions to manage the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journal = &Request::getJournal();
		
		if (!isset($journal) || !Validation::isJournalManager($journal->getJournalId())) {
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('manager', 'manager.journalManagement'))
				: array(array('user', 'navigation.user'))
		);
	}
	
	
	//
	// Setup
	//

	function setup($args) {
		SetupHandler::setup($args);
	}

	function saveSetup($args) {
		SetupHandler::saveSetup($args);
	}
	
	
	//
	// People Management
	//

	function people($args) {
		PeopleHandler::people($args);
	}
	
	function enrollSearch($args) {
		PeopleHandler::enrollSearch($args);
	}
	
	function enroll() {
		PeopleHandler::enroll();
	}
	
	function unEnroll() {
		PeopleHandler::unEnroll();
	}
	
	function createUser() {
		PeopleHandler::createUser();
	}
	
	function editUser($args) {
		PeopleHandler::editUser($args);
	}
	
	function updateUser() {
		PeopleHandler::updateUser();
	}
	
	
	//
	// Section Management
	//
	
	function sections() {
		SectionHandler::sections();
	}
	
	function createSection() {
		SectionHandler::createSection();
	}
	
	function editSection($args) {
		SectionHandler::editSection($args);
	}
	
	function updateSection() {
		SectionHandler::updateSection();
	}
	
	function deleteSection($args) {
		SectionHandler::deleteSection();
	}
	
	function moveSection() {
		SectionHandler::moveSection();
	}
	
}

?>
