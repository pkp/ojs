<?php

/**
 * index.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Wrapper for loading the LDAP authentiation plugin.
 *
 * $Id$
 */

require('LDAPAuthPlugin.inc.php');

return new LDAPAuthPlugin();

?>
