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
function init() {
	if (!Config::getVar('general', 'installed') && Request::getRequestedPage() != 'install') {
		// Redirect to installer if application has not been installed
		Request::redirect('install');
		
	} else if (Config::getVar('general', 'installed')) {
		// Initialize session unless loading installer
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
	}
	
	// Determine the handler for this request
	$page = Request::getRequestedPage();
	$op = Request::getRequestedOp();
	
	require(sprintf('pages/%s/index.php', $page));
	
	if (in_array(strtolower($op), get_class_methods(HANDLER_CLASS))) {
		// Call a specific operation
		call_user_func(array(HANDLER_CLASS, $op), Request::getRequestedArgs());
		
	} else {
		// Call the selected handler's index operation
		call_user_func(array(HANDLER_CLASS, 'index'), Request::getRequestedArgs());
	}
}

// Initialize system and handle the current request
require('includes/driver.inc.php');
init();
?>
