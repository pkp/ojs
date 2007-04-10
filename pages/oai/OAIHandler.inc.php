<?php

/**
 * OAIHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.oai
 *
 * Handle OAI protocol requests. 
 *
 * $Id$
 */

define('SESSION_DISABLE_INIT', 1); // FIXME?

import('oai.ojs.JournalOAI');

class OAIHandler extends Handler {

	function index() {
		OAIHandler::validate();

		$oai = &new JournalOAI(new OAIConfig(Request::getRequestUrl(), Config::getVar('oai', 'repository_id')));
		$oai->execute();
	}
	
	function validate() {
		// Site validation checks not applicable
		//parent::validate();
		
		if (!Config::getVar('oai', 'oai')) {
			Request::redirect(null, 'index');
		}
	}
}

?>
