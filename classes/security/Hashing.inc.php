<?php

/**
 * @file classes/security/Hashing.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Hashing
 * @ingroup security
 *
 * @brief Class providing password hashing operations.
 * @see https://github.com/ircmaxell/password_compat
 */

class Hashing {

	/**
	 * Verify the hash is correct
	 *
	 * @param $password plain-text password
	 * @param $hash hashed string
	 * @return boolean
	 */
	function isValid($password, $hash) {
		if (Hashing::isSupported()) {
			return password_verify($password, $hash);
		}
		return false;
	}

	/**
	 * Does this password require rehashing
	 *
	 * @param $hash string
	 * @return boolean
	 **/
	function needsRehash($hash) {
		if (Hashing::isSupported()) {
			return password_needs_rehash($hash, PASSWORD_BCRYPT);
		}
		return false;
	}

	/**
	 * Get hash for string
	 *
	 * @param $string string
	 * @return string|false
	 */
	function getHash($string) {
		if (Hashing::isSupported()) {
			return password_hash($string, PASSWORD_BCRYPT);
		}
		return false;
	}

	/**
	 * Is password library supported?
	 *
	 * @return boolean
	 */
	function isSupported() {
		static $supported;

		if ($supported === null) {
			$supported = false;

			if (Hashing::initialize()) {
				/**
				 * Check if current PHP version is compatible with the library
				 *
				 * taken from PasswordCompat\binary\check()
				 *
				 * we can't use that directly because namespaces may not be supported
				 * which will cause a syntax error
				 */
				if (function_exists('crypt')) {
					$hash = '$2y$04$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
					$supported = (crypt("password", $hash) == $hash);
				}
			}
		}

		return $supported;
	}

	/**
	 * Load hashing library (if supported)
	 *
	 * @return boolean
	 */
	function initialize() {
		static $init;

		if ($init === null) {
			// password_compat uses namespaces, so ensure PHP version supports this
			if (version_compare(phpversion(), '5.3.0', '>=')) {
				$init = require_once(BASE_SYS_DIR . '/lib/password_compat/lib/password.php');
			}
			else {
				$init = false;
			}
		}

		return $init;
	}
}
