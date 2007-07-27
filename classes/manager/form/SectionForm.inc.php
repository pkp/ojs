<?php

/**
 * @file SectionForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class SectionForm
 *
 * Form for creating and modifying journal sections.
 *
 * $Id$
 */

import('form.Form');

class SectionForm extends Form {

	/** @var $sectionId int The ID of the section being edited */
	var $sectionId;

	/** @var $includeSectionEditor object Additional section editor to
	 *       include in assigned list for this section
	 */
	var $includeSectionEditor;

	/** @var $omitSectionEditor object Assigned section editor to omit from
	 *       assigned list for this section
	 */
	var $omitSectionEditor;

	/** @var $sectionEditors array List of user objects representing the
	 *       available section editors for this journal.
	 */
	var $sectionEditors;

	/**
	 * Constructor.
	 * @param $journalId int omit for a new journal
	 */
	function SectionForm($sectionId = null) {
		parent::Form('manager/sections/sectionForm.tpl');

		$journal =& Request::getJournal();
		$this->sectionId = $sectionId;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'title', 'required', 'manager.sections.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
		$this->addCheck(new FormValidatorPost($this));

		$this->includeSectionEditor = $this->omitSectionEditor = null;

		// Get a list of section editors for this journal.
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$this->sectionEditors =& $roleDao->getUsersByRoleId(ROLE_ID_SECTION_EDITOR, $journal->getJournalId());
		$this->sectionEditors =& $this->sectionEditors->toArray();
	}

	/**
	 * When displaying the form, include the specified section editor
	 * in the assigned list for this section.
	 * @param $sectionEditorId int
	 */
	function includeSectionEditor($sectionEditorId) {
		foreach ($this->sectionEditors as $key => $junk) {
			if ($this->sectionEditors[$key]->getUserId() == $sectionEditorId) {
				$this->includeSectionEditor =& $this->sectionEditors[$key];
			}
		}
	}

	/**
	 * When displaying the form, omit the specified section editor from
	 * the assigned list for this section.
	 */
	function omitSectionEditor($sectionEditorId) {
		foreach ($this->sectionEditors as $key => $junk) {
			if ($this->sectionEditors[$key]->getUserId() == $sectionEditorId) {
				$this->omitSectionEditor =& $this->sectionEditors[$key];
			}
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('sectionId', $this->sectionId);
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
				$sectionEditorsDao =& DAORegistry::getDAO('SectionEditorsDAO');
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
					'policy' => $section->getPolicy(),
					'assignedEditors' => $sectionEditorsDao->getEditorsBySectionId($journal->getJournalId(), $this->sectionId),
					'unassignedEditors' => $sectionEditorsDao->getEditorsNotInSection($journal->getJournalId(), $this->sectionId)
				);
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'titleAlt1', 'titleAlt2', 'abbrev', 'abbrevAlt1', 'abbrevAlt2', 'metaIndexed', 'metaReviewed', 'abstractsDisabled', 'identifyType', 'editorRestriction', 'hideTitle', 'hideAbout', 'policy'));
		$assignedEditorIds = Request::getUserVar('assignedEditorIds');
		if (empty($assignedEditorIds)) $assignedEditorIds = array();
		elseif (!is_array($assignedEditorIds)) $assignedEditorIds = array($assignedEditorIds);

		$assignedEditors = $unassignedEditors = array();

		foreach ($this->sectionEditors as $key => $junk) {
			$sectionEditor =& $this->sectionEditors[$key]; // Ref
			$userId = $sectionEditor->getUserId();

			$isIncludeEditor = $this->includeSectionEditor && $this->includeSectionEditor->getUserId() == $userId;
			$isOmitEditor = $this->omitSectionEditor && $this->omitSectionEditor->getUserId() == $userId;
			if ((in_array($userId, $assignedEditorIds) || $isIncludeEditor) && !$isOmitEditor) {
				$assignedEditors[] = array(
					'user' => &$sectionEditor,
					'canReview' => (Request::getUserVar("canReview-$userId")?1:0),
					'canEdit' => (Request::getUserVar("canEdit-$userId")?1:0)
				);
			} else {
				$unassignedEditors[] =& $sectionEditor;
			}

			unset($sectionEditor);
		}

		$this->setData('assignedEditors', $assignedEditors);
		$this->setData('unassignedEditors', $unassignedEditors);
	}
	
	/**
	 * Save section.
	 */
	function execute() {
		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
			
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		
		if (isset($this->sectionId)) {
			$section = &$sectionDao->getSection($this->sectionId, $journalId);
		}
		
		if (!isset($section)) {
			$section = &new Section();
			$section->setJournalId($journalId);
			$section->setSequence(REALLY_BIG_NUMBER);
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
			$sectionDao->resequenceSections($journalId);
		}
		
		// Save assigned editors
		$assignedEditorIds = Request::getUserVar('assignedEditorIds');
		if (empty($assignedEditorIds)) $assignedEditorIds = array();
		elseif (!is_array($assignedEditorIds)) $assignedEditorIds = array($assignedEditorIds);
		$sectionEditorsDao = &DAORegistry::getDAO('SectionEditorsDAO');
		$sectionEditorsDao->deleteEditorsBySectionId($sectionId, $journalId);
		foreach ($this->sectionEditors as $key => $junk) {
			$sectionEditor =& $this->sectionEditors[$key];
			$userId = $sectionEditor->getUserId();
			// We don't have to worry about omit- and include-
			// section editors because this function is only called
			// when the Save button is pressed and those are only
			// used in other cases.
			if (in_array($userId, $assignedEditorIds)) $sectionEditorsDao->insertEditor(
				$journalId,
				$sectionId,
				$userId,
				Request::getUserVar("canReview-$userId"),
				Request::getUserVar("canEdit-$userId")
			);
			unset($sectionEditor);
		}
	}
}

?>
