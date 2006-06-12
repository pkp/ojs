<?php

/**
 * index.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
