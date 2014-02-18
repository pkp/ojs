<?php

/**
 * @file controllers/grid/settings/sections/form/SectionForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionForm
 * @ingroup controllers_grid_settings_section_form
 *
 * @brief Form for adding/edditing a section
 * stores/retrieves from an associative array
 */

import('lib.pkp.classes.form.Form');

class SectionForm extends Form {
	/** the id for the section being edited **/
	var $_sectionId;

	/** @var int The current user ID */
	var $_userId;

	/** @var string Cover image extension */
	var $_imageExtension;

	/** @var array Cover image information from getimagesize */
	var $_sizeArray;

	/**
	 * Constructor.
	 */
	function SectionForm($request, $sectionId = null) {
		$this->setSectionId($sectionId);

		$journal = $request->getJournal();
		$user = $request->getUser();
		$this->_userId = $user->getId();

		parent::Form('controllers/grid/settings/sections/form/sectionForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.setup.form.section.nameRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCustom($this, 'reviewFormId', 'optional', 'manager.sections.form.reviewFormId', array(DAORegistry::getDAO('ReviewFormDAO'), 'reviewFormExists'), array(ASSOC_TYPE_JOURNAL, $journal->getId())));

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
	}

	/**
	 * Initialize form data from current settings.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, $request) {
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
				'hideAbout' => $section->getHideAbout(),
				'disableComments' => $section->getDisableComments(),
				'policy' => $section->getPolicy(null), // Localized
				'wordCount' => $section->getAbstractWordCount()
			);
		}
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('sectionId', $this->getSectionId());


		$journal = $request->getJournal();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$sectionEditorCount = $userGroupDao->getContextUsersCount($journal->getId(), null, ROLE_ID_SUB_EDITOR);
		$templateMgr->assign('sectionEditorCount', $sectionEditorCount);
		$templateMgr->assign('commentsEnabled', $journal->getSetting('enableComments'));
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewForms = $reviewFormDao->getActiveByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId());
		$reviewFormOptions = array();
		while ($reviewForm = $reviewForms->next()) {
			$reviewFormOptions[$reviewForm->getId()] = $reviewForm->getLocalizedTitle();
		}
		$templateMgr->assign('reviewFormOptions', $reviewFormOptions);

		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'abbrev', 'policy', 'reviewFormId', 'identifyType', 'metaIndexed', 'metaReviewed', 'abstractsNotRequired', 'editorRestriction', 'hideTitle', 'hideAuthor', 'hideAbout', 'disableComments', 'wordCount', 'sectionEditors'));
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
		$section->setHideAbout($this->getData('hideAbout') ? 1 : 0);
		$section->setDisableComments($this->getData('disableComments') ? 1 : 0);
		$section->setPolicy($this->getData('policy'), null); // Localized
		$section->setAbstractWordCount($this->getData('wordCount'));

		$section = parent::execute($section);

		// Insert or update the section in the DB
		if ($this->getSectionId()) {
			$sectionDao->updateObject($section);
		} else {
			$this->setSectionId($sectionDao->insertObject($section));
		}

		import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');
		// Save the section editor associations.
		ListbuilderHandler::unpack(
			$request,
			$this->getData('sectionEditors'),
			array(&$this, 'deleteSectionEditorEntry'),
			array(&$this, 'insertSectionEditorEntry'),
			array(&$this, 'updateSectionEditorEntry')
		);

		return true;
	}

	/**
	 * Persist a section editor association
	 * @see ListbuilderHandler::insertEntry
	 */
	function insertSectionEditorEntry($request, $newRowId) {
		$journal = $request->getJournal();
		$sectionId = $this->getSectionId();
		$userId = array_shift($newRowId);

		$sectionEditorsDao = DAORegistry::getDAO('SectionEditorsDAO');

		// Make sure the membership doesn't already exist
		if ($sectionEditorsDao->editorExists($journal->getId(), $this->getSectionId(), $userId)) {
			return false;
		}

		// Otherwise, insert the row.
		$sectionEditorsDao->insertEditor($journal->getId(), $this->getSectionId(), $userId, true, true);
		return true;
	}

	/**
	 * Delete a section editor association with this section.
	 * @see ListbuilderHandler::deleteEntry
	 * @param $request PKPRequest
	 * @param $rowId int
	 */
	function deleteSectionEditorEntry($request, $rowId) {
		$sectionEditorsDao = DAORegistry::getDAO('SectionEditorsDAO');
		$journal = $request->getJournal();

		$sectionEditorsDao->deleteEditor($journal->getId(), $this->getSectionId(), $rowId);
		return true;
	}

	/**
	 * Update a section editor association with this section.
	 * @see ListbuilderHandler::deleteEntry
	 * @param $request PKPRequest
	 * @param $rowId int the old section editor
	 * @param $newRowId array the new section editor
	 */
	function updateSectionEditorEntry($request, $rowId, $newRowId) {
		$this->deleteSectionEditorEntry($request, $rowId);
		$this->insertSectionEditorEntry($request, $newRowId);
		return true;
	}

	/**
	 * Get the section ID for this section.
	 * @return int
	 */
	function getSectionId() {
		return $this->_sectionId;
	}

	/**
	 * Set the section ID for this section.
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		$this->_sectionId = $sectionId;
	}
}

?>
