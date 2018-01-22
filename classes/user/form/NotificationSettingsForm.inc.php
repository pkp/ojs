<?php

/**
 * @file classes/notification/form/NotificationSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationSettingsForm
 * @ingroup notification_form
 *
 * @brief Form to edit notification settings.
 */


import('lib.pkp.classes.notification.form.PKPNotificationSettingsForm');

class NotificationSettingsForm extends PKPNotificationSettingsForm {

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
	}

	/**
	 * Display the form.
	 * @return PKPRequest
	 */
	function display($request) {
		$templateMgr = TemplateManager::getManager($request);
 
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll(true);
		while ($thisJournal = $journals->next()) {
			if ($thisJournal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $thisJournal->getSetting('enableOpenAccessNotification')) {
				$templateMgr->assign('displayOpenAccessNotification', true);
			}
		}
 
		parent::display($request);
	}

	/**
	 * Save profile settings.
	 */
	function execute($request) {
		$user = $request->getUser();
 
		$journalDao = DAORegistry::getDAO('JournalDAO');
 
		$openAccessNotify = $request->getUserVar('openAccessNotify');
 
		$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
		$journals = $journalDao->getAll(true);
		while ($thisJournal = $journals->next()) {
			if ($thisJournal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $thisJournal->getSetting('enableOpenAccessNotification')) {
				$currentlyReceives = $user->getSetting('openAccessNotification', $thisJournal->getId());
				$shouldReceive = !empty($openAccessNotify) && in_array($thisJournal->getId(), $openAccessNotify);
				if ($currentlyReceives != $shouldReceive) {
					$userSettingsDao->updateSetting($user->getId(), 'openAccessNotification', $shouldReceive, 'bool', $thisJournal->getId());
				}
			}
		}
 
		parent::execute($request);
	}
}

?>
