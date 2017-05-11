<?php

/**
 * @file classes/security/Validation.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Validation
 * @ingroup security
 *
 * @brief Class providing user validation/authentication operations.
 */

require_once(BASE_SYS_DIR . '/lib/pkp/lib/vendor/ircmaxell/password-compat/lib/password.php');

class Validation {

	/**
	 * Authenticate user credentials and mark the user as logged in in the current session.
	 * @param $username string
	 * @param $password string unencrypted password
	 * @param $reason string reference to string to receive the reason an account was disabled; null otherwise
	 * @param $remember boolean remember a user's session past the current browser session
	 * @return User the User associated with the login credentials, or false if the credentials are invalid
	 */
	static function login($username, $password, &$reason, $remember = false) {
		$reason = null;
		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->getByUsername($username, true);
		if (!isset($user)) {
			// User does not exist
			return false;
		}

		if ($user->getAuthId()) {
			$authDao = DAORegistry::getDAO('AuthSourceDAO');
			$auth = $authDao->getPlugin($user->getAuthId());
		} else {
			$auth = null;
		}

		if ($auth) {
			// Validate against remote authentication source
			$valid = $auth->authenticate($username, $password);
			if ($valid) {
				$oldEmail = $user->getEmail();
				$auth->doGetUserInfo($user);
				if ($user->getEmail() != $oldEmail) {
					// FIXME requires email addresses to be unique; if changed email already exists, ignore
					if ($userDao->userExistsByEmail($user->getEmail())) {
						$user->setEmail($oldEmail);
					}
				}
			}
		} else {
			// Validate against user database
			$rehash = null;
			$valid = Validation::verifyPassword($username, $password, $user->getPassword(), $rehash);

			if ($valid && !empty($rehash)) {
				// update to new hashing algorithm
				$user->setPassword($rehash);
			}
		}

		if (!$valid) {
			// Login credentials are invalid
			return false;

		} else {
			return self::registerUserSession($user, $reason, $remember);
		}
	}

	/**
	 * Verify if the input password is correct
	 *
	 * @param string $username the string username
	 * @param string $password the plaintext password
	 * @param string $hash the password hash from the database
	 * @param string &$rehash if password needs rehash, this variable is used
	 * @return boolean
	 */
	function verifyPassword($username, $password, $hash, &$rehash) {
		if (password_needs_rehash($hash, PASSWORD_BCRYPT)) {
			// update to new hashing algorithm
			$oldHash = Validation::encryptCredentials($username, $password, false, true);

			if ($oldHash === $hash) {
				// update hash
				$rehash = Validation::encryptCredentials($username, $password);

				return true;
			}
		}

		return password_verify($password, $hash);
	}

	/**
	 * Mark the user as logged in in the current session.
	 * @param $user User user to register in the session
	 * @param $reason string reference to string to receive the reason an account was disabled; null otherwise
	 * @param $remember boolean remember a user's session past the current browser session
	 * @return mixed User or boolean the User associated with the login credentials, or false if the credentials are invalid
	 */
	static function registerUserSession($user, &$reason, $remember = false) {
		if (!is_a($user, 'User')) return false;

		if ($user->getDisabled()) {
			// The user has been disabled.
			$reason = $user->getDisabledReason();
			if ($reason === null) $reason = '';
			return false;
		}

		// The user is valid, mark user as logged in in current session
		$sessionManager = SessionManager::getManager();

		// Regenerate session ID first
		$sessionManager->regenerateSessionId();

		$session = $sessionManager->getUserSession();
		$session->setSessionVar('userId', $user->getId());
		$session->setUserId($user->getId());
		$session->setSessionVar('username', $user->getUsername());
		$session->setRemember($remember);

		if ($remember && Config::getVar('general', 'session_lifetime') > 0) {
			// Update session expiration time
			$sessionManager->updateSessionLifetime(time() +  Config::getVar('general', 'session_lifetime') * 86400);
		}

		$user->setDateLastLogin(Core::getCurrentDate());
		$userDao = DAORegistry::getDAO('UserDAO');
		$userDao->updateObject($user);

		return $user;
	}

