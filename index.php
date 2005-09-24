<?php

/**
 * index.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Front controller for OJS site. Loads required files and dispatches requests to the appropriate handler. 
 *
 * $Id$
 */

/**
 * Handle a new request.
 */
function handleRequest() {
	if (!Config::getVar('general', 'installed') && pageRequiresInstall()) {
		// Redirect to installer if application has not been installed
		Request::redirect('install');
		
	}

	// Determine the handler for this request
	$page = Request::getRequestedPage();
	$op = Request::getRequestedOp();
	$sourceFile = sprintf('pages/%s/index.php', $page);

	HookRegistry::call('LoadHandler', array(&$page, &$op, &$sourceFile));

	require($sourceFile);

	if (!defined('SESSION_DISABLE_INIT')) {
		// Initialize session
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
	}

	$methods = array_map('strtolower', get_class_methods(HANDLER_CLASS));

	if (in_array(strtolower($op), $methods)) {
		// Call a specific operation
		call_user_func(array(HANDLER_CLASS, $op), Request::getRequestedArgs());
		
	} else {
		// Call the selected handler's index operation
		call_user_func(array(HANDLER_CLASS, 'index'), Request::getRequestedArgs());
	}
}

// Initialize system and handle the current request
require('includes/driver.inc.php');
initSystem();
handleRequest();

?>
