<?php

/**
 * @file controllers/grid/settings/sections/form/SectionForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
		parent::__construct(
			$request,
			'controllers/grid/settings/sections/form/sectionForm.tpl',
			$sectionId
		);

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.setup.form.section.nameRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
		$journal = $request->getJournal();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$request = Application::get()->getRequest();
		$journal = $request->getJournal();

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sectionId = $this->getSectionId();
		if ($sectionId) {
			$section = $sectionDao->getById($sectionId, $journal->getId());
		}

		if (isset($section) ) {
			$this->setData(array(
				'title' => $section->getTitle(null), // Localized
				'abbrev' => $section->getAbbrev(null), // Localized
				'metaIndexed' => !$section->getMetaIndexed(), // #2066: Inverted
				'abstractsNotRequired' => $section->getAbstractsNotRequired(),
				'identifyType' => $section->getIdentifyType(null), // Localized
				'editorRestriction' => $section->getEditorRestricted(),
				'hideTitle' => $section->getHideTitle(),
				'hideAuthor' => $section->getHideAuthor(),
				'policy' => $section->getPolicy(null), // Localized
				'wordCount' => $section->getAbstractWordCount(),
				'subEditors' => $this->_getAssignedSubEditorIds($sectionId, $journal->getId()),
			));
		}

		parent::initData();
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('sectionId', $this->getSectionId());

		$journal = $request->getJournal();

		// Section/Series Editors
		$subEditorsListPanel = $this->_getSubEditorsListPanel($journal->getId(), $request);
		$templateMgr->assign(array(
			'hasSubEditors' => !empty($subEditorsListPanel->items),
			'subEditorsListData' => [
				'components' => [
					'subeditors' => $subEditorsListPanel->getConfig(),
				]
			]
		));

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array('abbrev', 'policy', 'identifyType', 'metaIndexed', 'abstractsNotRequired', 'editorRestriction', 'hideTitle', 'hideAuthor', 'wordCount'));
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
	 * @return mixed
	 */
	function execute() {
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$journal = Application::get()->getRequest()->getJournal();

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
		$section->setMetaIndexed($this->getData('metaIndexed') ? 0 : 1); // #2066: Inverted
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

		return parent::execute();
	}
}
