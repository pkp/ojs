<?php

/**
 * SectionForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for creating and modifying journal sections.
 *
 * $Id$
 */

import('form.Form');

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
		$this->addCheck(new FormValidator($this, 'title', 'required', 'manager.sections.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
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
		$templateMgr->assign('helpTopicId','journal.managementPages.sections');
		
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
					'titleAlt1' => $section->getTitleAlt1(),
					'titleAlt2' => $section->getTitleAlt2(),
					'abbrev' => $section->getAbbrev(),
					'abbrevAlt1' => $section->getAbbrevAlt1(),
					'abbrevAlt2' => $section->getAbbrevAlt2(),
					'metaIndexed' => !$section->getMetaIndexed(), // #2066: Inverted
					'metaReviewed' => !$section->getMetaReviewed(), // #2066: Inverted
					'abstractsDisabled' => $section->getAbstractsDisabled(),
					'identifyType' => $section->getIdentifyType(),
					'editorRestriction' => $section->getEditorRestricted(),
					'hideTitle' => $section->getHideTitle(),
					'hideAbout' => $section->getHideAbout(),
					'policy' => $section->getPolicy()
				);
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'titleAlt1', 'titleAlt2', 'abbrev', 'abbrevAlt1', 'abbrevAlt2', 'metaIndexed', 'metaReviewed', 'abstractsDisabled', 'identifyType', 'editorRestriction', 'hideTitle', 'hideAbout', 'policy'));
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
			// Kludge: Move this section to the end of the list
			$section->setSequence(10000);
		}
		
		$section->setTitle($this->getData('title'));
		$section->setTitleAlt1($this->getData('titleAlt1'));
		$section->setTitleAlt2($this->getData('titleAlt2'));
		$section->setAbbrev($this->getData('abbrev'));
		$section->setAbbrevAlt1($this->getData('abbrevAlt1'));
		$section->setAbbrevAlt2($this->getData('abbrevAlt2'));
		$section->setMetaIndexed($this->getData('metaIndexed') ? 0 : 1); // #2066: Inverted
		$section->setMetaReviewed($this->getData('metaReviewed') ? 0 : 1); // #2066: Inverted
		$section->setAbstractsDisabled($this->getData('abstractsDisabled') ? 1 : 0);
		$section->setIdentifyType($this->getData('identifyType'));
		$section->setEditorRestricted($this->getData('editorRestriction') ? 1 : 0);
		$section->setHideTitle($this->getData('hideTitle') ? 1 : 0);
		$section->setHideAbout($this->getData('hideAbout') ? 1 : 0);
		$section->setPolicy($this->getData('policy'));
		
		if ($section->getSectionId() != null) {
			$sectionDao->updateSection($section);
			$sectionId = $section->getSectionId();
			
		} else {
			$sectionId = $sectionDao->insertSection($section);
			$sectionDao->resequenceSections($journal->getJournalId());
		}
		
		// Save assigned editors
		$sectionEditorsDao = &DAORegistry::getDAO('SectionEditorsDAO');
		$sectionEditorsDao->deleteEditorsBySectionId($sectionId, $journal->getJournalId());
		$editors = explode(':', Request::getUserVar('assignedEditors'));
		foreach ($editors as $edUserId) {
			if (!empty($edUserId)) {
				$sectionEditorsDao->insertEditor($journal->getJournalId(), $sectionId, $edUserId);
			}
		}
	}
	
}

?>
