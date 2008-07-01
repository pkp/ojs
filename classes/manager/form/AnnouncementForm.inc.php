<?php

/**
 * @defgroup manager_form
 */
 
/**
 * @file classes/manager/form/AnnouncementForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementForm
 * @ingroup manager_form
 *
 * @brief Form for journal managers to create/edit announcements.
 */

// $Id$


import('form.Form');

class AnnouncementForm extends Form {
	/** @var announcementId int the ID of the announcement being edited */
	var $announcementId;

	/**
	 * Constructor
	 * @param announcementId int leave as default for new announcement
	 */
	function AnnouncementForm($announcementId = null) {

		$this->announcementId = isset($announcementId) ? (int) $announcementId : null;
		$journal = &Request::getJournal();

		parent::Form('manager/announcement/announcementForm.tpl');

		// If provided, announcement type is valid 
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'optional', 'manager.announcements.form.typeIdValid', create_function('$typeId, $journalId', '$announcementTypeDao = &DAORegistry::getDAO(\'AnnouncementTypeDAO\'); return $announcementTypeDao->announcementTypeExistsByTypeId($typeId, $journalId);'), array($journal->getJournalId())));

		// Title is provided
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.announcements.form.titleRequired'));

		// Short description is provided
		$this->addCheck(new FormValidatorLocale($this, 'descriptionShort', 'required', 'manager.announcements.form.descriptionShortRequired'));

		// Description is provided
		$this->addCheck(new FormValidatorLocale($this, 'description', 'required', 'manager.announcements.form.descriptionRequired'));

		// If provided, expiry date is valid
		$this->addCheck(new FormValidatorCustom($this, 'dateExpireYear', 'optional', 'manager.announcements.form.dateExpireValid', create_function('$dateExpireYear', '$minYear = date(\'Y\'); $maxYear = date(\'Y\') + ANNOUNCEMENT_EXPIRE_YEAR_OFFSET_FUTURE; return ($dateExpireYear >= $minYear && $dateExpireYear <= $maxYear) ? true : false;')));

		$this->addCheck(new FormValidatorCustom($this, 'dateExpireYear', 'optional', 'manager.announcements.form.dateExpireYearIncompleteDate', create_function('$dateExpireYear, $form', '$dateExpireMonth = $form->getData(\'dateExpireMonth\'); $dateExpireDay = $form->getData(\'dateExpireDay\'); return ($dateExpireMonth != null && $dateExpireDay != null) ? true : false;'), array(&$this)));

		$this->addCheck(new FormValidatorCustom($this, 'dateExpireMonth', 'optional', 'manager.announcements.form.dateExpireValid', create_function('$dateExpireMonth', 'return ($dateExpireMonth >= 1 && $dateExpireMonth <= 12) ? true : false;')));

		$this->addCheck(new FormValidatorCustom($this, 'dateExpireMonth', 'optional', 'manager.announcements.form.dateExpireMonthIncompleteDate', create_function('$dateExpireMonth, $form', '$dateExpireYear = $form->getData(\'dateExpireYear\'); $dateExpireDay = $form->getData(\'dateExpireDay\'); return ($dateExpireYear != null && $dateExpireDay != null) ? true : false;'), array(&$this)));

		$this->addCheck(new FormValidatorCustom($this, 'dateExpireDay', 'optional', 'manager.announcements.form.dateExpireValid', create_function('$dateExpireDay', 'return ($dateExpireDay >= 1 && $dateExpireDay <= 31) ? true : false;')));

		$this->addCheck(new FormValidatorCustom($this, 'dateExpireDay', 'optional', 'manager.announcements.form.dateExpireDayIncompleteDate', create_function('$dateExpireDay, $form', '$dateExpireYear = $form->getData(\'dateExpireYear\'); $dateExpireMonth = $form->getData(\'dateExpireMonth\'); return ($dateExpireYear != null && $dateExpireMonth != null) ? true : false;'), array(&$this)));
		$this->addCheck(new FormValidatorPost($this));

	}

	/**
	 * Get the list of localized field names for this object
	 * @return array
	 */
	function getLocaleFieldNames() {
		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		return $announcementDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$journal = &Request::getJournal();

		$templateMgr->assign('announcementId', $this->announcementId);
		$templateMgr->assign('yearOffsetFuture', ANNOUNCEMENT_EXPIRE_YEAR_OFFSET_FUTURE);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.announcements');

		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypes = &$announcementTypeDao->getAnnouncementTypesByJournalId($journal->getJournalId());
		$templateMgr->assign('announcementTypes', $announcementTypes);

		parent::display();
	}

	/**
	 * Initialize form data from current announcement.
	 */
	function initData() {
		if (isset($this->announcementId)) {
			$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
			$announcement = &$announcementDao->getAnnouncement($this->announcementId);

			if ($announcement != null) {
				$this->_data = array(
					'typeId' => $announcement->getTypeId(),
					'title' => $announcement->getTitle(null), // Localized
					'descriptionShort' => $announcement->getDescriptionShort(null), // Localized
					'description' => $announcement->getDescription(null), // Localized
					'dateExpire' => $announcement->getDateExpire()
				);

			} else {
				$this->announcementId = null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('typeId', 'title', 'descriptionShort', 'description', 'dateExpireYear', 'dateExpireMonth', 'dateExpireDay'));
		$this->_data['dateExpire'] = $this->_data['dateExpireYear'] . '-' . $this->_data['dateExpireMonth'] . '-' . $this->_data['dateExpireDay'];

	}

	/**
	 * Save announcement. 
	 */
	function execute() {
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
		$journal = &Request::getJournal();

		if (isset($this->announcementId)) {
			$announcement = &$announcementDao->getAnnouncement($this->announcementId);
		}

		if (!isset($announcement)) {
			$announcement = &new Announcement();
		}

		$announcement->setJournalId($journal->getJournalId());
		$announcement->setTitle($this->getData('title'), null); // Localized
		$announcement->setDescriptionShort($this->getData('descriptionShort'), null); // Localized
		$announcement->setDescription($this->getData('description'), null); // Localized

		if ($this->getData('typeId') != null) {
			$announcement->setTypeId($this->getData('typeId'));
		} else {
			$announcement->setTypeId(null);
		}

		if ($this->getData('dateExpireYear') != null) {
			$announcement->setDateExpire($this->getData('dateExpireYear') . '-' . $this->getData('dateExpireMonth') . '-' . $this->getData('dateExpireDay'));
		} else {
			$announcement->setDateExpire(null);
		}

		// Update or insert announcement
		if ($announcement->getAnnouncementId() != null) {
			$announcementDao->updateAnnouncement($announcement);
		} else {
			$announcement->setDatetimePosted(Core::getCurrentDate());
			$announcementDao->insertAnnouncement($announcement);
		}
	}
}

?>
