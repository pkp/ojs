<?php

/**
 * AuthorHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for journal author functions. 
 *
 * $Id$
 */

import('pages.author.SubmitHandler');

class AuthorHandler extends Handler {

	/**
	 * Display journal author index page.
	 */
	function index() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('author/index.tpl');
	}
	
	/**
	 * Validate that user has permissions to author the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isAuthor($journal->getJournalId())) {
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('author', 'author.journalAuthor'))
				: array(array('user', 'navigation.user'))
		);
	}


	function submit($args) {
		SubmitHandler::submit($args);
	}
	
}

?>
