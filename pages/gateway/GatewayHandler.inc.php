<?php

/**
 * GatewayHandler.inc.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.gateway
 *
 * Handle external gateway requests. 
 *
 * $Id$
 */

class GatewayHandler extends Handler {

	function index() {
		Request::redirect('index');
	}

	function lockss() {
		parent::validate();
		
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		
		if ($journal != null) {
			if (!$journal->getSetting('enableLockss')) {
				Request::redirect('index');
			}
			
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issues = $issueDao->getPublishedIssues($journal->getJournalId());
			
			$templateMgr->assign_by_ref('journal', $journal);
			$templateMgr->assign_by_ref('issues', $issues);
			
			$locales = $templateMgr->get_template_vars('languageToggleLocales');
			if (!isset($locales) || empty($locales)) {
				$localeNames = &Locale::getAllLocales();
				$primaryLocale = Locale::getPrimaryLocale();
				$locales = array($primaryLocale => $localeNames[$primaryLocale]);
			}
			$templateMgr->assign_by_ref('locales', $locales);

		} else {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journals = &$journalDao->getEnabledJournals();
			$templateMgr->assign_by_ref('journals', $journals);
		}
		
		$templateMgr->display('gateway/lockss.tpl');
	}
}

?>
