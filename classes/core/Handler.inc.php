<?php

/**
 * Handler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 *
 * Base request handler class.
 *
 * $Id$
 */

class Handler {

	/**
	 * Fallback method in case request handler does not implement index method.
	 */
	function index() {
		header('HTTP/1.0 404 Not Found');
		die('<H1>404 Not Found</H1>');
	}
	
	/**
	 * 
	 */
	function validate() {
		if (Config::getVar('security', 'force_ssl') && Request::getProtocol() != 'https') {
			// Force SSL connections site-wide
			Request::redirectSSL();
		}
		
		if (($journal = Request::getJournal()) != null && !Validation::isLoggedIn() && Request::getRequestedPage() != 'login' && Request::getRequestedPage() != 'user') {
			// Check if unregistered users can access the site
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			if ($journalSettingsDao->getSetting($journal->getJournalId(), 'restrictSiteAccess')) {
				Request::redirect('login');
			}
		}
	}
	
}
?>
