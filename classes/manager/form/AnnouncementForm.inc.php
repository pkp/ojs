<?php

/**
 * @defgroup manager_form
 */

/**
 * @file classes/manager/form/AnnouncementForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementForm
 * @ingroup manager_form
 *
 * @brief Form for journal managers to create/edit announcements.
 */

import('lib.pkp.classes.manager.form.PKPAnnouncementForm');

class AnnouncementForm extends PKPAnnouncementForm {
	/**
	 * Constructor
	 * @param announcementId int leave as default for new announcement
	 */
	function AnnouncementForm($journalId, $announcementId = null) {
		parent::PKPAnnouncementForm($journalId, $announcementId);

		// If provided, announcement type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'optional', 'manager.announcements.form.typeIdValid', create_function('$typeId, $journalId', '$announcementTypeDao =& DAORegistry::getDAO(\'AnnouncementTypeDAO\'); return $announcementTypeDao->announcementTypeExistsByTypeId($typeId, ASSOC_TYPE_JOURNAL, $journalId);'), array($journalId)));
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
		$journalId = $this->getContextId();
		return array(ASSOC_TYPE_JOURNAL, $journalId);
	}

	/**
	 * Helper function to assign the AssocType and the AssocId
	 * @param Announcement the announcement to be modified
	 */
	function _setAnnouncementAssocId(&$announcement) {
		$journalId = $this->getContextId();
		$announcement->setAssocType(ASSOC_TYPE_JOURNAL);
		$announcement->setAssocId($journalId);
	}

	/**
	 * Save announcement.
	 * @param $request Request
	 */
	function execute(&$request) {
		$announcement = parent::execute();
		$journalId = $this->getContextId();

		// Send a notification to associated users
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$notificationUsers = array();
		$allUsers = $roleDao->getUsersByJournalId($journalId);
		while (!$allUsers->eof()) {
			$user =& $allUsers->next();
			$notificationUsers[] = array('id' => $user->getId());
			unset($user);
		}
		foreach ($notificationUsers as $userRole) {
			$notificationManager->createNotification(
				$request, $userRole['id'], NOTIFICATION_TYPE_NEW_ANNOUNCEMENT,
				$journalId, ASSOC_TYPE_ANNOUNCEMENT, $announcement->getId()
			);
		}
		$notificationManager->sendToMailingList($request,
			$notificationManager->createNotification(
				$request, UNSUBSCRIBED_USER_NOTIFICATION, NOTIFICATION_TYPE_NEW_ANNOUNCEMENT,
				$journalId, ASSOC_TYPE_ANNOUNCEMENT, $announcement->getId()
			)
		);
	}
}

?>
