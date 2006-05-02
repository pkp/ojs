<?php

/**
 * AnnouncementTypeForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
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
		$journal = &Request::getJournal();

		parent::Form('manager/announcement/announcementTypeForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidator($this, 'typeName', 'required', 'manager.announcementTypes.form.typeNameRequired'));

		// Type name does not already exist for this journal
		if ($this->typeId == null) {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'manager.announcementTypes.form.typeNameExists', array(DAORegistry::getDAO('AnnouncementTypeDAO'), 'announcementTypeExistsByTypeName'), array($journal->getJournalId()), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'manager.announcementTypes.form.typeNameExists', create_function('$typeName, $journalId, $typeId', '$announcementTypeDao = &DAORegistry::getDAO(\'AnnouncementTypeDAO\'); $checkId = $announcementTypeDao->getAnnouncementTypeByTypeName($typeName, $journalId); return ($checkId == 0 || $checkId == $typeId) ? true : false;'), array($journal->getJournalId(), $this->typeId)));
		}
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
					'typeName' => $announcementType->getTypeName()
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
		$this->readUserVars(array('typeName'));
	
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
		$announcementType->setTypeName($this->getData('typeName'));

		// Update or insert announcement type
		if ($announcementType->getTypeId() != null) {
			$announcementTypeDao->updateAnnouncementType($announcementType);
		} else {
			$announcementTypeDao->insertAnnouncementType($announcementType);
		}
	}
	
}

?>
