<?php

/**
 * @file AdminLanguagesHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 * @class AdminLanguagesHandler
 *
 * Handle requests for changing site language settings. 
 *
 * $Id$
 */

class AdminLanguagesHandler extends AdminHandler {
	
	/**
	 * Display form to modify site language settings.
	 */
	function languages() {
		parent::validate();
		parent::setupTemplate(true);
		
		$site = &Request::getSite();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('localeNames', Locale::getAllLocales());
		$templateMgr->assign('primaryLocale', $site->getLocale());
		$templateMgr->assign('supportedLocales', $site->getSupportedLocales());
		$templateMgr->assign('profileLocalesEnabled', $site->getProfileLocalesEnabled());
		$templateMgr->assign('installedLocales', $site->getInstalledLocales());
		$templateMgr->assign('uninstalledLocales', array_diff(array_keys(Locale::getAllLocales()), $site->getInstalledLocales()));
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		$templateMgr->display('admin/languages.tpl');
	}
	
	/**
	 * Update language settings.
	 */
	function saveLanguageSettings() {
		parent::validate();
		parent::setupTemplate(true);
		
		$site = &Request::getSite();
		
		$primaryLocale = Request::getUserVar('primaryLocale');
		$supportedLocales = Request::getUserVar('supportedLocales');
		$profileLocalesEnabled = Request::getUserVar('profileLocalesEnabled');
		
		if (Locale::isLocaleValid($primaryLocale)) {
			$site->setLocale($primaryLocale);
		}
		
		$newSupportedLocales = array();
		if (isset($supportedLocales) && is_array($supportedLocales)) {
			foreach ($supportedLocales as $locale) {
				 if (Locale::isLocaleValid($locale)) {
				 	array_push($newSupportedLocales, $locale);
				 }
			}
		}
		if (!in_array($primaryLocale, $newSupportedLocales)) {
			array_push($newSupportedLocales, $primaryLocale);
		}
		$site->setSupportedLocales($newSupportedLocales);
		$site->setProfileLocalesEnabled(isset($profileLocalesEnabled) ? 1 : 0);
		
		$siteDao = &DAORegistry::getDAO('SiteDAO');
		$siteDao->updateSite($site);
		
		AdminLanguagesHandler::removeLocalesFromJournals();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'currentUrl' => Request::url(null, null, 'languages'),
			'pageTitle' => 'common.languages',
			'message' => 'common.changesSaved',
			'backLink' => Request::url(null, 'admin'),
			'backLinkLabel' => 'admin.siteAdmin'
		));
		$templateMgr->display('common/message.tpl');
	}
	
	/**
	 * Install a new locale.
	 */
	function installLocale() {
		parent::validate();
		
		$site = &Request::getSite();
		$installLocale = Request::getUserVar('installLocale');
		
		if (isset($installLocale) && is_array($installLocale)) {
			$installedLocales = $site->getInstalledLocales();
			
			foreach ($installLocale as $locale) {
				if (Locale::isLocaleValid($locale) && !in_array($locale, $installedLocales)) {
					array_push($installedLocales, $locale);
					Locale::installLocale($locale);
				}
			}
			
			$site->setInstalledLocales($installedLocales);
			$siteDao = &DAORegistry::getDAO('SiteDAO');
			$siteDao->updateSite($site);
		}
		
		Request::redirect(null, null, 'languages');
	}
	
	/**
	 * Uninstall a locale
	 */
	function uninstallLocale() {
		parent::validate();
		
		$site = &Request::getSite();
		$locale = Request::getUserVar('locale');
		
		if (isset($locale) && !empty($locale) && $locale != $site->getLocale()) {
			$installedLocales = $site->getInstalledLocales();
			
			if (in_array($locale, $installedLocales)) {
				$installedLocales = array_diff($installedLocales, array($locale));
				$site->setInstalledLocales($installedLocales);
				$supportedLocales = $site->getSupportedLocales();
				$supportedLocales = array_diff($supportedLocales, array($locale));
				$site->setSupportedLocales($supportedLocales);
				$siteDao = &DAORegistry::getDAO('SiteDAO');
				$siteDao->updateSite($site);
		
				AdminLanguagesHandler::removeLocalesFromJournals();
				Locale::uninstallLocale($locale);
			}
		}
		
		Request::redirect(null, null, 'languages');
	}
	
	/*
	 * Reload data for an installed locale.
	 */
	function reloadLocale() {
		parent::validate();
		
		$site = &Request::getSite();
		$locale = Request::getUserVar('locale');
	
		if (in_array($locale, $site->getInstalledLocales())) {
			Locale::reloadLocale($locale);
		}
		
		Request::redirect(null, null, 'languages');
	}
	
	/**
	 * Helper function to remove unsupported locales from journals.
	 */
	function removeLocalesFromJournals() {
		$site = &Request::getSite();
		$siteSupportedLocales = $site->getSupportedLocales();
	
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journals = &$journalDao->getJournals();
		$journals = &$journals->toArray();
		foreach ($journals as $journal) {
			$primaryLocale =  $journal->getSetting('primaryLocale');
			$alternateLocale1 =  $journal->getSetting('alternateLocale1');
			$alternateLocale2 =  $journal->getSetting('alternateLocale2');
			$supportedLocales = $journal->getSetting('supportedLocales');
			
			if (isset($primaryLocale) && !in_array($primaryLocale, $siteSupportedLocales)) {
				$settingsDao->updateSetting($journal->getJournalId(), 'primaryLocale', null, 'string');
			}
			
			if (isset($alternateLocale1) && !in_array($alternateLocale1, $siteSupportedLocales)) {
				$settingsDao->updateSetting($journal->getJournalId(), 'alternateLocale1', null, 'string');
			}
			
			if (isset($alternateLocale2) && !in_array($alternateLocale2, $siteSupportedLocales)) {
				$settingsDao->updateSetting($journal->getJournalId(), 'alternateLocale2', null, 'string');
			}
			
			if (is_array($supportedLocales)) {
				$supportedLocales = array_intersect($supportedLocales, $siteSupportedLocales);
				$settingsDao->updateSetting($journal->getJournalId(), 'supportedLocales', $supportedLocales, 'object');
			}
		}
	}
	
}

?>