	/**
	 * Mark the user as logged out in the current session.
	 * @return boolean
	 */
	static function logout() {
		$sessionManager = SessionManager::getManager();
		$session = $sessionManager->getUserSession();
		$session->unsetSessionVar('userId');
		$session->unsetSessionVar('signedInAs');
		$session->setUserId(null);

		if ($session->getRemember()) {
			$session->setRemember(0);
			$sessionManager->updateSessionLifetime(0);
		}

		$sessionDao = DAORegistry::getDAO('SessionDAO');
		$sessionDao->updateObject($session);

		return true;
	}

	/**
	 * Redirect to the login page, appending the current URL as the source.
	 * @param $message string Optional name of locale key to add to login page
	 */
	static function redirectLogin($message = null) {
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
	static function checkCredentials($username, $password) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->getByUsername($username, false);

		$valid = false;
		if (isset($user)) {
			if ($user->getAuthId()) {
				$authDao = DAORegistry::getDAO('AuthSourceDAO');
				$auth =& $authDao->getPlugin($user->getAuthId());
			}

			if (isset($auth)) {
				$valid = $auth->authenticate($username, $password);
			} else {
				// Validate against user database
				$rehash = null;
				$valid = Validation::verifyPassword($username, $password, $user->getPassword(), $rehash);

				if ($valid && !empty($rehash)) {
					// update to new hashing algorithm
					$user->setPassword($rehash);

					// save new password hash to database
					$userDao->updateObject($user);
				}
			}
		}

		return $valid;
	}

	/**
	 * Check if a user is authorized to access the specified role in the specified context.
	 * @param $roleId int
	 * @param $contextId optional (e.g., for global site admin role), the ID of the context
	 * @return boolean
	 */
	static function isAuthorized($roleId, $contextId = 0) {
		if (!Validation::isLoggedIn()) {
			return false;
		}

		if ($contextId === -1) {
			// Get context ID from request
			$application = PKPApplication::getApplication();
			$request = $application->getRequest();
			$context = $request->getContext();
			$contextId = $context == null ? 0 : $context->getId();
		}

		$sessionManager = SessionManager::getManager();
		$session = $sessionManager->getUserSession();
		$user = $session->getUser();

		$roleDao = DAORegistry::getDAO('RoleDAO');
		return $roleDao->userHasRole($contextId, $user->getId(), $roleId);
	}

	/**
	 * Encrypt user passwords for database storage.
	 * The username is used as a unique salt to make dictionary
	 * attacks against a compromised database more difficult.
	 * @param $username string username (kept for backwards compatibility)
	 * @param $password string unencrypted password
	 * @param $encryption string optional encryption algorithm to use, defaulting to the value from the site configuration
	 * @param $legacy boolean if true, use legacy hashing technique for backwards compatibility
	 * @return string encrypted password
	 */
	static function encryptCredentials($username, $password, $encryption = false, $legacy = false) {
		if ($legacy) {
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
		} else {
			return password_hash($password, PASSWORD_BCRYPT);
		}
	}

