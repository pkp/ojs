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
 * Check if request is for a page that requires the system to be installed.
 * Any pages that can be accessed from an uninstalled system should be allowed here.
 * @return boolean
 */
function pageRequiresInstall() {
	$page = Request::getRequestedPage();
	return ($page != 'install' && $page != 'help');
}

/**
 * Perform basic system initialization.
 * Initializes configuration variables, database connection, and user session.
 */
function initSystem() {
	Registry::set('system.debug.startTime', Core::microtime());
	
	if (Config::getVar('general', 'installed')) {
		// Initialize database connection
		$conn = &DBConnection::getInstance();
		
		if (!$conn->isConnected()) {
			if (Config::getVar('database', 'debug')) {
				$dbconn = &$conn->getDBConn();
				die('Database connection failed: ' . $dbconn->errorMsg());
				
			} else {
				die('Database connection failed!');
			}
		}
		
		// Initialize session
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
	}
}

function glue_url ($url) {
	if (!is_array($url)) return false;

	$returner = @$url['scheme'] ? @$url['scheme'] . ':' . ((strtolower(@$url['scheme']) == 'mailto') ? '' : '//') : '';
	$returner .= @$url['user'] ? @$url['user'] . (@$url['pass']? ':' . @$url['pass']:'') . '@' : '';
	$returner .= @$url['host'] ? @$url['host'] : '';
	$returner .= @$url['port'] ? ':' . @$url['port'] : '';
	$returner .= @$url['path'] ? @$url['path'] : '';
	$returner .= @$url['query'] ? '?' . @$url['query'] : '';
	$returner .= @$url['fragment'] ? '#'. @$url['fragment'] : '';
	return $returner;
}

?>
