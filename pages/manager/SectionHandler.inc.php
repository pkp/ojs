<?php

/**
 * SectionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for section management functions. 
 *
 * $Id$
 */

class SectionHandler extends ManagerHandler {

	/**
	 * Display a list of the sections within the current journal.
	 */
	function sections() {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = &$sectionDao->getJournalSections($journal->getJournalId());
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array('manager', 'manager.journalManagement')));
		$templateMgr->assign('sections', $sections);
		$templateMgr->assign('helpTopicId','journal.managementPages.sections');
		$templateMgr->display('manager/sections/sections.tpl');
	}
	
	/**
	 * Display form to create a new section.
	 */
	function createSection() {
		SectionHandler::editSection();
	}
	
	/**
	 * Display form to create/edit a section.
	 * @param $args array optional, if set the first parameter is the ID of the section to edit
	 */
	function editSection($args = array()) {
		parent::validate();
		parent::setupTemplate(true);
		
		import('manager.form.SectionForm');
		
		$sectionForm = &new SectionForm(!isset($args) || empty($args) ? null : $args[0]);
		$sectionForm->initData();
		$sectionForm->display();
	}
	
	/**
	 * Save changes to a section.
	 */
	function updateSection() {
		parent::validate();
		
		import('manager.form.SectionForm');
		
		$sectionForm = &new SectionForm(Request::getUserVar('sectionId'));
		$sectionForm->readInputData();
		
		if ($sectionForm->validate()) {
			$sectionForm->execute();
			Request::redirect('manager/sections');
			
		} else {
			parent::setupTemplate(true);
			$sectionForm->display();
		}
	}
	
	/**
	 * Delete a section.
	 * @param $args array first parameter is the ID of the section to delete
	 */
	function deleteJournal($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
		
			$sectionDao = &DAORegistry::getDAO('SectionDAO');
			$sectionDao->deleteSectionById($args[0], $journal->getJournalId());
		}
		
		Request::redirect('manager/sections');
	}
	
	/**
	 * Change the sequence of a section.
	 */
	function moveSection() {
		parent::validate();
		
		$journal = &Request::getJournal();
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection(Request::getUserVar('sectionId'), $journal->getJournalId());
		
		if ($section != null) {
			$section->setSequence($section->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$sectionDao->updateSection($section);
			$sectionDao->resequenceSections($journal->getJournalId());
		}
		
		Request::redirect('manager/sections');
	}
	
}
?>
