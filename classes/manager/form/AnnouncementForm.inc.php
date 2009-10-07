<?php

/**
 * @defgroup manager_form
 */

/**
 * @file classes/manager/form/AnnouncementForm.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementForm
 * @ingroup manager_form
 *
 * @brief Form for journal managers to create/edit announcements.
 */

// $Id$

import('manager.form.PKPAnnouncementForm');

class AnnouncementForm extends PKPAnnouncementForm {
	/**
	 * Constructor
	 * @param announcementId int leave as default for new announcement
	 */
	function AnnouncementForm($announcementId = null) {
		parent::PKPAnnouncementForm($announcementId);
		$journal =& Request::getJournal();

		// If provided, announcement type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'optional', 'manager.announcements.form.typeIdValid', create_function('$typeId, $journalId', '$announcementTypeDao =& DAORegistry::getDAO(\'AnnouncementTypeDAO\'); return $announcementTypeDao->announcementTypeExistsByTypeId($typeId, ASSOC_TYPE_JOURNAL, $journalId);'), array($journal->getJournalId())));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.announcements');
		parent::display();
	}

	function _getAnnouncementTypesAssocId() {
		$journal =& Request::getJournal();
		return array(ASSOC_TYPE_JOURNAL, $journal->getJournalId());
	}

	/**
	 * Helper function to assign the AssocType and the AssocId
	 * @param Announcement the announcement to be modified
	 */
	function _setAnnouncementAssocId(&$announcement) {
		$journal =& Request::getJournal();
		$announcement->setAssocType(ASSOC_TYPE_JOURNAL);
		$announcement->setAssocId($journal->getJournalId());
	}

	/**
	 * Save announcement.
	 */
	function execute() {
		parent::execute();
		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();

		// Send a notification to associated users
		import('notification.Notification');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$notificationUsers = array();
		$allUsers = $roleDao->getUsersByJournalId($journalId);
		while (!$allUsers->eof()) {
			$user =& $allUsers->next();
			$notificationUsers[] = array('id' => $user->getId());
			unset($user);
		}
		$url = Request::url(null, 'announcement', 'view', array(1));
		foreach ($notificationUsers as $userRole) {
			Notification::createNotification($userRole['id'], "notification.type.newAnnouncement",
				null, $url, 1, NOTIFICATION_TYPE_NEW_ANNOUNCEMENT);
		}
		$notificationDao =& DAORegistry::getDAO('NotificationDAO');
		$notificationDao->sendToMailingList(Notification::createNotification(0, "notification.type.newAnnouncement",
				null, $url, 1, NOTIFICATION_TYPE_NEW_ANNOUNCEMENT));
	}
}

?>
