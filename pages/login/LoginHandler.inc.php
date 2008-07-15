<?php

/**
 * @file LoginHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LoginHandler
 * @ingroup pages_login
 *
 * @brief Handle login/logout requests. 
 */

// $Id$


import('core.Handler');

class LoginHandler extends Handler {

	/**
	 * Display user login form.
	 * Redirect to user index page if user is already validated.
	 */
	function index() {
		parent::validate();
		if (Validation::isLoggedIn()) {
			Request::redirect(null, 'user');
		}

		if (Config::getVar('security', 'force_login_ssl') && Request::getProtocol() != 'https') {
			// Force SSL connections for login
			Request::redirectSSL();
		}

		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();

		$templateMgr = &TemplateManager::getManager();

		// If the user wasn't expecting a login page, i.e. if they're new to the
		// site and want to submit a paper, it helps to explain why they need to
		// register.
		if(Request::getUserVar('loginMessage'))
			$templateMgr->assign('loginMessage', Request::getUserVar('loginMessage'));

		$templateMgr->assign('username', $session->getSessionVar('username'));
		$templateMgr->assign('remember', Request::getUserVar('remember'));
		$templateMgr->assign('source', Request::getUserVar('source'));
		$templateMgr->assign('showRemember', Config::getVar('general', 'session_lifetime') > 0);
		$templateMgr->display('user/login.tpl');
	}
	
	/**
	 * Handle login when implicitAuth is enabled.
	 * If the user came in on a non-ssl url - then redirect back to the ssl url
	 */
	function implicitAuthLogin() {	
		if (Request::getProtocol() != 'https') 
			Request::redirectSSL();

		$wayf_url = Config::getVar("security", "implicit_auth_wayf_url");
		
		if ($wayf_url == "")
			die("Error in implicit authentication. WAYF URL not set in config file.");
			
		$url = $wayf_url . "?target=https://" . Request::getServerHost() . Request::getBasePath() . '/index.php/index/login/implicitAuthReturn';
		
		Request::redirectUrl($url);
	}

	/**
	 * This is the function that Shibboleth redirects to - after the user has authenticated.
	 */
	function implicitAuthReturn() {
		parent::validate();

		if (Validation::isLoggedIn()) {
			Request::redirect(null, 'user');
		}		

		// Login - set remember to false
		$user = Validation::login(Request::getUserVar('username'), Request::getUserVar('password'), $reason, false);		

		Request::redirect(null, 'user');		
	}

	/**
	 * Validate a user's credentials and log the user in.
	 */
	function signIn() {
		parent::validate();
		if (Validation::isLoggedIn()) {
			Request::redirect(null, 'user');
		}

		if (Config::getVar('security', 'force_login_ssl') && Request::getProtocol() != 'https') {
			// Force SSL connections for login
			Request::redirectSSL();
		}

		$user = Validation::login(Request::getUserVar('username'), Request::getUserVar('password'), $reason, Request::getUserVar('remember') == null ? false : true);
		if ($user !== false) {
			if (Config::getVar('security', 'force_login_ssl') && !Config::getVar('security', 'force_ssl')) {
				// Redirect back to HTTP if forcing SSL for login only
				Request::redirectNonSSL();

			} else if ($user->getMustChangePassword()) {
				// User must change their password in order to log in
				Validation::logout();
				Request::redirect(null, null, 'changePassword', $user->getUsername());

			} else {
				$source = Request::getUserVar('source');
				if (isset($source) && !empty($source)) {
					Request::redirectUrl(Request::getProtocol() . '://' . Request::getServerHost() . $source, false);
				} else {
					Request::redirect(null, 'user');
				}
			}

		} else {
			$sessionManager = &SessionManager::getManager();
			$session = &$sessionManager->getUserSession();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('username', Request::getUserVar('username'));
			$templateMgr->assign('remember', Request::getUserVar('remember'));
			$templateMgr->assign('source', Request::getUserVar('source'));
			$templateMgr->assign('showRemember', Config::getVar('general', 'session_lifetime') > 0);
			$templateMgr->assign('error', $reason===null?'user.login.loginError':($reason===''?'user.login.accountDisabled':'user.login.accountDisabledWithReason'));
			$templateMgr->assign('reason', $reason);
			$templateMgr->display('user/login.tpl');
		}
	}

	/**
	 * Log a user out.
	 */
	function signOut() {
		parent::validate();
		if (Validation::isLoggedIn()) {
			Validation::logout();
		}

		Request::redirect(null, Request::getRequestedPage());
	}

	/**
	 * Display form to reset a user's password.
	 */
	function lostPassword() {
		parent::validate();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('user/lostPassword.tpl');
	}

