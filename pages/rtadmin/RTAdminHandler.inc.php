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
		RTAdminHandler::validate();
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
}

?>
