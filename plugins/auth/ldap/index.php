<?php

/**
 * index.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Wrapper for loading the LDAP authentiation plugin.
 *
 * $Id$
 */

require_once('LDAPAuthPlugin.inc.php');

return new LDAPAuthPlugin();

?>
