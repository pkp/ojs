<?php

/**
 * OAIHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.oai
 *
 * Handle OAI protocol requests. 
 *
 * $Id$
 */

import('oai.ojs.JournalOAI');

class OAIHandler extends Handler {

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 */
	function index() {
		OAIHandler::validate();
		
		$oai = new JournalOAI(new OAIConfig(Request::getRequestUrl(), 'ojs'));
		$oai->execute();
	}
	
	/**
	 * Redirect to index if system has already been installed.
	 */
	function validate() {
		parent::validate();
		
		// FIXME Check if OAI interface is enabled.
	}
}

?>
