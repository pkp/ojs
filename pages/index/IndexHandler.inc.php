<?php

/**
 * IndexHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.index
 *
 * Handle site index requests. 
 *
 * $Id$
 */

class IndexHandler extends Handler {

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 */
	function index() {		
		$templateMgr = &TemplateManager::getManager();
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journalPath = Request::getRequestedJournalPath();
		
		if ($journalPath != 'index' && $journalDao->journalExistsByPath($journalPath)) {
			$templateMgr->display('index/journal.tpl');
			
		} else {
			$siteDao = &DAORegistry::getDAO('SiteDAO');
			$site = &$siteDao->getSite();
			$templateMgr->assign('intro', $site->getIntro());
			
			$journals = &$journalDao->getJournals();
			$templateMgr->assign('journals', $journals);
			$templateMgr->display('index/site.tpl');
		}
	}
	
}

?>
