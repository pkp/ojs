<?php

/**
 * LoginHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.login
 *
 * Handle login/logout requests. 
 *
 * $Id$
 */

class LoginHandler extends Handler {

	/**
	 * Display user login form.
	 * Redirect to user index page if user is already validated.
	 */
	function index() {
		if (Validation::isLoggedIn()) {
			Request::redirect('user');
		}
		
		if (Config::getVar('security', 'force_login_ssl') && Request::getProtocol() != 'https') {
			// Force SSL connections for login
			Request::redirectSSL();
		}
		
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('username', $session->getSessionVar('username'));
		$templateMgr->assign('showRemember', Config::getVar('general', 'session_lifetime') > 0);
		$templateMgr->display('user/login.tpl');
	}
	
	/**
	 * Validate a user's credentials and log the user in.
	 */
	function signIn() {
		if (Validation::isLoggedIn()) {
			Request::redirect('user');
		}
		
		if (Config::getVar('security', 'force_login_ssl') && Request::getProtocol() != 'https') {
			// Force SSL connections for login
			Request::redirectSSL();
		}

		if (Validation::login(Request::getUserVar('username'), Request::getUserVar('password'), Request::getUserVar('remember') == null ? false : true)) {
			if (Config::getVar('security', 'force_login_ssl') && !Config::getVar('security', 'force_ssl')) {
				// Redirect back to HTTP if forcing SSL for login only
				Request::redirectNonSSL();
				
			} else {
 				Request::redirect('user');
			}
			
		} else {
			$sessionManager = &SessionManager::getManager();
			$session = &$sessionManager->getUserSession();
			
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('username', Request::getUserVar('username'));
			$templateMgr->assign('remember', Request::getUserVar('remember'));
			$templateMgr->assign('showRemember', Config::getVar('general', 'session_lifetime') > 0);
			$templateMgr->assign('error', 'user.login.loginError');
			$templateMgr->display('user/login.tpl');
		}
	}
	
	/**
	 * Log a user out.
	 */
	function signOut() {
		if (Validation::isLoggedIn()) {
			Validation::logout();
		}
		
		Request::redirect('login');
	}
	
}

?>
