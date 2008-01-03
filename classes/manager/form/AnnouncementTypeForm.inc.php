<?php

/**
 * @file AnnouncementTypeForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class AnnouncementTypeForm
 *
 * Form for journal managers to create/edit announcement types.
 *
 * $Id$
 */

import('form.Form');

class AnnouncementTypeForm extends Form {
	/** @var typeId int the ID of the announcement type being edited */
	var $typeId;

	/**
	 * Constructor
	 * @param typeId int leave as default for new announcement type
	 */
	function AnnouncementTypeForm($typeId = null) {
		$this->typeId = isset($typeId) ? (int) $typeId : null;

		parent::Form('manager/announcement/announcementTypeForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.announcementTypes.form.typeNameRequired'));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		return $announcementTypeDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('typeId', $this->typeId);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.announcements');

		parent::display();
	}

	/**
	 * Initialize form data from current announcement type.
	 */
	function initData() {
		if (isset($this->typeId)) {
			$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
			$announcementType = &$announcementTypeDao->getAnnouncementType($this->typeId);

			if ($announcementType != null) {
				$this->_data = array(
					'name' => $announcementType->getName(null) // Localized
				);

			} else {
				$this->typeId = null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name'));
	}

	/**
	 * Save announcement type. 
	 */
	function execute() {
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
		$journal = &Request::getJournal();

		if (isset($this->typeId)) {
			$announcementType = &$announcementTypeDao->getAnnouncementType($this->typeId);
		}

		if (!isset($announcementType)) {
			$announcementType = &new AnnouncementType();
		}

		$announcementType->setJournalId($journal->getJournalId());
		$announcementType->setName($this->getData('name'), null); // Localized

		// Update or insert announcement type
		if ($announcementType->getTypeId() != null) {
			$announcementTypeDao->updateAnnouncementType($announcementType);
		} else {
			$announcementTypeDao->insertAnnouncementType($announcementType);
		}
	}
}

?>
