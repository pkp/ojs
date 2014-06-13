<?php

/**
 * @file pages/admin/AdminLanguagesHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminLanguagesHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for changing site language settings.
 */

import('pages.admin.AdminHandler');

class AdminLanguagesHandler extends AdminHandler {
	/**
	 * Constructor
	 **/
	function AdminLanguagesHandler() {
		parent::AdminHandler();
	}

	/**
	 * Display form to modify site language settings.
	 * @param $args array
	 * @param $request object
	 */
	function languages($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		$site =& $request->getSite();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('localeNames', AppLocale::getAllLocales());
		$templateMgr->assign('primaryLocale', $site->getPrimaryLocale());
		$templateMgr->assign('supportedLocales', $site->getSupportedLocales());
		$localesComplete = array();
		foreach (AppLocale::getAllLocales() as $key => $name) {
			$localesComplete[$key] = AppLocale::isLocaleComplete($key);
		}
		$templateMgr->assign('localesComplete', $localesComplete);

		$templateMgr->assign('installedLocales', $site->getInstalledLocales());
		$templateMgr->assign('uninstalledLocales', array_diff(array_keys(AppLocale::getAllLocales()), $site->getInstalledLocales()));
		$templateMgr->assign('helpTopicId', 'site.siteManagement');

		import('classes.i18n.LanguageAction');
		$languageAction = new LanguageAction();
		if ($languageAction->isDownloadAvailable()) {
			$templateMgr->assign('downloadAvailable', true);
			$templateMgr->assign('downloadableLocales', $languageAction->getDownloadableLocales());
		}

		$templateMgr->display('admin/languages.tpl');
	}

	/**
	 * Update language settings.
	 * @param $args array
	 * @param $request object
	 */
	function saveLanguageSettings($args, &$request) {
		$this->validate();

		$site =& $request->getSite();

		$primaryLocale = $request->getUserVar('primaryLocale');
		$supportedLocales = $request->getUserVar('supportedLocales');

		if (AppLocale::isLocaleValid($primaryLocale)) {
			$site->setPrimaryLocale($primaryLocale);
		}

		$newSupportedLocales = array();
		if (isset($supportedLocales) && is_array($supportedLocales)) {
			foreach ($supportedLocales as $locale) {
				if (AppLocale::isLocaleValid($locale)) {
					array_push($newSupportedLocales, $locale);
				}
			}
		}
		if (!in_array($primaryLocale, $newSupportedLocales)) {
			array_push($newSupportedLocales, $primaryLocale);
		}
		$site->setSupportedLocales($newSupportedLocales);

		$siteDao =& DAORegistry::getDAO('SiteDAO');
		$siteDao->updateObject($site);

		$this->_removeLocalesFromJournals($request);

		$user =& $request->getUser();

		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$notificationManager->createTrivialNotification($user->getId());

		$request->redirect(null, null, 'index');
	}

	/**
	 * Install a new locale.
	 * @param $args array
	 * @param $request object
	 */
	function installLocale($args, &$request) {
		$this->validate();

		$site =& $request->getSite();
		$installLocale = $request->getUserVar('installLocale');

		if (isset($installLocale) && is_array($installLocale)) {
			$installedLocales = $site->getInstalledLocales();

			foreach ($installLocale as $locale) {
				if (AppLocale::isLocaleValid($locale) && !in_array($locale, $installedLocales)) {
					array_push($installedLocales, $locale);
					AppLocale::installLocale($locale);
				}
			}

			$site->setInstalledLocales($installedLocales);
			$siteDao =& DAORegistry::getDAO('SiteDAO');
			$siteDao->updateObject($site);
		}

		$request->redirect(null, null, 'languages');
	}

	/**
	 * Uninstall a locale
	 * @param $args array
	 * @param $request object
	 */
	function uninstallLocale($args, &$request) {
		$this->validate();

		$site =& $request->getSite();
		$locale = $request->getUserVar('locale');

		if (isset($locale) && !empty($locale) && $locale != $site->getPrimaryLocale()) {
			$installedLocales = $site->getInstalledLocales();

			if (in_array($locale, $installedLocales)) {
				$installedLocales = array_diff($installedLocales, array($locale));
				$site->setInstalledLocales($installedLocales);
				$supportedLocales = $site->getSupportedLocales();
				$supportedLocales = array_diff($supportedLocales, array($locale));
				$site->setSupportedLocales($supportedLocales);
				$siteDao =& DAORegistry::getDAO('SiteDAO');
				$siteDao->updateObject($site);

				$this->_removeLocalesFromJournals($request);
				AppLocale::uninstallLocale($locale);
			}
		}

		$request->redirect(null, null, 'languages');
	}

	/**
	 * Reload data for an installed locale.
	 * @param $args array
	 * @param $request object
	 */
	function reloadLocale($args, &$request) {
		$this->validate();

		$site =& $request->getSite();
		$locale = $request->getUserVar('locale');

		if (in_array($locale, $site->getInstalledLocales())) {
			AppLocale::reloadLocale($locale);

			$user =& $request->getUser();

			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification($user->getId());
		}

		$request->redirect(null, null, 'languages');
	}

	/**
	 * Reload default email templates for a locale.
	 * @param $args array
	 * @param $request object
	 */
	function reloadDefaultEmailTemplates($args, &$request) {
		$this->validate();

		$site =& $request->getSite();
		$locale = $request->getUserVar('locale');

		if (in_array($locale, $site->getInstalledLocales())) {
			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplateDao->deleteDefaultEmailTemplatesByLocale($locale);
			$emailTemplateDao->installEmailTemplateData($emailTemplateDao->getMainEmailTemplateDataFilename($locale));

			$user =& $request->getUser();

			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification($user->getId());
		}

		$request->redirect(null, null, 'languages');
	}
	
	/**
	 * Helper function to remove unsupported locales from journals.
	 * @param $request object
	 */
	function _removeLocalesFromJournals(&$request) {
		$site =& $request->getSite();
		$siteSupportedLocales = $site->getSupportedLocales();

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$settingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journals =& $journalDao->getJournals();
		$journals =& $journals->toArray();
		foreach ($journals as $journal) {
			$primaryLocale = $journal->getPrimaryLocale();
			$supportedLocales = $journal->getSetting('supportedLocales');

			if (isset($primaryLocale) && !in_array($primaryLocale, $siteSupportedLocales)) {
				$journal->setPrimaryLocale($site->getPrimaryLocale());
				$journalDao->updateJournal($journal);
			}

			if (is_array($supportedLocales)) {
				$supportedLocales = array_intersect($supportedLocales, $siteSupportedLocales);
				$settingsDao->updateSetting($journal->getId(), 'supportedLocales', $supportedLocales, 'object');
			}
		}
	}

	/**
	 * Download a locale from the PKP web site.
	 * @param $args array
	 * @param $request object
	 */
	function downloadLocale($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);
		$locale = $request->getUserVar('locale');

		import('classes.i18n.LanguageAction');
		$languageAction = new LanguageAction();

		if (!$languageAction->isDownloadAvailable()) $request->redirect(null, null, 'languages');

		if (!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
			$request->redirect(null, null, 'languages');
		}

		$templateMgr =& TemplateManager::getManager();

		$errors = array();
		if (!$languageAction->downloadLocale($locale, $errors)) {
			$templateMgr->assign('errors', $errors);
			$templateMgr->display('admin/languageDownloadErrors.tpl');
			return;
		}

		$user =& $request->getUser();

		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$params = array('locale' => $locale);
		$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_LOCALE_INSTALLED, $params);
		$request->redirect(null, null, 'languages');
	}
}

?>
