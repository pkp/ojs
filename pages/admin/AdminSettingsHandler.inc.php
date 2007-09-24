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
		parent::validate();
		parent::setupTemplate(true);
		$site =& Request::getSite();

		import('admin.form.SiteSettingsForm');

		$settingsForm = &new SiteSettingsForm();
		$settingsForm->readInputData();

		if (Request::getUserVar('uploadSiteStyleSheet')) {
			if (!$settingsForm->uploadSiteStyleSheet()) {
				$settingsForm->addError('siteStyleSheet', Locale::translate('admin.settings.siteStyleSheetInvalid'));
			}
		} elseif (Request::getUserVar('deleteSiteStyleSheet')) {
			$publicFileManager =& new PublicFileManager();
			$publicFileManager->removeSiteFile($site->getSiteStyleFilename());
		} elseif (Request::getUserVar('uploadPageHeaderTitleImage')) {
			if (!$settingsForm->uploadPageHeaderTitleImage($settingsForm->getFormLocale())) {
				$settingsForm->addError('pageHeaderTitleImage', Locale::translate('admin.settings.pageHeaderImageInvalid'));
			}
		} elseif (Request::getUserVar('deletePageHeaderTitleImage')) {
			$publicFileManager =& new PublicFileManager();
			$setting = $site->getData('pageHeaderTitleImage');
			$formLocale = $settingsForm->getFormLocale();
			if (isset($setting[$formLocale])) {
				$publicFileManager->removeSiteFile($setting[$formLocale]['uploadName']);
				unset($setting[$formLocale]);
				$site->setData('pageHeaderTitleImage', $setting);
				$siteDao =& DAORegistry::getDAO('SiteDAO');
				$siteDao->updateSite($site);
			}
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
