<?php

/**
 * RTVersionHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rtadmin
 *
 * Handle Reading Tools administration requests -- setup section. 
 *
 * $Id$
 */

import('rt.ojs.JournalRTAdmin');

class RTVersionHandler extends RTAdminHandler {
	function exportVersion() {
		RTAdminHandler::validate();
	}

	function importVersion() {
		RTAdminHandler::validate();
	}

	function restoreVersions() {
		RTAdminHandler::validate();

		$journal = &Request::getJournal();
		$rtAdmin = &new JournalRTAdmin($journal->getJournalId());
		$rtAdmin->restoreVersions();

		print "DONE";
	}

	function versions() {
		RTAdminHandler::validate();
	}

	function editVersion() {
		RTAdminHandler::validate();
	}

	function saveVersion() {
		RTAdminHandler::validate();
	}
}

?>