	/**
	 * Send a request to reset a user's password
	 */
	function requestResetPassword() {
		parent::validate();
		$templateMgr = &TemplateManager::getManager();

		$email = Request::getUserVar('email');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &$userDao->getUserByEmail($email);

		if ($user == null || ($hash = Validation::generatePasswordResetHash($user->getUserId())) == false) {
			$templateMgr->assign('error', 'user.login.lostPassword.invalidUser');
			$templateMgr->display('user/lostPassword.tpl');

		} else {
			$site = &Request::getSite();

			// Send email confirming password reset
			import('mail.MailTemplate');
			$mail = &new MailTemplate('PASSWORD_RESET_CONFIRM');
			$mail->setFrom($site->getSiteContactEmail(), $site->getSiteContactName());
			$mail->assignParams(array(
				'url' => Request::url(null, 'login', 'resetPassword', $user->getUsername(), array('confirm' => $hash)),
				'siteTitle' => $site->getSiteTitle()
			));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
			$templateMgr->assign('pageTitle',  'user.login.resetPassword');
			$templateMgr->assign('message', 'user.login.lostPassword.confirmationSent');
			$templateMgr->assign('backLink', Request::url(null, Request::getRequestedPage()));
			$templateMgr->assign('backLinkLabel',  'user.login');
			$templateMgr->display('common/message.tpl');
		}
	}

	/**
	 * Reset a user's password
	 * @param $args array first param contains the username of the user whose password is to be reset
	 */
	function resetPassword($args) {
		parent::validate();

		$username = isset($args[0]) ? $args[0] : null;
		$userDao = &DAORegistry::getDAO('UserDAO');
		$confirmHash = Request::getUserVar('confirm');

		if ($username == null || ($user = &$userDao->getUserByUsername($username)) == null) {
			Request::redirect(null, null, 'lostPassword');
			return;
		}

		$templateMgr = &TemplateManager::getManager();

		$hash = Validation::generatePasswordResetHash($user->getUserId());
		if ($hash == false || $confirmHash != $hash) {
			$templateMgr->assign('errorMsg', 'user.login.lostPassword.invalidHash');
			$templateMgr->assign('backLink', Request::url(null, null, 'lostPassword'));
			$templateMgr->assign('backLinkLabel',  'user.login.resetPassword');
			$templateMgr->display('common/error.tpl');

		} else {
			// Reset password
			$newPassword = Validation::generatePassword();

			if ($user->getAuthId()) {
				$authDao = &DAORegistry::getDAO('AuthSourceDAO');
				$auth = &$authDao->getPlugin($user->getAuthId());
			}

			if (isset($auth)) {
				$auth->doSetUserPassword($user->getUsername(), $newPassword);
				$user->setPassword(Validation::encryptCredentials($user->getUserId(), Validation::generatePassword())); // Used for PW reset hash only
			} else {
				$user->setPassword(Validation::encryptCredentials($user->getUsername(), $newPassword));
			}

			$user->setMustChangePassword(1);
			$userDao->updateUser($user);

			// Send email with new password
			$site = &Request::getSite();
			import('mail.MailTemplate');
			$mail = &new MailTemplate('PASSWORD_RESET');
			$mail->setFrom($site->getSiteContactEmail(), $site->getSiteContactName());
			$mail->assignParams(array(
				'username' => $user->getUsername(),
				'password' => $newPassword,
				'siteTitle' => $site->getSiteTitle()
			));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
			$templateMgr->assign('pageTitle',  'user.login.resetPassword');
			$templateMgr->assign('message', 'user.login.lostPassword.passwordSent');
			$templateMgr->assign('backLink', Request::url(null, Request::getRequestedPage()));
			$templateMgr->assign('backLinkLabel',  'user.login');
			$templateMgr->display('common/message.tpl');
		}
	}

	/**
	 * Display form to change user's password.
	 * @param $args array first argument may contain user's username
	 */
	function changePassword($args = array()) {
		parent::validate();

		import('user.form.LoginChangePasswordForm');

		$passwordForm = &new LoginChangePasswordForm();
		$passwordForm->initData();
		if (isset($args[0])) {
			$passwordForm->setData('username', $args[0]);
		}
		$passwordForm->display();
	}

	/**
	 * Save user's new password.
	 */
	function savePassword() {
		parent::validate();

		import('user.form.LoginChangePasswordForm');

		$passwordForm = &new LoginChangePasswordForm();
		$passwordForm->readInputData();

		if ($passwordForm->validate()) {
			if ($passwordForm->execute()) {
				$user = Validation::login($passwordForm->getData('username'), $passwordForm->getData('password'), $reason);
			}
			Request::redirect(null, 'user');

		} else {
			$passwordForm->display();
		}
	}

}

?>
