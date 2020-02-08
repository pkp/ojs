<?php
/**
 * @defgroup plugins_auth_ldap LDAP Authentication Plugin
 */
 
/**
 * @file plugins/auth/ldap/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_auth_ldap
 * @brief Wrapper for loading the LDAP authentiation plugin.
 *
 */

require_once('LDAPAuthPlugin.inc.php');

return new LDAPAuthPlugin();


