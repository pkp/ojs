<?php
/**
 * @defgroup plugins_auth_ldap LDAP Authentication Plugin
 */
 
/**
 * @file plugins/auth/ldap/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_auth_ldap
 * @brief Wrapper for loading the LDAP authentiation plugin.
 *
 */

require_once('LDAPAuthPlugin.inc.php');

return new LDAPAuthPlugin();


