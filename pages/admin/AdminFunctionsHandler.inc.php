<?php

/**
 * AdminFunctionsHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 *
 * Handle requests for site administrative/maintenance functions. 
 *
 * $Id$
 */

class AdminFunctionsHandler extends AdminHandler {
	
	/**
	 * Clear compiled templates.
	 */
	function clearTemplateCache() {
		parent::validate();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->clearTemplateCache();
		Request::redirect('admin');
	}
	
}

?>
