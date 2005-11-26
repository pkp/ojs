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
		$rangeInfo = &Handler::getRangeInfo('sections');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = &$sectionDao->getJournalSections($journal->getJournalId(), $rangeInfo);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'manager'), 'manager.journalManagement')));
		$templateMgr->assign_by_ref('sections', $sections);
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
			Request::redirect(null, null, 'sections');
			
		} else {
			parent::setupTemplate(true);
			$sectionForm->display();
		}
	}
	
	/**
	 * Delete a section.
	 * @param $args array first parameter is the ID of the section to delete
	 */
	function deleteSection($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
		
			$sectionDao = &DAORegistry::getDAO('SectionDAO');
			$sectionDao->deleteSectionById($args[0], $journal->getJournalId());
		}
		
		Request::redirect(null, null, 'sections');
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
		
		Request::redirect(null, null, 'sections');
	}
	
}
?>
