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
			
			if ($section != null) {
				$this->_data = array(
					'title' => $section->getTitle(),
					'abbrev' => $section->getAbbrev()
				);
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'abbrev'));
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
		
		if ($section->getSectionId() != null) {
			$sectionDao->updateSection($section);
			
		} else {
			$sectionDao->insertSection($section);
			$sectionDao->resequenceSections($journal->getJournalId());
		}
	}
	
}

?>
