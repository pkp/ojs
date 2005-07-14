<?php

/**
 * RegistrationHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 *
 * Handle requests for user registration. 
 *
 * $Id$
 */

class RegistrationHandler extends UserHandler {

	/**
	 * Display registration form for new users.
	 */
	function register() {
		RegistrationHandler::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		
		if ($journal != null) {
			import('user.form.RegistrationForm');
		
			$regForm = &new RegistrationForm();
			$regForm->initData();
			$regForm->display();
			
		} else {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journals = &$journalDao->getEnabledJournals(); //Enabled added
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign_by_ref('journals', $journals);
			$templateMgr->display('user/registerSite.tpl');
		}
	}
	
	/**
	 * Validate user registration information and register new user.
	 */
	function registerUser() {
		RegistrationHandler::validate();
		import('user.form.RegistrationForm');
		
		$regForm = &new RegistrationForm();
		$regForm->readInputData();
		
		if ($regForm->validate()) {
			$regForm->execute();
			Validation::login($regForm->getData('username'), $regForm->getData('password'), $reason);
			if ($reason !== null) {
				parent::setupTemplate(true);
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'user.login');
				$templateMgr->assign('errorMsg', $reason==''?'user.login.accountDisabled':'user.login.accountDisabledWithReason');
				$templateMgr->assign('errorParams', array('reason' => $reason));
				$templateMgr->assign('backLink', Request::getPageUrl() . '/login');
				$templateMgr->assign('backLinkLabel', 'user.login');
				$templateMgr->display('common/error.tpl');
			}
			Request::redirect('login');
			
		} else {
			parent::setupTemplate(true);
			$regForm->display();
		}
	}
	
	/**
	 * Show error message if user registration is not allowed.
	 */
	function registrationDisabled() {
		parent::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'user.register');
		$templateMgr->assign('errorMsg', 'user.register.registrationDisabled');
		$templateMgr->assign('backLink', Request::getPageUrl() . '/login');
		$templateMgr->assign('backLinkLabel', 'user.login');
		$templateMgr->display('common/error.tpl');
	}

	/**
	 * Validation check.
	 * Checks if journal allows user registration.
	 */	
	function validate() {
		parent::validate(false);
		$journal = Request::getJournal();
		if ($journal != null) {
			$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
			if ($journalSettingsDao->getSetting($journal->getJournalId(), 'disableUserReg')) {
				// Users cannot register themselves for this journal
				RegistrationHandler::registrationDisabled();
				exit;
			}
		}
	}
	
}

?>
