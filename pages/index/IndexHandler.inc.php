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
		parent::validate();
		
		$templateMgr = &TemplateManager::getManager();
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journalPath = Request::getRequestedJournalPath();
		
		if ($journalPath != 'index' && $journalDao->journalExistsByPath($journalPath)) {
			$journal = &Request::getJournal();
			//assign Header and Homepage Content before displaying template
			$headerTitleType = $journal->getSetting('headerTitleType');
			$templateMgr->assign('journalHeaderTitleType', $headerTitleType);
			$templateMgr->assign('pageHeaderTitleType', 3); //pageTitle is invalid
			$templateMgr->assign('journalLogo', $journal->getSetting('journalHeaderLogoImage'));
			$templateMgr->assign('journalHeaderTitle', $journal->getSetting('journalHeaderTitle'));
			$templateMgr->assign('journalHeaderTitleImage', $journal->getSetting('journalHeaderTitleImage'));
			$templateMgr->assign('additionalContent', $journal->getSetting('additionalContent'));
			$templateMgr->assign('homepageImage', $journal->getSetting('homepageImage'));
			$templateMgr->assign('journalDescription', $journal->getSetting('journalDescription'));
			
			$templateMgr->display('index/journal.tpl');	
		} else {
			$siteDao = &DAORegistry::getDAO('SiteDAO');
			$site = &$siteDao->getSite();
			
			if ($site->getJournalRedirect() && ($journal = $journalDao->getJournal($site->getJournalRedirect())) != null) {
				Request::redirect($journal->getPath(), false);
			}
			
			$templateMgr->assign('intro', $site->getIntro());
			$journals = &$journalDao->getEnabledJournals();   //Enabled Added
			$templateMgr->assign('journals', $journals);
			$templateMgr->display('index/site.tpl');
		}
	}
	
}

?>
