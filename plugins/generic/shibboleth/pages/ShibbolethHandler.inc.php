<?php

/**
 * @file plugins/generic/shibboleth/pages/ShibbolethHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * @class ShibbolethHandler
 * @ingroup plugins_generic_shibboleth
 *
 * @brief Handle Shibboleth responses
 */

import('classes.handler.Handler');

class ShibbolethHandler extends Handler {
	/** @var ShibbolethAuthPlugin */
	var $_plugin;

	/** @var int */
	var $_contextId;

	/**
	 * Intercept normal login/registration requests; defer to Shibboleth.
	 * 
	 * @param $args array
	 * @param $request Request
	 * @return bool
	 */
	function activateUser($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}

	/**
	 * @copydoc ShibbolethHandler::activateUser()
	 */
	function changePassword($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}

	/**
	 * @copydoc ShibbolethHandler::activateUser()
	 */
	function index($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}

	/**
	 * @copydoc ShibbolethHandler::activateUser()
	 */
	function lostPassword($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}

	/**
	 * @copydoc ShibbolethHandler::activateUser()
	 */
	function register($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}

	/**
	 * @copydoc ShibbolethHandler::activateUser()
	 */
	function registerUser($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}

	/**
	 * @copydoc ShibbolethHandler::activateUser()
	 */
	function requestResetPassword($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}

	/**
	 * @copydoc ShibbolethHandler::activateUser()
	 */
	function savePassword($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}