	/**
	 * Generate a random password.
	 * Assumes the random number generator has already been seeded.
	 * @param $length int the length of the password to generate (default 8)
	 * @return string
	 */
	static function generatePassword($length = 8) {
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
	 * @param $expiry int timestamp when hash expires, defaults to CURRENT_TIME + RESET_SECONDS
	 * @return string (boolean false if user is invalid)
	 */
	static function generatePasswordResetHash($userId, $expiry = null) {
		$userDao = DAORegistry::getDAO('UserDAO');
		if (($user = $userDao->getById($userId)) == null) {
			// No such user
			return false;
		}
		// create hash payload
		$salt = Config::getVar('security', 'salt');

		if (empty($expiry)) {
			$expires = (int) Config::getVar('security', 'reset_seconds', 7200);
			$expiry = time() + $expires;
		}

		// use last login time to ensure the hash changes when they log in
		$data = $user->getUsername() . $user->getPassword() . $user->getDateLastLogin() . $expiry;

		// generate hash and append expiry timestamp
		$algos = hash_algos();

		foreach (array('sha256', 'sha1', 'md5') as $algo) {
			if (in_array($algo, $algos)) {
				return hash_hmac($algo, $data, $salt) . ':' . $expiry;
			}
		}

		// fallback to MD5
		return md5($data . $salt) . ':' . $expiry;
	}

	/**
	 * Check if provided password reset hash is valid.
	 * @param $userId int
	 * @param $hash string
	 * @return boolean
	 */
	function verifyPasswordResetHash($userId, $hash) {
		// append ":" to ensure the explode results in at least 2 elements
		list(, $expiry) = explode(':', $hash . ':');

		if (empty($expiry) || ((int) $expiry < time())) {
			// expired
			return false;
		}

		return ($hash === Validation::generatePasswordResetHash($userId, $expiry));
	}

	/**
	 * Suggest a username given the first and last names.
	 * @return string
	 */
	static function suggestUsername($firstName, $lastName) {
		$initial = PKPString::substr($firstName, 0, 1);

		$suggestion = PKPString::regexp_replace('/[^a-zA-Z0-9_-]/', '', PKPString::strtolower($initial . $lastName));
		$userDao = DAORegistry::getDAO('UserDAO');
		for ($i = ''; $userDao->userExistsByUsername($suggestion . $i); $i++);
		return $suggestion . $i;
	}

	/**
	 * Check if the user must change their password in order to log in.
	 * @return boolean
	 */
	static function isLoggedIn() {
		$sessionManager = SessionManager::getManager();
		$session = $sessionManager->getUserSession();

		$userId = $session->getUserId();
		return isset($userId) && !empty($userId);
	}

	/**
	 * Check if the user is logged in as a different user.
	 * @return boolean
	 */
	static function isLoggedInAs() {
		$sessionManager = SessionManager::getManager();
		$session = $sessionManager->getUserSession();
		$signedInAs = $session->getSessionVar('signedInAs');

		return isset($signedInAs) && !empty($signedInAs);
	}

	/**
	 * Shortcut for checking authorization as site admin.
	 * @return boolean
	 */
	static function isSiteAdmin() {
		return Validation::isAuthorized(ROLE_ID_SITE_ADMIN);
	}

	/**
	 * Check whether a user is allowed to administer another user.
	 * @param $administeredUserId int User ID of user to potentially administer
	 * @param $administratorUserId int User ID of user who wants to do the administrating
	 * @return boolean True IFF the administration operation is permitted
	 */
	static function canAdminister($administeredUserId, $administratorUserId) {
		$roleDao = DAORegistry::getDAO('RoleDAO');

		// You can administer yourself
		if ($administeredUserId == $administratorUserId) return true;

		// You cannot adminster administrators
		if ($roleDao->userHasRole(CONTEXT_SITE, $administeredUserId, ROLE_ID_SITE_ADMIN)) return false;

		// Otherwise, administrators can administer everyone
		if ($roleDao->userHasRole(CONTEXT_SITE, $administratorUserId, ROLE_ID_SITE_ADMIN)) return true;

		// Check for administered user group assignments in other contexts
		// that the administrator user doesn't have a manager role in.
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups = $userGroupDao->getByUserId($administeredUserId);
		while ($userGroup = $userGroups->next()) {
			if ($userGroup->getContextId()!=CONTEXT_SITE && !$roleDao->userHasRole($userGroup->getContextId(), $administratorUserId, ROLE_ID_MANAGER)) {
				// Found an assignment: disqualified.
				return false;
			}
		}

		// Make sure the administering user has a manager role somewhere
		$foundManagerRole = false;
		$roles = $roleDao->getByUserId($administratorUserId);
		foreach ($roles as $role) {
			if ($role->getRoleId() == ROLE_ID_MANAGER) $foundManagerRole = true;
		}
		if (!$foundManagerRole) return false;

		// There were no conflicting roles. Permit administration.
		return true;
	}
}

?>
