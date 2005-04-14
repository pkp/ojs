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

	function index() {
		OAIHandler::validate();
		
		// FIXME Proper config
		$oai = new JournalOAI(new OAIConfig(Request::getRequestUrl(), 'ojs.pkp.ubc.ca'));
		$oai->execute();
	}
	
	function validate() {
		parent::validate();
		
		// FIXME Check if OAI interface is enabled.
	}
}

?>
