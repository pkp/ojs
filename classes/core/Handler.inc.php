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
		fatalError('404 Not Found');
	}
	
	/**
	 * Perform request access validation based on security settings.
	 * @param $requiresJournal boolean
	 */
	function validate($requiresJournal = false) {
		if (Config::getVar('security', 'force_ssl') && Request::getProtocol() != 'https') {
			// Force SSL connections site-wide
			Request::redirectSSL();
		}
		
		$journal = Request::getJournal();
		
		if ($requiresJournal && $journal == null) {
			// Requested page is only allowed for journals
			Request::redirect(null, 'about');
		}
		
		if ($journal != null && !Validation::isLoggedIn() && Request::getRequestedPage() != 'login' && Request::getRequestedPage() != 'user' && Request::getRequestedPage() != 'help') {
			// Check if unregistered users can access the site
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			if ($journalSettingsDao->getSetting($journal->getJournalId(), 'restrictSiteAccess')) {
				Request::redirect(null, 'login');
			}
		}
	}

	/**
	 * Return the DBResultRange structure and misc. variables describing the current page of a set of pages.
	 * @param $rangeName string Symbolic name of range of pages; must match the Smarty {page_list ...} name.
	 * @return array ($pageNum, $dbResultRange)
	 */
	function &getRangeInfo($rangeName) {
		$journal = &Request::getJournal();
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

		$pageNum = Request::getUserVar($rangeName . 'Page');
		if (empty($pageNum)) $pageNum=1;

		if ($journal) $count = $journalSettingsDao->getSetting($journal->getJournalId(), 'itemsPerPage');
		if (!isset($count)) $count = Config::getVar('interface', 'items_per_page');

		import('db.DBResultRange');

		if (isset($count)) $returner = &new DBResultRange($count, $pageNum);
		else $returner = &new DBResultRange(-1, -1);

		return $returner;
	}
}
?>
