<?php

/**
 * @file classes/security/Validation.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Validation
 * @ingroup security
 *
 * @brief Class providing user validation/authentication operations.
 */

import('classes.security.Role');

class Validation {

	/**
	 * Authenticate user credentials and mark the user as logged in in the current session.
	 * @param $username string
	 * @param $password string unencrypted password
	 * @param $reason string reference to string to receive the reason an account was disabled; null otherwise
	 * @param $remember boolean remember a user's session past the current browser session
	 * @return User the User associated with the login credentials, or false if the credentials are invalid
	 */
	function &login($username, $password, &$reason, $remember = false) {
		$implicitAuth = Config::getVar('security', 'implicit_auth');

		$reason = null;
		$valid = false;
		$userDao =& DAORegistry::getDAO('UserDAO');

		if ($implicitAuth) { // Implicit auth
			if (!Validation::isLoggedIn()) {
				PluginRegistry::loadCategory('implicitAuth');

				// Call the implicitAuth hook. It will set user.

				HookRegistry::call('ImplicitAuthPlugin::implicitAuth', array(&$user));

				$valid=true;
			}
		} else { // Regular Auth
			$user =& $userDao->getByUsername($username, true);

			if (!isset($user)) {
				// User does not exist
				return $valid;
			}

			if ($user->getAuthId()) {
				$authDao =& DAORegistry::getDAO('AuthSourceDAO');
				$auth =& $authDao->getPlugin($user->getAuthId());
			}

			if (isset($auth)) {
				// Validate against remote authentication source
				$valid = $auth->authenticate($username, $password);
				if ($valid) {
					$oldEmail = $user->getEmail();
					$auth->doGetUserInfo($user);
					if ($user->getEmail() != $oldEmail) {
						// FIXME OJS requires email addresses to be unique; if changed email already exists, ignore
						if ($userDao->userExistsByEmail($user->getEmail())) {
							$user->setEmail($oldEmail);
						}
					}
				}
			} else {
				// Validate against OJS user database
				$valid = ($user->getPassword() === Validation::encryptCredentials($username, $password));
			}
		}

		if (!$valid) {
			// Login credentials are invalid
			return $valid;

		} else {
			if ($user->getDisabled()) {
				// The user has been disabled.
				$reason = $user->getDisabledReason();
				if ($reason === null) $reason = '';
				$valid = false;
				return $valid;
			}

			// The user is valid, mark user as logged in in current session
			$sessionManager =& SessionManager::getManager();

			// Regenerate session ID first
			$sessionManager->regenerateSessionId();

			$session =& $sessionManager->getUserSession();
			$session->setSessionVar('userId', $user->getId());
			$session->setUserId($user->getId());
			$session->setSessionVar('username', $user->getUsername());
			$session->setRemember($remember);

			if ($remember && Config::getVar('general', 'session_lifetime') > 0) {
				// Update session expiration time
				$sessionManager->updateSessionLifetime(time() +  Config::getVar('general', 'session_lifetime') * 86400);
			}

			$user->setDateLastLogin(Core::getCurrentDate());
			$userDao->updateObject($user);

			return $user;
		}
	}

	/**
	 * Mark the user as logged out in the current session.
	 * @return boolean
	 */
	function logout() {
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		$session->unsetSessionVar('userId');
		$session->unsetSessionVar('signedInAs');
		$session->setUserId(null);

		if ($session->getRemember()) {
			$session->setRemember(0);
			$sessionManager->updateSessionLifetime(0);
		}

		$sessionDao =& DAORegistry::getDAO('SessionDAO');
		$sessionDao->updateObject($session);

		return true;
	}

	/**
	 * Redirect to the login page, appending the current URL as the source.
	 * @param $message string Optional name of locale key to add to login page
	 */
	function redirectLogin($message = null) {
		$args = array();

		if (isset($_SERVER['REQUEST_URI'])) {
			$args['source'] = $_SERVER['REQUEST_URI'];
		}
		if ($message !== null) {
			$args['loginMessage'] = $message;
		}

		Request::redirect(null, 'login', null, null, $args);
	}

	/**
	 * Check if a user's credentials are valid.
	 * @param $username string username
	 * @param $password string unencrypted password
	 * @return boolean
	 */
	function checkCredentials($username, $password) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getByUsername($username, false);

		$valid = false;
		if (isset($user)) {
			if ($user->getAuthId()) {
				$authDao =& DAORegistry::getDAO('AuthSourceDAO');
				$auth =& $authDao->getPlugin($user->getAuthId());
			}

			if (isset($auth)) {
				$valid = $auth->authenticate($username, $password);
			} else {
				$valid = ($user->getPassword() === Validation::encryptCredentials($username, $password));
			}
		}

