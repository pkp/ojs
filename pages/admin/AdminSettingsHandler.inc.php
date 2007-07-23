<?php

/**
 * @file AdminSettingsHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 * @class AdminSettingsHandler
 *
 * Handle requests for changing site admin settings. 
 *
 * $Id$
 */

class AdminSettingsHandler extends AdminHandler {
	
	/**
	 * Display form to modify site settings.
	 */
	function settings() {
		parent::validate();
		parent::setupTemplate(true);
		
		import('admin.form.SiteSettingsForm');

		$settingsForm = &new SiteSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}
	
	/**
	 * Validate and save changes to site settings.
	 */
	function saveSettings() {
		parent::validate();
		parent::setupTemplate(true);
		
		import('admin.form.SiteSettingsForm');
		
		$settingsForm = &new SiteSettingsForm();
		$settingsForm->readInputData();

		if (Request::getUserVar('uploadSiteStyleSheet')) {
			$site =& Request::getSite();
			if (!$settingsForm->uploadSiteStyleSheet()) {
				$settingsForm->addError('siteStyleSheet', 'admin.settings.siteStyleSheetInvalid');
			}
		} elseif (Request::getUserVar('deleteSiteStyleSheet')) {
			$site =& Request::getSite();
			$publicFileManager =& new PublicFileManager();
			$publicFileManager->removeSiteFile($site->getSiteStyleFilename());
		} elseif ($settingsForm->validate()) {
			$settingsForm->execute();
		
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, 'settings'),
				'pageTitle' => 'admin.siteSettings',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, Request::getRequestedPage()),
				'backLinkLabel' => 'admin.siteAdmin'
			));
			$templateMgr->display('common/message.tpl');
			exit();
		}
		$settingsForm->display();
	}
	
}

?>
