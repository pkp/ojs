<?php

/**
 * SectionForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for creating and modifying journal sections.
 *
 * $Id$
 */

class SectionForm extends Form {

	/** The ID of the section being edited */
	var $sectionId;
	
	/**
	 * Constructor.
	 * @param $journalId int omit for a new journal
	 */
	function SectionForm($sectionId = null) {
		parent::Form('manager/sections/sectionForm.tpl');
		
		$this->sectionId = $sectionId;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'title', 'required', 'manager.sections.form.titleRequired'));
		$this->addCheck(new FormValidator(&$this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('sectionId', $this->sectionId);
		
		if (Request::getUserVar('assignedEditors') != null) {
			// Reloading edit form -- get editors from form data
			$unassignedEditorIds = explode(':', Request::getUserVar('unassignedEditors'));
			$assignedEditorIds = explode(':', Request::getUserVar('assignedEditors'));
			
			$userDao = &DAORegistry::getDAO('UserDAO');
			
			// Get section editors not assigned to this section
			$unassignedEditors = array();
			foreach ($unassignedEditorIds as $edUserId) {
				if (!empty($edUserId)) {
					$unassignedEditors[] = &$userDao->getUser($edUserId);
				}
			}
			
			// Get section editors assigned to this section
			$assignedEditors = array();
			foreach ($assignedEditorIds as $edUserId) {
				if (!empty($edUserId)) {
					$assignedEditors[] = &$userDao->getUser($edUserId);
				}
			}
			
		} else {
			$journal = &Request::getJournal();
			$sectionEditorsDao = &DAORegistry::getDAO('SectionEditorsDAO');
			
			// Get section editors not assigned to this section
			$unassignedEditors = &$sectionEditorsDao->getEditorsNotInSection($journal->getJournalId(), $this->sectionId);
			
			// Get section editors assigned to this section
			$assignedEditors = &$sectionEditorsDao->getEditorsBySectionId($journal->getJournalId(), $this->sectionId);
		}
		
		$templateMgr->assign('unassignedEditors', $unassignedEditors);
		$templateMgr->assign('assignedEditors', $assignedEditors);
		
		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->sectionId)) {
			$journal = &Request::getJournal();
			$sectionDao = &DAORegistry::getDAO('SectionDAO');
			$section = &$sectionDao->getSection($this->sectionId, $journal->getJournalId());
			
			if ($section == null) {
				unset($this->sectionId);
				
			} else {
				$this->_data = array(
					'title' => $section->getTitle(),
					'abbrev' => $section->getAbbrev(),
					'metaIndexed' => $section->getMetaIndexed(),
					'authorIndexed' => $section->getAuthorIndexed(),
					'rst' => $section->getRST(),
					'policy' => $section->getPolicy()
				);
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'abbrev', 'metaIndexed', 'authorIndexed', 'rst', 'policy'));
	}
	
	/**
	 * Save section.
	 */
	function execute() {
		$journal = &Request::getJournal();
			
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		
		if (isset($this->sectionId)) {
			$section = &$sectionDao->getSection($this->sectionId, $journal->getJournalId());
		}
		
		if (!isset($section)) {
			$section = &new Section();
			$section->setJournalId($journal->getJournalId());
		}
		
		$section->setTitle($this->getData('title'));
		$section->setAbbrev($this->getData('abbrev'));
		$section->setMetaIndexed($this->getData('metaIndexed') ? 1 : 0);
		$section->setAuthorIndexed($this->getData('authorIndexed') ? 1 : 0);
		$section->setRST($this->getData('rst') ? 1 : 0);
		$section->setPolicy($this->getData('policy'));
		
		if ($section->getSectionId() != null) {
			$sectionDao->updateSection($section);
			$sectionId = $section->getSectionId();
			
		} else {
			$sectionDao->insertSection($section);
			$sectionId = $sectionDao->getInsertSectionId();
			$sectionDao->resequenceSections($journal->getJournalId());
		}
		
		// Save assigned editors
		$sectionEditorsDao = &DAORegistry::getDAO('SectionEditorsDAO');
		$sectionEditorsDao->deleteEditorsBySectionId($journal->getJournalId(), $sectionId);
		$editors = explode(':', Request::getUserVar('assignedEditors'));
		foreach ($editors as $edUserId) {
			$sectionEditorsDao->insertEditor($journal->getJournalId(), $sectionId, $edUserId);
		}
	}
	
}

?>
