<?php

/**
 * @file plugins/auth/ldap/LDAPAuthPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LDAPAuthPlugin
 * @ingroup plugins_auth_ldap
 *
 * @brief LDAP authentication plugin.
 */

use PKP\plugins\AuthPlugin;

use PKP\facades\Locale;

class LDAPAuthPlugin extends AuthPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
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
    // - SASL: sasl, saslmech, saslrealm, saslauthcid, saslauthzid, saslprop

    /** @var resource the LDAP connection */
    public $conn;

    /**
     * Return the name of this plugin.
     *
     * @return string
     */
    public function getName()
    {
        return 'ldap';
    }

    /**
     * Return the localized name of this plugin.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.auth.ldap.displayName');
    }

    /**
     * Return the localized description of this plugin.
     *
     * @return string
     */
    public function getDescription()
    {
        return __('plugins.auth.ldap.description');
    }


    //
    // Core Plugin Functions
    // (Must be implemented by every authentication plugin)
    //

    /**
     * Returns an instance of the authentication plugin
     *
     * @param array $settings settings specific to this instance.
     * @param int $authId identifier for this instance
     *
     * @return LDAPuthPlugin
     */
    public function getInstance($settings, $authId)
    {
        return new LDAPAuthPlugin($settings, $authId);
    }

    /**
     * Authenticate a username and password.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool true if authentication is successful
     */
    public function authenticate($username, $password)
    {
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
     *
     * @param string $username
     *
     * @return bool
     */
    public function userExists($username)
    {
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
     *
     * @param User $user User to update
     *
     * @return bool true if successful
     */
    public function getUserInfo($user)
    {
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
     *
     * @param User $user User to store
     *
     * @return bool true if successful
     */
    public function setUserInfo($user)
    {
        $valid = false;
        if ($this->open()) {
            if ($entry = $this->getUserEntry($user->getUsername())) {
                $userdn = ldap_get_dn($this->conn, $entry);
                if ($this->bind($this->settings['managerdn'], $this->settings['managerpwd'])) {
                    $attr = [];
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
     *
     * @param string $username user to update
     * @param string $password the new password
     *
     * @return bool true if successful
     */
    public function setUserPassword($username, $password)
    {
        if ($this->open()) {
            if ($entry = $this->getUserEntry($username)) {
                $userdn = ldap_get_dn($this->conn, $entry);
                if ($this->bind($this->settings['managerdn'], $this->settings['managerpwd'])) {
                    $attr = ['userPassword' => $this->encodePassword($password)];
                    $valid = ldap_modify($this->conn, $userdn, $attr);
                }
            }
            $this->close();
        }
    }

    /**
     * Create a user on the LDAP server.
     *
     * @param User $user User to create
     *
     * @return bool true if successful
     */
    public function createUser($user)
    {
        $valid = false;
        if ($this->open()) {
            if (!($entry = $this->getUserEntry($user->getUsername()))) {
                if ($this->bind($this->settings['managerdn'], $this->settings['managerpwd'])) {
                    $userdn = $this->settings['uid'] . '=' . $user->getUsername() . ',' . $this->settings['basedn'];
                    $attr = [
                        'objectclass' => ['top', 'person', 'organizationalPerson', 'inetorgperson'],
                        $this->settings['uid'] => $user->getUsername(),
                        'userPassword' => $this->encodePassword($user->getPassword())
                    ];
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
     *
     * @param string $username user to delete
     *
     * @return bool true if successful
     */
    public function deleteUser($username)
    {
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
    public function open()
    {
        $this->conn = ldap_connect($this->settings['hostname'], (int)$this->settings['port']);
        ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        return $this->conn;
    }

    /**
     * Close connection.
     */
    public function close()
    {
        ldap_close($this->conn);
        $this->conn = null;
    }

    /**
     * Bind to a directory.
     * $binddn string directory to bind (optional)
     * $password string (optional)
     *
     * @param null|mixed $binddn
     * @param null|mixed $password
     */
    public function bind($binddn = null, $password = null)
    {
        if (isset($this->settings['sasl'])) {
            // Not well tested
            return @ldap_sasl_bind($this->conn, $binddn, $password, $this->settings['saslmech'], $this->settings['saslrealm'], $this->settings['saslauthcid'], $this->settings['saslauthzid'], $this->settings['saslprop']);
        }
        return @ldap_bind($this->conn, $binddn, $password);
    }

    /**
     * Lookup a user entry in the directory.
     *
     * @param string $username
     */
    public function getUserEntry($username)
    {
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
     *
     * @param User $user
     * @param array $uattr
     */
    public function userFromAttr(&$user, &$uattr)
    {
        $siteDao = DAORegistry::getDAO('SiteDAO'); /** @var SiteDAO $siteDao */
        $site = $siteDao->getSite();

        $attr = array_change_key_case($uattr, CASE_LOWER);
        $givenName = @$attr['givenname'][0];
        $familyName = @$attr['sn'][0];
        if (!isset($familyName)) {
            $familyName = @$attr['surname'][0];
        }
        $affiliation = @$attr['o'][0];
        if (!isset($affiliation)) {
            $affiliation = @$attr['organizationname'][0];
        }
        $email = @$attr['mail'][0];
        if (!isset($email)) {
            $email = @$attr['email'][0];
        }
        $phone = @$attr['telephonenumber'][0];
        $mailingAddress = @$attr['postaladdress'][0];
        if (!isset($mailingAddress)) {
            $mailingAddress = @$attr['registeredAddress'][0];
        }
        $biography = null;
        $interests = null;

        // Only update fields that exist
        if (isset($givenName)) {
            $user->setGivenName($givenName, Locale::getLocale());
        }
        if (isset($familyName)) {
            $user->setFamilyName($familyName, Locale::getLocale());
        }
        if (isset($affiliation)) {
            $user->setAffiliation($affiliation, Locale::getLocale());
        }
        if (isset($email)) {
            $user->setEmail($email);
        }
        if (isset($phone)) {
            $user->setPhone($phone);
        }
        if (isset($mailingAddress)) {
            $user->setMailingAddress($mailingAddress);
        }
        if (isset($biography)) {
            $user->setBiography($biography, Locale::getLocale());
        }
        if (isset($interests)) {
            $user->setInterests($interests, Locale::getLocale());
        }
    }

    /**
     * Update entry attributes from User object.
     * TODO How to deal with deleted fields.
     *
     * @param User $user
     * @param array $attr
     */
    public function userToAttr(&$user, &$attr)
    {
        $siteDao = DAORegistry::getDAO('SiteDAO'); /** @var SiteDAO $siteDao */
        $site = $siteDao->getSite();
        // FIXME empty strings for unset fields?
        if ($user->getFullName()) {
            $attr['cn'] = $user->getFullName();
        }
        if ($user->getLocalizedGivenName()) {
            $attr['givenName'] = $user->getLocalizedGivenName();
        }
        if ($user->getLocalizedFamilyName()) {
            $attr['sn'] = $user->getLocalizedFamilyName();
        }
        if ($user->getLocalizedAffiliation()) {
            $attr['organizationName'] = $user->getLocalizedAffiliation();
        }
        if ($user->getEmail()) {
            $attr['mail'] = $user->getEmail();
        }
        if ($user->getPhone()) {
            $attr['telephoneNumber'] = $user->getPhone();
        }
        if ($user->getMailingAddress()) {
            $attr['postalAddress'] = $user->getMailingAddress();
        }
    }

    /**
     * Encode password for the 'userPassword' field using the specified hash.
     *
     * @param string $password
     *
     * @return string hashed string (with prefix).
     */
    public function encodePassword($password)
    {
        switch ($this->settings['pwhash']) {
            case 'md5':
                return '{MD5}' . base64_encode(pack('H*', md5($password)));
            case 'smd5':
                $salt = pack('C*', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand());
                return '{SMD5}' . base64_encode(pack('H*', md5($password . $salt)) . $salt);
            case 'sha':
                return '{SHA}' . base64_encode(pack('H*', sha1($password)));
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
