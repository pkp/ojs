<?php

/**
 * AdminJournalHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 *
 * Handle requests for journal management in site administration. 
 *
 * $Id$
 */

class AdminJournalHandler extends AdminHandler {

	/**
	 * Display a list of the journals hosted on the site.
	 */
	function journals() {
		parent::validate();
		parent::setupTemplate(true);
		
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journals = &$journalDao->getJournals();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('journals', $journals);
		$templateMgr->display('admin/journals.tpl');
	}
	
	/**
	 * Display form to create a new journal.
	 */
	function createJournal() {
		AdminJournalHandler::editJournal();
	}
	
	/**
	 * Display form to create/edit a journal.
	 * @param $args array optional, if set the first parameter is the ID of the journal to edit
	 */
	function editJournal($args = array()) {
		parent::validate();
		parent::setupTemplate(true);
		
		import('admin.form.JournalSiteSettingsForm');
		
		$settingsForm = &new JournalSiteSettingsForm(!isset($args) || empty($args) ? null : $args[0]);
		$settingsForm->initData();
		$settingsForm->display();
	}
	
	/**
	 * Save changes to a journal's settings.
	 */
	function updateJournal() {
		parent::validate();
		
		import('admin.form.JournalSiteSettingsForm');
		
		$settingsForm = &new JournalSiteSettingsForm(Request::getUserVar('journalId'));
		$settingsForm->readInputData();
		
		if ($settingsForm->validate()) {
			$settingsForm->execute();
			Request::redirect('admin/journals');
			
		} else {
			parent::setupTemplate(true);
			$settingsForm->display();
		}
	}
	
	/**
	 * Delete a journal.
	 * @param $args array first parameter is the ID of the journal to delete
	 */
	function deleteJournal($args) {
		parent::validate();
		
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		
		if (isset($args) && !empty($args)) {
			$journalDao->deleteJournalById($args[0]);
		}
		
		Request::redirect('admin/journals');
	}
	
	/**
	 * Change the sequence of a journal on the site index page.
	 */
	function moveJournal() {
		parent::validate();
		
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journal = &$journalDao->getJournal(Request::getUserVar('journalId'));
		
		if ($journal != null) {
			$journal->setSequence($journal->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$journalDao->updateJournal($journal);
			$journalDao->resequenceJournals();
		}
		
		Request::redirect('admin/journals');
	}
	
}

?>
