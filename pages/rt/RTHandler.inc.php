<?php

/**
 * RTHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rt
 *
 * Handle Reading Tools requests. 
 *
 * $Id$
 */

import('rt.RT');

class RTHandler extends Handler {

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 */
	function index() {
		RTHandler::validate();
	}
	
	/**
	 * Redirect to index if system has already been installed.
	 */
	function validate() {
		parent::validate(true);
	}
	
	function about() {
		RTHandler::validate();
	}
	
	function bio() {
		RTHandler::validate();
	}
	
	function metadata() {
		RTHandler::validate();
	}
	
	function cite() {
		RTHandler::validate();
	}
	
	function printerFriendly() {
		RTHandler::validate();
	}
	
	function defineWord() {
		RTHandler::validate();
	}
	
	function emailColleague() {
		RTHandler::validate();
	}
	
	function suppFiles() {
		RTHandler::validate();
	}
	
	function suppFileMetadata() {
		RTHandler::validate();
	}
}

?>
