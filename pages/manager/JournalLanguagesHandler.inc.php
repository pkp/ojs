<?php

/**
 * JournalLanguagesHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
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
				'currentUrl' => Request::getPageUrl() . '/manager/languages',
				'pageTitle' => 'common.languages',
				'message' => 'common.changesSaved',
				'backLink' => Request::getPageUrl() . '/manager',
				'backLinkLabel' => 'manager.journalManagement'
			));
			$templateMgr->display('common/message.tpl');
			
		} else {
			$settingsForm->display();
		}
	}
	
}
?>