	/**
	 * Login handler; receives post-validation Shibboleth redirect.
	 * 
	 * @param $args array
	 * @param $request Request
	 * @return bool
	 */
	function shibLogin($args, $request) {
		$this->_plugin = $this->_getPlugin();
		$this->_contextId = $this->_plugin->getCurrentContextId();
		$uinHeader = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethHeaderUin'
		);
		$emailHeader = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethHeaderEmail'
		);

		// We rely on these headers being present.
		if (!isset($_SERVER[$uinHeader])) {
			errog_log(
				"Shibboleth plugin enabled, but not properly configured; failed to find $uinHeader"
			);
			Validation::logout();
			Validation::redirectLogin();
			return false;
		}
		if (!isset($_SERVER[$emailHeader])) {
			error_log(
				"Shibboleth plugin enabled, but not properly configured; failed to find $emailHeader"
			);
			Validation::logout();
			Validation::redirectLogin();
			return false;
		}

		$uin = $_SERVER[$uinHeader];
		$userEmail = $_SERVER[$emailHeader];

		// The UIN must be set; otherwise login failed.
		if (is_null($uin)) {
			Validation::logout();
			Validation::redirectLogin();
			return false;
		}

		// Try to locate the user by UIN.
		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->getUserByAuthStr($uin, true);
		if (isset($user)) {
			syslog(LOG_INFO, "Shibboleth located returning user $uin");
		} else {
			// We use the e-mail as a key.
			if (empty($userEmail)) {
				error_log(
					"Shibboleth failed to find UIN $uin and no email given."
				);
				Validation::logout();
				Validation::redirectLogin();
				return false;
			}
			$user = $userDao->getUserByEmail($userEmail);

			if (isset($user)) {
				syslog(LOG_INFO, "Shibboleth located returning email $userEmail");

				if ($user->getAuthStr() != "") {
					error_log(
						"Shibboleth user with email $userEmail already has UID"
					);
					Validation::logout();
					Validation::redirectLogin();
					return false;
				} else {
					$user->setAuthStr($uin);
					$userDao->updateObject($user);
				}
			} else {
				$user = $this->_registerFromShibboleth();
			}
		}

		if (isset($user)) {
			$this->_checkAdminStatus($user);

			$disabledReason = null;
			$success = Validation::registerUserSession($user, $disabledReason);

			if (!$success) {
				// @@@ TODO: present user with disabled reason
				error_log(
					"Disabled user $uin attempted Shibboleth login" .
						(is_null($disabledReason) ? "" : ": $disabledReason")
				);
				Validation::logout();
				Validation::redirectLogin();
				return false;
			}

			return $this->_redirectAfterLogin($request);
		}

		return false;
	}

	/**
	 * @copydoc ShibbolethHandler::activateUser()
	 */
	function signIn($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}

	/**
	 * Intercept normal logout; redirect to context home page instead
	 * of login (which would send back to Shibboleth again).
	 * 
	 * @param $args array
	 * @param $request Request
	 * @return bool
	 */
	function signOut($args, $request) {
		$context = $this->getTargetContext($request);
		$router = $request->getRouter();

		Validation::logout();

		$contextPath = is_null($context) ? "" : $context->getPath();
		$returnUrl = $router->url($request, $contextPath);
		return $request->redirectUrl($returnUrl);
	}

	/**
	 * @copydoc ShibbolethHandler::activateUser()
	 */
	function validate($args, $request) {
		return $this->_shibbolethRedirect($args, $request);
	}


	//
	// Private helper methods
	//
	/**
	 * Get the Shibboleth plugin object
	 * 
	 * @return ShibbolethAuthPlugin
	 */
	function _getPlugin() {
		$plugin = PluginRegistry::getPlugin('generic', SHIBBOLETH_PLUGIN_NAME);
		return $plugin;
	}

	/**
	 * Check if the user should be an admin according to the
	 * Shibboleth plugin settings, and adjust the User object
	 * accordingly.
	 * 
	 * @param $user User
	 */
	function _checkAdminStatus($user) {
		$adminsStr = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethAdminUins'
		);
		$admins = explode(' ', $adminsStr);

		$uin = $user->getAuthStr();
		if (empty($uin)) {
			return;
		}

		$userId = $user->getId();
		$adminFound = array_search($uin, $admins);

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		// should be unique
		$adminGroup = $userGroupDao->getByRoleId(0, ROLE_ID_SITE_ADMIN)->next();
		$adminId = $adminGroup->getId();

		// If they are in the list of users who should be admins
		if ($adminFound !== false) {
			// and if they are not already an admin
			if(!$userGroupDao->userInGroup($userId, $adminId)) {
				syslog(LOG_INFO, "Shibboleth assigning admin to $uin");
				$userGroupDao->assignUserToGroup($userId, $adminId);
			}
		} else {
			// If they are not in the admin list - then be sure they
			// are not an admin in the role table
			error_log("removing admin for $uin");
			$userGroupDao->removeUserFromGroup($userId, $adminId, 0);
		}
	}

	/**
	 * @copydoc LoginHandler::_redirectAfterLogin
	 */
	function _redirectAfterLogin($request) {
		$context = $this->getTargetContext($request);
		// If there's a context, send them to the dashboard after login.
		if ($context && $request->getUserVar('source') == '' &&
			array_intersect(
				array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_AUTHOR, ROLE_ID_REVIEWER, ROLE_ID_ASSISTANT),
				(array) $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES)
			)) {
			return $request->redirect($context->getPath(), 'dashboard');
		}

		return $request->redirectHome();
	}

	/**
	 * Create a new user from the Shibboleth-provided information.
	 * 
	 * @return User
	 */
	function _registerFromShibboleth() {
		$uinHeader = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethHeaderUin'
		);
		$emailHeader = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethHeaderEmail'
		);
		$firstNameHeader = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethHeaderFirstName'
		);
		$lastNameHeader = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethHeaderLastName'
		);
		$initialsHeader = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethHeaderInitials'
		);
		$phoneHeader = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethHeaderPhone'
		);
		$mailingHeader = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethHeaderMailing'
		);

		// We rely on these headers being present.	Redundant with the
		// login handler, but we need to check for more headers than
		// these; better safe than sorry.
		if (!isset($_SERVER[$uinHeader])) {
			error_log(
				"Shibboleth plugin enabled, but not properly configured; failed to find $uinHeader"
			);
			Validation::logout();
			Validation::redirectLogin();
			return false;
		}
		if (!isset($_SERVER[$emailHeader])) {
			error_log(
				"Shibboleth plugin enabled, but not properly configured; failed to find $emailHeader"
			);
			Validation::logout();
			Validation::redirectLogin();
			return false;
		}

		// required values
		$uin = $_SERVER[$uinHeader];
		$userEmail = $_SERVER[$emailHeader];
		$userFirstName = $_SERVER[$firstNameHeader];
		$userLastName = $_SERVER[$lastNameHeader];

		if (empty($uin) || empty($userEmail) || empty($userFirstName) || empty($userLastName)) {
			error_log("Shibboleth failed to find required fields for new user");
		}

		// optional values
		$userInitials = isset($_SERVER[$initialsHeader]) ? $_SERVER[$initialsHeader] : null;
		$userPhone = isset($_SERVER[$phoneHeader]) ? $_SERVER[$phoneHeader] : null;
		$userMailing = isset($_SERVER[$mailingHeader]) ? $_SERVER[$mailingHeader] : null;

		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->newDataObject();
		$user->setAuthStr($uin);
		$user->setUsername($userEmail);
		$user->setEmail($userEmail);
		$user->setFirstName($userFirstName);
		$user->setLastName($userLastName);

		if (!empty($userInitials)) {
			$user->setInitials($userInitials);
		}
		if (!empty($userPhone)) {
			$user->setPhone($userPhone);
		}
		if (!empty($userMailing)) {
			$user->setMailingAddress($userMailing);
		}

		$user->setDateRegistered(Core::getCurrentDate());
		$user->setPassword(
			Validation::encryptCredentials(
				Validation::generatePassword(40),
				Validation::generatePassword(40)
			)
		);

		$userDao->insertObject($user);
		$userId = $user->getId();
		if ($userId) {
			return $user;
		} else {
			return null;
		}
	}

	/**
	 * Intercept normal login/registration requests; defer to Shibboleth.
	 * 
	 * @param $args array
	 * @param $request Request
	 * @return bool
	 */
	function _shibbolethRedirect($args, $request) {
		$this->_plugin = $this->_getPlugin();
		$this->_contextId = $this->_plugin->getCurrentContextId();
		$context = $this->getTargetContext($request);
		$router = $request->getRouter();

		$wayfUrl = $this->_plugin->getSetting(
			$this->_contextId,
			'shibbolethWayfUrl'
		);
		$shibLoginUrl = $router->url(
			$request,
			null,
			'shibboleth',
			'shibLogin',
			null,
			null,
			true
		);
		$shibUrl = $wayfUrl . '?target=' . $shibLoginUrl;
		return $request->redirectUrl($shibUrl);
	}
}
?>
