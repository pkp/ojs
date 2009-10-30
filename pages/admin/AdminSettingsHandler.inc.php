<?php

/**
 * @file AdminSettingsHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminSettingsHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for changing site admin settings. 
 *
 * $Id$
 */

import('pages.admin.AdminHandler');

class AdminSettingsHandler extends AdminHandler {
	/**
	 * Constructor
	 **/
	function AdminSettingsHandler() {
		parent::AdminHandler();
	}

	/**
	 * Display form to modify site settings.
	 */
	function settings() {
		$this->validate();
		$this->setupTemplate(true);

		import('admin.form.SiteSettingsForm');

		$settingsForm = new SiteSettingsForm();
		if ($settingsForm->isLocaleResubmit()) {
			$settingsForm->readInputData();
		} else {
			$settingsForm->initData();
		}
		$settingsForm->display();
	}

	/**
	 * Validate and save changes to site settings.
	 */
	function saveSettings() {
		$this->validate();
		$this->setupTemplate(true);
		$site =& Request::getSite();

		import('admin.form.SiteSettingsForm');

		$settingsForm = new SiteSettingsForm();
		$settingsForm->readInputData();

		if (Request::getUserVar('uploadSiteStyleSheet')) {
			if (!$settingsForm->uploadSiteStyleSheet()) {
				$settingsForm->addError('siteStyleSheet', Locale::translate('admin.settings.siteStyleSheetInvalid'));
			}
		} elseif (Request::getUserVar('deleteSiteStyleSheet')) {
			$publicFileManager = new PublicFileManager();
			$publicFileManager->removeSiteFile($site->getSiteStyleFilename());
		} elseif (Request::getUserVar('uploadPageHeaderTitleImage')) {
			if (!$settingsForm->uploadPageHeaderTitleImage($settingsForm->getFormLocale())) {
				$settingsForm->addError('pageHeaderTitleImage', Locale::translate('admin.settings.homeHeaderImageInvalid'));
			}
		} elseif (Request::getUserVar('deletePageHeaderTitleImage')) {
			$publicFileManager = new PublicFileManager();
			$setting = $site->getSetting('pageHeaderTitleImage');
			$formLocale = $settingsForm->getFormLocale();
			if (isset($setting[$formLocale])) {
				$publicFileManager->removeSiteFile($setting[$formLocale]['uploadName']);
				$setting[$formLocale] = array();
				$site->updateSetting('pageHeaderTitleImage', $setting, 'object', true);

				// Refresh site header
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('displayPageHeaderTitle', $site->getLocalizedPageHeaderTitle());
			}
		} elseif ($settingsForm->validate()) {
			$settingsForm->execute();
			$templateMgr =& TemplateManager::getManager();
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