		return $valid;
	}

	/**
	 * Check if a user is authorized to access the specified role in the specified journal.
	 * @param $roleId int
	 * @param $journalId optional (e.g., for global site admin role), the ID of the journal
	 * @return boolean
	 */
	function isAuthorized($roleId, $journalId = 0) {
		if (!Validation::isLoggedIn()) {
			return false;
		}

		if ($journalId === -1) {
			// Get journal ID from request
			$journal =& Request::getJournal();
			$journalId = $journal == null ? 0 : $journal->getId();
		}

		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		$user =& $session->getUser();

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		return $roleDao->userHasRole($journalId, $user->getId(), $roleId);
	}

	/**
	 * Encrypt user passwords for database storage.
	 * The username is used as a unique salt to make dictionary
	 * attacks against a compromised database more difficult.
	 * @param $username string username
	 * @param $password string unencrypted password
	 * @param $encryption string optional encryption algorithm to use, defaulting to the value from the site configuration
	 * @return string encrypted password
	 */
	function encryptCredentials($username, $password, $encryption = false) {
		$valueToEncrypt = $username . $password;

		if ($encryption == false) {
			$encryption = Config::getVar('security', 'encryption');
		}

		switch ($encryption) {
			case 'sha1':
				if (function_exists('sha1')) {
					return sha1($valueToEncrypt);
				}
			case 'md5':
			default:
				return md5($valueToEncrypt);
		}
	}

	/**
	 * Generate a random password.
	 * Assumes the random number generator has already been seeded.
	 * @param $length int the length of the password to generate (default 8)
	 * @return string
	 */
	function generatePassword($length = 8) {
		$letters = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
		$numbers = '23456789';

		$password = "";
		for ($i=0; $i<$length; $i++) {
			$password .= mt_rand(1, 4) == 4 ? $numbers[mt_rand(0,strlen($numbers)-1)] : $letters[mt_rand(0, strlen($letters)-1)];
		}
		return $password;
	}

	/**
	 * Generate a hash value to use for confirmation to reset a password.
	 * @param $userId int
	 * @return string (boolean false if user is invalid)
	 */
	function generatePasswordResetHash($userId) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		if (($user = $userDao->getUser($userId)) == null) {
			// No such user
			return false;
		}
		return substr(md5($user->getId() . $user->getUsername() . $user->getPassword()), 0, 6);
	}

	/**
	 * Suggest a username given the first and last names.
	 * @return string
	 */
	function suggestUsername($firstName, $lastName) {
		$initial = String::substr($firstName, 0, 1);

		$suggestion = String::regexp_replace('/[^a-zA-Z0-9_-]/', '', String::strtolower($initial . $lastName));
		$userDao =& DAORegistry::getDAO('UserDAO');
		for ($i = ''; $userDao->userExistsByUsername($suggestion . $i); $i++);
		return $suggestion . $i;
	}

	/**
	 * Check if the user must change their password in order to log in.
	 * @return boolean
	 */
	function isLoggedIn() {
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();

		$userId = $session->getUserId();
		return isset($userId) && !empty($userId);
	}

	/**
	 * Shortcut for checking authorization as site admin.
	 * @return boolean
	 */
	function isSiteAdmin() {
		return Validation::isAuthorized(ROLE_ID_SITE_ADMIN);
	}

	/**
	 * Shortcut for checking authorization as journal manager.
	 * @param $journalId int
	 * @return boolean
	 */
	function isJournalManager($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_JOURNAL_MANAGER, $journalId);
	}

	/**
	 * Shortcut for checking authorization as editor.
	 * @param $journalId int
	 * @return boolean
	 */
	function isEditor($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_EDITOR, $journalId);
	}

	/**
	 * Shortcut for checking authorization as section editor.
	 * @param $journalId int
	 * @return boolean
	 */
	function isSectionEditor($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_SECTION_EDITOR, $journalId);
	}

	/**
	 * Shortcut for checking authorization as layout editor.
	 * @param $journalId int
	 * @return boolean
	 */
	function isLayoutEditor($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_LAYOUT_EDITOR, $journalId);
	}

	/**
	 * Shortcut for checking authorization as reviewer.
	 * @param $journalId int
	 * @return boolean
	 */
	function isReviewer($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_REVIEWER, $journalId);
	}

	/**
	 * Shortcut for checking authorization as copyeditor.
	 * @param $journalId int
	 * @return boolean
	 */
	function isCopyeditor($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_COPYEDITOR, $journalId);
	}

	/**
	 * Shortcut for checking authorization as proofreader.
	 * @param $journalId int
	 * @return boolean
	 */
	function isProofreader($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_PROOFREADER, $journalId);
	}

	/**
	 * Shortcut for checking authorization as author.
	 * @param $journalId int
	 * @return boolean
	 */
	function isAuthor($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_AUTHOR, $journalId);
	}

	/**
	 * Shortcut for checking authorization as reader.
	 * @param $journalId int
	 * @return boolean
	 */
	function isReader($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_READER, $journalId);
	}

	/**
	 * Shortcut for checking authorization as subscription manager.
	 * @param $journalId int
	 * @return boolean
	 */
	function isSubscriptionManager($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_SUBSCRIPTION_MANAGER, $journalId);
	}

	/**
	 * Check whether a user is allowed to administer another user.
	 * @param $journalId int
	 * @param $userId int
	 * @return boolean
	 */
	function canAdminister($journalId, $userId) {
		if (Validation::isSiteAdmin()) return true;
		if (!Validation::isJournalManager($journalId)) return false;

		// Check for roles in other journals that this user
		// doesn't have administrative rights over.
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roles =& $roleDao->getRolesByUserId($userId);
		foreach ($roles as $role) {
			if ($role->getRoleId() == ROLE_ID_SITE_ADMIN) return false;
			if (
				$role->getJournalId() != $journalId &&
				!Validation::isJournalManager($role->getJournalId())
			) return false;
		}

		// There were no conflicting roles.
		return true;
	}
}

?>
