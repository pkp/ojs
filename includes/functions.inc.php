<?php

/**
 * functions.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Contains definitions for common functions used system-wide.
 * Any frequently-used functions that cannot be put into an appropriate class should be added here.
 *
 * $Id$
 */

/**
 * Emulate a Java-style import statement.
 * Simply includes the associated PHP file (using require_once so multiple calls to include the same file have no effect).
 * @param $class string the complete name of the class to be imported (e.g. "core.Core")
 */
function import($class) {
	require_once(str_replace('.', '/', $class) . '.inc.php');
}

/**
 * Checks if a page requires that the system be installed.
 * Any pages that can be accesed from an uninstalled system should be allowed
 * here.
 * @return boolean
 */
function pageRequiresInstall() {
	$page = Request::getRequestedPage();
	return ($page != 'install' && $page != 'help');
}

?>
