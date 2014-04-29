<?php

/**
 * @file classes/manager/form/SectionForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionForm
 * @ingroup manager_form
 *
 * @brief Form for creating and modifying journal sections.
 */

import('lib.pkp.classes.form.Form');

class SectionForm extends Form {

	/** @var $section Section The section being edited */
	var $section;

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
		$journalId = $journal->getId();

		// Retrieve/instantiate section.
		$section = null;
		if (is_numeric($sectionId)) {
			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$section =& $sectionDao->getSection($sectionId, $journalId);
		}
		$this->section =& $section;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.sections.form.titleRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCustom($this, 'reviewFormId', 'optional', 'manager.sections.form.reviewFormId', array(DAORegistry::getDAO('ReviewFormDAO'), 'reviewFormExists'), array(ASSOC_TYPE_JOURNAL, $journal->getId())));
		$this->includeSectionEditor = $this->omitSectionEditor = null;

		// Get a list of section editors for this journal.
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$this->sectionEditors =& $roleDao->getUsersByRoleId(ROLE_ID_SECTION_EDITOR, $journal->getId());
		$this->sectionEditors =& $this->sectionEditors->toArray();
	}

	/**
	 * When displaying the form, include the specified section editor
	 * in the assigned list for this section.
	 * @param $sectionEditorId int
	 */
	function includeSectionEditor($sectionEditorId) {
		foreach ($this->sectionEditors as $key => $junk) {
			if ($this->sectionEditors[$key]->getId() == $sectionEditorId) {
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
			if ($this->sectionEditors[$key]->getId() == $sectionEditorId) {
				$this->omitSectionEditor =& $this->sectionEditors[$key];
			}
		}
	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		return $sectionDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();

		$section =& $this->section;
		$sectionId = (is_a($section, 'Section') ? $section->getId() : null);
		$templateMgr->assign('sectionId', $sectionId);
		$templateMgr->assign('commentsEnabled', $journal->getSetting('enableComments'));
		$templateMgr->assign('helpTopicId','journal.managementPages.sections');

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForms =& $reviewFormDao->getActiveByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId());
		$reviewFormOptions = array();
		while ($reviewForm =& $reviewForms->next()) {
			$reviewFormOptions[$reviewForm->getId()] = $reviewForm->getLocalizedTitle();
		}
		$templateMgr->assign_by_ref('reviewFormOptions', $reviewFormOptions);

		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$journal =& Request::getJournal();
		$sectionEditorsDao =& DAORegistry::getDAO('SectionEditorsDAO');
		$section =& $this->section;
		if (is_a($section, 'Section')) {
			$this->_data = array(
				'title' => $section->getTitle(null), // Localized
				'abbrev' => $section->getAbbrev(null), // Localized
				'reviewFormId' => $section->getReviewFormId(),
				'metaIndexed' => !$section->getMetaIndexed(), // #2066: Inverted
				'metaReviewed' => !$section->getMetaReviewed(), // #2066: Inverted
				'abstractsNotRequired' => $section->getAbstractsNotRequired(),
				'identifyType' => $section->getIdentifyType(null), // Localized
				'editorRestriction' => $section->getEditorRestricted(),
				'hideTitle' => $section->getHideTitle(),
				'hideAuthor' => $section->getHideAuthor(),
				'hideAbout' => $section->getHideAbout(),
				'disableComments' => $section->getDisableComments(),
				'policy' => $section->getPolicy(null), // Localized
				'assignedEditors' => $sectionEditorsDao->getEditorsBySectionId($journal->getId(), $section->getId()),
				'unassignedEditors' => $sectionEditorsDao->getEditorsNotInSection($journal->getId(), $section->getId()),
				'wordCount' => $section->getAbstractWordCount()
			);
		} else {
			$this->_data = array(
				'unassignedEditors' => $sectionEditorsDao->getEditorsNotInSection($journal->getId(), null)
			);
		}
		parent::initData();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'abbrev', 'policy', 'reviewFormId', 'identifyType', 'metaIndexed', 'metaReviewed', 'abstractsNotRequired', 'editorRestriction', 'hideTitle', 'hideAuthor', 'hideAbout', 'disableComments', 'wordCount'));

		$assignedEditorIds = Request::getUserVar('assignedEditorIds');
		if (empty($assignedEditorIds)) {
			$assignedEditorIds = array();
		} elseif (!is_array($assignedEditorIds)) {
			$assignedEditorIds = array($assignedEditorIds);
		}

		$assignedEditors = $unassignedEditors = array();

		foreach ($this->sectionEditors as $key => $junk) {
			$sectionEditor =& $this->sectionEditors[$key]; // Ref
			$userId = $sectionEditor->getId();

			$isIncludeEditor = $this->includeSectionEditor && $this->includeSectionEditor->getId() == $userId;
			$isOmitEditor = $this->omitSectionEditor && $this->omitSectionEditor->getId() == $userId;
			if ((in_array($userId, $assignedEditorIds) || $isIncludeEditor) && !$isOmitEditor) {
				$assignedEditors[] = array(
					'user' => &$sectionEditor,
					'canReview' => (Request::getUserVar('canReview' . $userId)?1:0),
					'canEdit' => (Request::getUserVar('canEdit' . $userId)?1:0)
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
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		// We get the section DAO early on so that
		// the section class will be imported.
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$section =& $this->section;
		if (!is_a($section, 'Section')) {
			$section = new Section();
			$section->setJournalId($journalId);
			$section->setSequence(REALLY_BIG_NUMBER);
		}

		$section->setTitle($this->getData('title'), null); // Localized
		$section->setAbbrev($this->getData('abbrev'), null); // Localized
		$reviewFormId = $this->getData('reviewFormId');
		if ($reviewFormId === '') $reviewFormId = null;
		$section->setReviewFormId($reviewFormId);
		$section->setMetaIndexed($this->getData('metaIndexed') ? 0 : 1); // #2066: Inverted
		$section->setMetaReviewed($this->getData('metaReviewed') ? 0 : 1); // #2066: Inverted
		$section->setAbstractsNotRequired($this->getData('abstractsNotRequired') ? 1 : 0);
		$section->setIdentifyType($this->getData('identifyType'), null); // Localized
		$section->setEditorRestricted($this->getData('editorRestriction') ? 1 : 0);
		$section->setHideTitle($this->getData('hideTitle') ? 1 : 0);
		$section->setHideAuthor($this->getData('hideAuthor') ? 1 : 0);
		$section->setHideAbout($this->getData('hideAbout') ? 1 : 0);
		$section->setDisableComments($this->getData('disableComments') ? 1 : 0);
		$section->setPolicy($this->getData('policy'), null); // Localized
		$section->setAbstractWordCount($this->getData('wordCount'));

		$section =& parent::execute($section);

		if ($section->getId() != null) {
			$sectionDao->updateSection($section);
			$sectionId = $section->getId();
		} else {
			$sectionId = $sectionDao->insertSection($section);
			$sectionDao->resequenceSections($journalId);
		}

		// Save assigned editors
		$assignedEditorIds = Request::getUserVar('assignedEditorIds');
		if (empty($assignedEditorIds)) $assignedEditorIds = array();
		elseif (!is_array($assignedEditorIds)) $assignedEditorIds = array($assignedEditorIds);
		$sectionEditorsDao =& DAORegistry::getDAO('SectionEditorsDAO');
		$sectionEditorsDao->deleteEditorsBySectionId($sectionId, $journalId);
		foreach ($this->sectionEditors as $key => $junk) {
			$sectionEditor =& $this->sectionEditors[$key];
			$userId = $sectionEditor->getId();
			// We don't have to worry about omit- and include-
			// section editors because this function is only called
			// when the Save button is pressed and those are only
			// used in other cases.
			if (in_array($userId, $assignedEditorIds)) $sectionEditorsDao->insertEditor(
				$journalId,
				$sectionId,
				$userId,
				Request::getUserVar('canReview' . $userId),
				Request::getUserVar('canEdit' . $userId)
			);
			unset($sectionEditor);
		}
	}
}

?>
