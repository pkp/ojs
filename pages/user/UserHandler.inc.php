<?php

/**
 * UserHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 *
 * Handle requests for user functions. 
 *
 * $Id$
 */

import('pages.user.ProfileHandler');
import('pages.user.RegistrationHandler');
import('pages.user.EmailHandler');

class UserHandler extends Handler {

	/**
	 * Display user index page.
	 */
	function index() {
		UserHandler::validate();
		
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		UserHandler::setupTemplate();
		$templateMgr = &TemplateManager::getManager();
		
		$journal = &Request::getJournal();
		
		if ($journal == null) {
			// Show roles for all journals
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journals = &$journalDao->getJournals();
			
			$journalsToDisplay = array();
			$rolesToDisplay = array();
			
			// Fetch the user's roles for each journal
			foreach ($journals as $journal) {
				$roles = &$roleDao->getRolesByUserId($session->getUserId(), $journal->getJournalId());
				if (!empty($roles)) {
					$journalsToDisplay[] = $journal;
					$rolesToDisplay[$journal->getJournalId()] = &$roles;
				}
			}
			
			$templateMgr->assign('showAllJournals', 1);
			$templateMgr->assign('userJournals', $journalsToDisplay);
			$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, $session->getUserId(), ROLE_ID_SITE_ADMIN));
			
		} else {
			// Show roles for the currently selected journal
			$roles = &$roleDao->getRolesByUserId($session->getUserId(), $journal->getJournalId());
			if (empty($roles)) {
				Request::redirect(Request::getIndexUrl() . '/index/user');
			}
			
			$rolesToDisplay[$journal->getJournalId()] = &$roles;
			$templateMgr->assign('userJournal', $journal);
		}
		
		$templateMgr->assign('userRoles', $rolesToDisplay);
		$templateMgr->display('user/index.tpl');
	}
	
	/**
	 * Change the locale for the current user.
	 * @param $args array first parameter is the new locale
	 */
	function setLocale($args) {
		$setLocale = isset($args[0]) ? $args[0] : null;
		
		$site = &Request::getSite();
		$journal = &Request::getJournal();
		if ($journal != null) {
			$journalSupportedLocales = $journal->getSetting('supportedLocales');
			if (!is_array($journalSupportedLocales)) {
				$journalSupportedLocales = array();
			}
		}
		
		if (Locale::isLocaleValid($setLocale) && (!isset($journalSupportedLocales) || in_array($setLocale, $journalSupportedLocales)) && in_array($setLocale, $site->getSupportedLocales())) {
			$session = &Request::getSession();
			$session->setSessionVar('currentLocale', $setLocale);
		}
		
		Request::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : Request::getPageUrl() . '/user');
	}
	
	/**
	 * Validate that user is logged in.
	 * Redirects to login form if not logged in.
	 * @param $loginCheck boolean check if user is logged in
	 */
	function validate($loginCheck = true) {
		parent::validate();
		if ($loginCheck && !Validation::isLoggedIn()) {
			Request::redirect('login');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		if ($subclass) {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('pageHierarchy', array(array('user', 'navigation.user')));
		}
	}
	
	
	//
	// Profiles
	//
	
	function profile() {
		ProfileHandler::profile();
	}
	
	function saveProfile() {
		ProfileHandler::saveProfile();
	}
	
	function changePassword() {
		ProfileHandler::changePassword();
	}
	
	function savePassword() {
		ProfileHandler::savePassword();
	}
	
	
	//
	// Registration
	//

	function register() {
		RegistrationHandler::register();
	}
	
	function registerUser() {
		RegistrationHandler::registerUser();
	}

	function email($args) {
		EmailHandler::email($args);
	}
}

?>
