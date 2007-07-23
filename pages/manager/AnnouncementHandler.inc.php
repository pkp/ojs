<?php

/**
 * @file AnnouncementHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class AnnouncementHandler
 *
 * Handle requests for announcement management functions. 
 *
 * $Id$
 */

class AnnouncementHandler extends ManagerHandler {

	function index() {
		AnnouncementHandler::announcements();
	}

	/**
	 * Display a list of announcements for the current journal.
	 */
	function announcements() {
		parent::validate();
		AnnouncementHandler::setupTemplate();

		$journal = &Request::getJournal();
		$rangeInfo = &Handler::getRangeInfo('announcements');
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
		$announcements = &$announcementDao->getAnnouncementsByJournalId($journal->getJournalId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('announcements', $announcements);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.announcements');
		$templateMgr->display('manager/announcement/announcements.tpl');
	}

	/**
	 * Delete an announcement.
	 * @param $args array first parameter is the ID of the announcement to delete
	 */
	function deleteAnnouncement($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
			$announcementId = (int) $args[0];
		
			$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');

			// Ensure announcement is for this journal
			if ($announcementDao->getAnnouncementJournalId($announcementId) == $journal->getJournalId()) {
				$announcementDao->deleteAnnouncementById($announcementId);
			}
		}
		
		Request::redirect(null, null, 'announcements');
	}

	/**
	 * Display form to edit an announcement.
	 * @param $args array optional, first parameter is the ID of the announcement to edit
	 */
	function editAnnouncement($args = array()) {
		parent::validate();
		AnnouncementHandler::setupTemplate();

		$journal = &Request::getJournal();
		$announcementId = !isset($args) || empty($args) ? null : (int) $args[0];
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');

		// Ensure announcement is valid and for this journal
		if (($announcementId != null && $announcementDao->getAnnouncementJournalId($announcementId) == $journal->getJournalId()) || ($announcementId == null)) {
			import('manager.form.AnnouncementForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'announcements'), 'manager.announcements'));

			if ($announcementId == null) {
				$templateMgr->assign('announcementTitle', 'manager.announcements.createTitle');
			} else {
				$templateMgr->assign('announcementTitle', 'manager.announcements.editTitle');	
			}

			$announcementForm = &new AnnouncementForm($announcementId);
			$announcementForm->initData();
			$announcementForm->display();

		} else {
				Request::redirect(null, null, 'announcements');
		}
	}

	/**
	 * Display form to create new announcement.
	 */
	function createAnnouncement() {
		AnnouncementHandler::editAnnouncement();
	}

	/**
	 * Save changes to an announcement.
	 */
	function updateAnnouncement() {
		parent::validate();
		
		import('manager.form.AnnouncementForm');
		
		$journal = &Request::getJournal();
		$announcementId = Request::getUserVar('announcementId') == null ? null : (int) Request::getUserVar('announcementId');
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');

		if (($announcementId != null && $announcementDao->getAnnouncementJournalId($announcementId) == $journal->getJournalId()) || $announcementId == null) {

			$announcementForm = &new AnnouncementForm($announcementId);
			$announcementForm->readInputData();
			
			if ($announcementForm->validate()) {
				$announcementForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, 'createAnnouncement');
				} else {
					Request::redirect(null, null, 'announcements');
				}
				
			} else {
				AnnouncementHandler::setupTemplate();

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'announcements'), 'manager.announcements'));

				if ($announcementId == null) {
					$templateMgr->assign('announcementTitle', 'manager.announcements.createTitle');
				} else {
					$templateMgr->assign('announcementTitle', 'manager.announcements.editTitle');	
				}

				$announcementForm->display();
			}
			
		} else {
				Request::redirect(null, null, 'announcements');
		}	
	}	

	/**
	 * Display a list of announcement types for the current journal.
	 */
	function announcementTypes() {
		parent::validate();
		AnnouncementHandler::setupTemplate(true);

		$journal = &Request::getJournal();
		$rangeInfo = &Handler::getRangeInfo('announcementTypes');
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypes = &$announcementTypeDao->getAnnouncementTypesByJournalId($journal->getJournalId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('announcementTypes', $announcementTypes);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.announcements');
		$templateMgr->display('manager/announcement/announcementTypes.tpl');
	}

	/**
	 * Delete an announcement type.
	 * @param $args array first parameter is the ID of the announcement type to delete
	 */
	function deleteAnnouncementType($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
			$typeId = (int) $args[0];
		
			$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');

			// Ensure announcement is for this journal
			if ($announcementTypeDao->getAnnouncementTypeJournalId($typeId) == $journal->getJournalId()) {
				$announcementTypeDao->deleteAnnouncementTypeById($typeId);
			}
		}
		
		Request::redirect(null, null, 'announcementTypes');
	}

	/**
	 * Display form to edit an announcement type.
	 * @param $args array optional, first parameter is the ID of the announcement type to edit
	 */
	function editAnnouncementType($args = array()) {
		parent::validate();
		AnnouncementHandler::setupTemplate(true);

		$journal = &Request::getJournal();
		$typeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');

		// Ensure announcement type is valid and for this journal
		if (($typeId != null && $announcementTypeDao->getAnnouncementTypeJournalId($typeId) == $journal->getJournalId()) || ($typeId == null)) {
			import('manager.form.AnnouncementTypeForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'announcementTypes'), 'manager.announcementTypes'));

			if ($typeId == null) {
				$templateMgr->assign('announcementTypeTitle', 'manager.announcementTypes.createTitle');
			} else {
				$templateMgr->assign('announcementTypeTitle', 'manager.announcementTypes.editTitle');	
			}

			$announcementTypeForm = &new AnnouncementTypeForm($typeId);
			$announcementTypeForm->initData();
			$announcementTypeForm->display();

		} else {
				Request::redirect(null, null, 'announcementTypes');
		}
	}

	/**
	 * Display form to create new announcement type.
	 */
	function createAnnouncementType() {
		AnnouncementHandler::editAnnouncementType();
	}

	/**
	 * Save changes to an announcement type.
	 */
	function updateAnnouncementType() {
		parent::validate();
		
		import('manager.form.AnnouncementTypeForm');
		
		$journal = &Request::getJournal();
		$typeId = Request::getUserVar('typeId') == null ? null : (int) Request::getUserVar('typeId');
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');

		if (($typeId != null && $announcementTypeDao->getAnnouncementTypeJournalId($typeId) == $journal->getJournalId()) || $typeId == null) {

			$announcementTypeForm = &new AnnouncementTypeForm($typeId);
			$announcementTypeForm->readInputData();
			
			if ($announcementTypeForm->validate()) {
				$announcementTypeForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, 'createAnnouncementType');
				} else {
					Request::redirect(null, null, 'announcementTypes');
				}
				
			} else {
				AnnouncementHandler::setupTemplate(true);

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'announcementTypes'), 'manager.announcementTypes'));

				if ($typeId == null) {
					$templateMgr->assign('announcementTypeTitle', 'manager.announcementTypes.createTitle');
				} else {
					$templateMgr->assign('announcementTypeTitle', 'manager.announcementTypes.editTitle');	
				}

				$announcementTypeForm->display();
			}
			
		} else {
				Request::redirect(null, null, 'announcementTypes');
		}	
	}	

	function setupTemplate($subclass = false) {
		parent::setupTemplate(true);
		if ($subclass) {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'announcements'), 'manager.announcements'));
		}
	}
}

?>
