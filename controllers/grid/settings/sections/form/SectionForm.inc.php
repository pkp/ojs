<?php

/**
 * @file controllers/grid/settings/sections/form/SectionForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionForm
 * @ingroup controllers_grid_settings_section_form
 *
 * @brief Form for adding/editing a section
 */

import('lib.pkp.controllers.grid.settings.sections.form.PKPSectionForm');

class SectionForm extends PKPSectionForm {

	/**
	 * Constructor.
	 * @param $request Request
	 * @param $sectionId int optional
	 */
	function __construct($request, $sectionId = null) {
		parent::__construct(
			$request,
			'controllers/grid/settings/sections/form/sectionForm.tpl',
			$sectionId
		);

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.setup.form.section.nameRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
		$journal = $request->getJournal();
		$this->addCheck(new FormValidatorCustom($this, 'reviewFormId', 'optional', 'manager.sections.form.reviewFormId', array(DAORegistry::getDAO('ReviewFormDAO'), 'reviewFormExists'), array(ASSOC_TYPE_JOURNAL, $journal->getId())));
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$request = Application::getRequest();
		$journal = $request->getJournal();

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sectionId = $this->getSectionId();
		if ($sectionId) {
			$section = $sectionDao->getById($sectionId, $journal->getId());
		}

		if (isset($section) ) {
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
				'policy' => $section->getPolicy(null), // Localized
				'wordCount' => $section->getAbstractWordCount(),
				'subEditors' => $this->_getAssignedSubEditorIds($sectionId, $journal->getId()),
			);
		}

		parent::initData();
	}

	/**
	 * Fetch form contents
	 * @param $request Request
	 * @see Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('sectionId', $this->getSectionId());

		$journal = $request->getJournal();

		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewForms = $reviewFormDao->getActiveByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId());
		$reviewFormOptions = array();
		while ($reviewForm = $reviewForms->next()) {
			$reviewFormOptions[$reviewForm->getId()] = $reviewForm->getLocalizedTitle();
		}
		$templateMgr->assign('reviewFormOptions', $reviewFormOptions);

		// Series Editors
		$sectionEditorsListData = $this->_getSubEditorsListPanelData($journal->getId(), $request);
		$templateMgr->assign(array(
			'hasSubEditors' => !empty($sectionEditorsListData['items']),
			'subEditorsListData' => json_encode($sectionEditorsListData),
		));

		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array('abbrev', 'policy', 'reviewFormId', 'identifyType', 'metaIndexed', 'metaReviewed', 'abstractsNotRequired', 'editorRestriction', 'hideTitle', 'hideAuthor', 'wordCount'));
	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		return $sectionDao->getLocaleFieldNames();
	}

	/**
	 * Save section.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return mixed
	 */
	function execute($args, $request) {
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$journal = $request->getJournal();

		// Get or create the section object
		if ($this->getSectionId()) {
			$section = $sectionDao->getById($this->getSectionId(), $journal->getId());
		} else {
			import('classes.journal.Section');
			$section = $sectionDao->newDataObject();
			$section->setJournalId($journal->getId());
		}

		// Populate/update the section object from the form
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
		$section->setPolicy($this->getData('policy'), null); // Localized
		$section->setAbstractWordCount($this->getData('wordCount'));

		// Insert or update the section in the DB
		if ($this->getSectionId()) {
			$sectionDao->updateObject($section);
		} else {
			$section->setSequence(REALLY_BIG_NUMBER);
			$this->setSectionId($sectionDao->insertObject($section));
			$sectionDao->resequenceSections($journal->getId());
		}

		// Update section editors
		$this->_saveSubEditors($journal->getId());

		return parent::execute($section, $request);
	}
}

?>
