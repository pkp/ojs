<?php

/**
 * @file JournalLanguagesHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalLanguagesHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing journal language settings.
 */

// $Id$

import('pages.manager.ManagerHandler');

class JournalLanguagesHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function JournalLanguagesHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display form to edit language settings.
	 */
	function languages() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		$settingsForm = new LanguageSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}

	/**
	 * Save changes to language settings.
	 * @param $args array
	 * @param $request object
	 */
	function saveLanguageSettings($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		$settingsForm = new LanguageSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();
			import('notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification('notification.notification', 'common.changesSaved');
			$request->redirect(null, null, 'index');
		} else {
			$settingsForm->display();
		}
	}

	/**
	 * Reload the default localized settings for the journal.
	 * @param $args array
	 * @param $request object
	 */
	function reloadLocalizedDefaultSettings($args, &$request) {
		// make sure the locale is valid
		$locale = $request->getUserVar('localeToLoad');
		if ( !Locale::isLocaleValid($locale) ) {
			$request->redirect(null, null, 'languages');
		}

		$this->validate();
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao->reloadLocalizedDefaultSettings(
			$journal->getId(), 'registry/journalSettings.xml',
			array(
				'indexUrl' => $request->getIndexUrl(),
				'journalPath' => $journal->getData('path'),
				'primaryLocale' => $journal->getPrimaryLocale(),
				'journalName' => $journal->getTitle($journal->getPrimaryLocale())
			),
			$locale
		);

		// Display a notification
		import('notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$notificationManager->createTrivialNotification('notification.notification', 'common.changesSaved');
		$request->redirect(null, null, 'languages');
	}
}

?>
