<?php

/**
 * @file AdminSettingsHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
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

		import('classes.admin.form.SiteSettingsForm');

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
	 * @param $args array
	 * @param $request object
	 */
	function saveSettings($args, $request) {
		$this->validate();
		$this->setupTemplate(true);
		$site =& $request->getSite();

		import('classes.admin.form.SiteSettingsForm');

		$settingsForm = new SiteSettingsForm();
		$settingsForm->readInputData();

		if ($request->getUserVar('uploadSiteStyleSheet')) {
			if (!$settingsForm->uploadSiteStyleSheet()) {
				$settingsForm->addError('siteStyleSheet', Locale::translate('admin.settings.siteStyleSheetInvalid'));
			}
		} elseif ($request->getUserVar('deleteSiteStyleSheet')) {
			$publicFileManager = new PublicFileManager();
			$publicFileManager->removeSiteFile($site->getSiteStyleFilename());
		} elseif ($request->getUserVar('uploadPageHeaderTitleImage')) {
			if (!$settingsForm->uploadPageHeaderTitleImage($settingsForm->getFormLocale())) {
				$settingsForm->addError('pageHeaderTitleImage', Locale::translate('admin.settings.homeHeaderImageInvalid'));
			}
		} elseif ($request->getUserVar('deletePageHeaderTitleImage')) {
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
			import('lib.pkp.classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification('notification.notification', 'common.changesSaved');
			$request->redirect(null, null, 'index');
		}
		$settingsForm->display();
	}
}

?>
