<?php

/**
 * @file JournalLanguagesHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class JournalLanguagesHandler
 *
 * Handle requests for changing journal language settings. 
 *
 * $Id$
 */

class JournalLanguagesHandler extends ManagerHandler {

	/**
	 * Display form to edit language settings.
	 */
	function languages() {
		parent::validate();
		parent::setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		$settingsForm = &new LanguageSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}

	/**
	 * Save changes to language settings.
	 */
	function saveLanguageSettings() {
		parent::validate();
		parent::setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		$settingsForm = &new LanguageSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, 'languages'),
				'pageTitle' => 'common.languages',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, Request::getRequestedPage()),
				'backLinkLabel' => 'manager.journalManagement'
			));
			$templateMgr->display('common/message.tpl');

		} else {
			$settingsForm->display();
		}
	}
	
	function reloadLocalizedDefaultSettings() {
		// make sure the locale is valid
		$locale = Request::getUserVar('localeToLoad');
		if ( !Locale::isLocaleValid($locale) ) {
			Request::redirect(null, null, 'languages');
		}

		parent::validate();
		parent::setupTemplate(true);
					
		$journal = &Request::getJournal();
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao->reloadLocalizedDefaultSettings($journal->getJournalId(), 'registry/journalSettings.xml', array(
				'indexUrl' => Request::getIndexUrl(),
				'journalPath' => $journal->getData('path'),
				'primaryLocale' => $journal->getPrimaryLocale(),
				'journalName' => $journal->getTitle($journal->getPrimaryLocale())
			),
			$locale);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'currentUrl' => Request::url(null, null, 'languages'),
			'pageTitle' => 'common.languages',
			'message' => 'common.changesSaved',
			'backLink' => Request::url(null, Request::getRequestedPage()),
			'backLinkLabel' => 'manager.journalManagement'
		));
		$templateMgr->display('common/message.tpl');
	}

	

}
?>
