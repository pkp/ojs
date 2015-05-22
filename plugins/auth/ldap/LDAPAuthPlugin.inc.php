<?php

/**
 * @file plugins/auth/ldap/LDAPAuthPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LDAPAuthPlugin
 * @ingroup plugins_auth_ldap
 *
 * @brief LDAP authentication plugin.
 */

import('classes.plugins.AuthPlugin');

class LDAPAuthPlugin extends AuthPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	// LDAP-specific configuration settings:
	// - hostname
	// - port
	// - basedn
	// - managerdn
	// - managerpwd
	// - pwhash
	// - SASL: sasl, saslmech, saslrealm, saslauthzid, saslprop

	/** @var $conn resource the LDAP connection */
	var $conn;

	/**
	 * Return the name of this plugin.
	 * @return string
	 */
	function getName() {
		return 'ldap';
	}

	/**
	 * Return the localized name of this plugin.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.auth.ldap.displayName');
	}

	/**
	 * Return the localized description of this plugin.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.auth.ldap.description');
	}


	//
	// Core Plugin Functions
	// (Must be implemented by every authentication plugin)
	//

	/**
	 * Returns an instance of the authentication plugin
	 * @param $settings array settings specific to this instance.
	 * @param $authId int identifier for this instance
	 * @return LDAPuthPlugin
	 */
	function &getInstance($settings, $authId) {
		$returner = new LDAPAuthPlugin($settings, $authId);
		return $returner;
	}

	/**
	 * Authenticate a username and password.
	 * @param $username string
	 * @param $password string
	 * @return boolean true if authentication is successful
	 */
	function authenticate($username, $password) {
		$valid = false;
		if ($password != null) {
			if ($this->open()) {
				if ($entry = $this->getUserEntry($username)) {
					$userdn = ldap_get_dn($this->conn, $entry);
					if ($this->bind($userdn, $password)) {
						$valid = true;
					}
				}
				$this->close();
			}
			return $valid;
		}
	}


	//
	// Optional Plugin Functions
	//

	/**
	 * Check if a username exists.
	 * @param $username string
	 * @return boolean
	 */
	function userExists($username) {
		$exists = true;
		if ($this->open()) {
			if ($this->bind()) {
				$result = ldap_search($this->conn, $this->settings['basedn'], $this->settings['uid'] . '=' . $username);
				$exists = (ldap_count_entries($this->conn, $result) != 0);
			}
			$this->close();
		}
		return $exists;
	}

	/**
	 * Retrieve user profile information from the LDAP server.
	 * @param $user User to update
	 * @return boolean true if successful
	 */
	function getUserInfo(&$user) {
		$valid = false;
		if ($this->open()) {
			if ($entry = $this->getUserEntry($user->getUsername())) {
				$valid = true;
				$attr = ldap_get_attributes($this->conn, $entry);
				$this->userFromAttr($user, $attr);
			}
			$this->close();
		}
		return $valid;
	}

	/**
	 * Store user profile information on the LDAP server.
	 * @param $user User to store
	 * @return boolean true if successful
	 */
	function setUserInfo(&$user) {
		$valid = false;
		if ($this->open()) {
			if ($entry = $this->getUserEntry($user->getUsername())) {
				$userdn = ldap_get_dn($this->conn, $entry);
				if ($this->bind($this->settings['managerdn'], $this->settings['managerpwd'])) {
					$attr = array();
					$this->userToAttr($user, $attr);
					$valid = ldap_modify($this->conn, $userdn, $attr);
				}
			}
			$this->close();
		}
		return $valid;
	}

	/**
	 * Change a user's password on the LDAP server.
	 * @param $username string user to update
	 * @param $password string the new password
	 * @return boolean true if successful
	 */
	function setUserPassword($username, $password) {
		if ($this->open()) {
			if ($entry = $this->getUserEntry($username)) {
				$userdn = ldap_get_dn($this->conn, $entry);
				if ($this->bind($this->settings['managerdn'], $this->settings['managerpwd'])) {
					$attr = array('userPassword' => $this->encodePassword($password));
					$valid = ldap_modify($this->conn, $userdn, $attr);
				}
			}
			$this->close();
		}
	}

	/**
	 * Create a user on the LDAP server.
	 * @param $user User to create
	 * @return boolean true if successful
	 */
	function createUser(&$user) {
		$valid = false;
		if ($this->open()) {
			if (!($entry = $this->getUserEntry($user->getUsername()))) {
				if ($this->bind($this->settings['managerdn'], $this->settings['managerpwd'])) {
					$userdn = $this->settings['uid'] . '=' . $user->getUsername() . ',' . $this->settings['basedn'];
					$attr = array(
						'objectclass' => array('top', 'person', 'organizationalPerson', 'inetorgperson'),
						$this->settings['uid'] => $user->getUsername(),
						'userPassword' => $this->encodePassword($user->getPassword())
					);
					$this->userToAttr($user, $attr);
					$valid = ldap_add($this->conn, $userdn, $attr);
				}
			}
			$this->close();
		}
		return $valid;
	}

	/**
	 * Delete a user from the LDAP server.
	 * @param $username string user to delete
	 * @return boolean true if successful
	 */
	function deleteUser($username) {
		$valid = false;
		if ($this->open()) {
			if ($entry = $this->getUserEntry($username)) {
				$userdn = ldap_get_dn($this->conn, $entry);
				if ($this->bind($this->settings['managerdn'], $this->settings['managerpwd'])) {
					$valid = ldap_delete($this->conn, $userdn);
				}
			}
			$this->close();
		}
		return $valid;
	}


	//
	// LDAP Helper Functions
	//

	/**
	 * Open connection to the server.
	 */
	function open() {
		$this->conn = ldap_connect($this->settings['hostname'], (int)$this->settings['port']);
		ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		return $this->conn;
	}

	/**
	 * Close connection.
	 */
	function close() {
		ldap_close($this->conn);
		$this->conn = null;
	}

	/**
	 * Bind to a directory.
	 * $binddn string directory to bind (optional)
	 * $password string (optional)
	 */
	function bind($binddn = null, $password = null) {
		if (isset($this->settings['sasl'])) {
			// FIXME ldap_sasl_bind requires PHP5, haven't tested this
			return @ldap_sasl_bind($this->conn, $binddn, $password, $this->settings['saslmech'], $this->settings['saslrealm'], $this->settings['saslauthzid'], $this->settings['saslprop']);
		}
		return @ldap_bind($this->conn, $binddn, $password);
	}

	/**
	 * Lookup a user entry in the directory.
	 * @param $username string
	 */
	function getUserEntry($username) {
		$entry = false;
		if ($this->bind($this->settings['managerdn'], $this->settings['managerpwd'])) {
			$result = ldap_search($this->conn, $this->settings['basedn'], $this->settings['uid'] . '=' . $username);
			if (ldap_count_entries($this->conn, $result) == 1) {
				$entry = ldap_first_entry($this->conn, $result);
			}
		}
		return $entry;
	}

	/**
	 * Update User object from entry attributes.
	 * TODO Abstract this to allow arbitrary LDAP <-> OJS schema mappings.
	 * For now must be subclassed for other schemas.
	 * TODO How to deal with deleted fields.
	 * @param $user User
	 * @param $uattr array
	 */
	function userFromAttr(&$user, &$uattr) {
		$attr = array_change_key_case($uattr, CASE_LOWER); // Note:  array_change_key_case requires PHP >= 4.2.0
		$firstName = @$attr['givenname'][0];
		$middleName = null;
		$initials = null;
		$lastName = @$attr['sn'][0];
		if (!isset($lastName))
			$lastName = @$attr['surname'][0];
		$affiliation = @$attr['o'][0];
		if (!isset($affiliation))
			$affiliation = @$attr['organizationname'][0];
		$email = @$attr['mail'][0];
		if (!isset($email))
			$email = @$attr['email'][0];
		$phone = @$attr['telephonenumber'][0];
		$fax = @$attr['facsimiletelephonenumber'][0];
		if (!isset($fax))
			$fax = @$attr['fax'][0];
		$mailingAddress = @$attr['postaladdress'][0];
		if (!isset($mailingAddress))
			$mailingAddress = @$attr['registeredAddress'][0];
		$biography = null;
		$interests = null;

		// Only update fields that exist
		if (isset($firstName))
			$user->setFirstName($firstName);
		if (isset($middleName))
			$user->setMiddleName($middleName);
		if (isset($initials))
			$user->setInitials($initials);
		if (isset($lastName))
			$user->setLastName($lastName);
		if (isset($affiliation))
			$user->setAffiliation($affiliation, AppLocale::getLocale());
		if (isset($email))
			$user->setEmail($email);
		if (isset($phone))
			$user->setPhone($phone);
		if (isset($fax))
			$user->setFax($fax);
		if (isset($mailingAddress))
			$user->setMailingAddress($mailingAddress);
		if (isset($biography))
			$user->setBiography($biography, AppLocale::getLocale());
		if (isset($interests))
			$user->setInterests($interests, AppLocale::getLocale());
	}

	/**
	 * Update entry attributes from User object.
	 * TODO How to deal with deleted fields.
	 * @param $user User
	 * @param $attr array
	 */
	function userToAttr(&$user, &$attr) {
		// FIXME empty strings for unset fields?
		if ($user->getFullName())
			$attr['cn'] = $user->getFullName();
		if ($user->getFirstName())
			$attr['givenName'] = $user->getFirstName();
		if ($user->getLastName())
			$attr['sn'] = $user->getLastName();
		if ($user->getAffiliation())
			$attr['organizationName'] = $user->getAffiliation(AppLocale::getLocale());
		if ($user->getEmail())
			$attr['mail'] = $user->getEmail();
		if ($user->getPhone())
			$attr['telephoneNumber'] = $user->getPhone();
		if ($user->getFax())
			$attr['facsimileTelephoneNumber'] = $user->getFax();
		if ($user->getMailingAddress())
			$attr['postalAddress'] = $user->getMailingAddress();
	}

	/**
	 * Encode password for the 'userPassword' field using the specified hash.
	 * @param $password string
	 * @return string hashed string (with prefix).
	 */
	function encodePassword($password) {
		switch ($this->settings['pwhash']) {
			case 'md5':
				return '{MD5}' . base64_encode(pack('H*', md5($password)));
			case 'smd5':
				$salt = pack('C*', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand());
				return '{SMD5}' . base64_encode(pack('H*', md5($password . $salt)) . $salt);
			case 'sha':
				return '{SHA}' . base64_encode(pack('H*', sha1($password))); // Note: sha1 requres PHP >= 4.3.0
			case 'ssha':
				$salt = pack('C*', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand());
				return '{SSHA}' . base64_encode(pack('H*', sha1($password . $salt)) . $salt);
			case 'crypt':
				return '{CRYPT}' . crypt($password);
			default:
				//return '{CLEARTEXT}'. $password;
				return $password;
		}
	}
}

?>
