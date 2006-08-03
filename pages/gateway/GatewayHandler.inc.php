<?php

/**
 * GatewayHandler.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
		Request::redirect(null, 'index');
	}

	function lockss() {
		parent::validate();
		
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		
		if ($journal != null) {
			if (!$journal->getSetting('enableLockss')) {
				Request::redirect(null, 'index');
			}
			
			$year = Request::getUserVar('year');
			
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			
			// FIXME Should probably go in IssueDAO or a subclass
			if (isset($year)) {
				$year = (int)$year;
				$result = &$issueDao->retrieve(
					'SELECT * FROM issues WHERE journal_id = ? AND year = ? AND published = 1 ORDER BY current DESC, year ASC, volume ASC, number ASC',
					array($journal->getJournalId(), $year)
				);
				if ($result->RecordCount() == 0) {
					unset($year);
				}
			}
			
			if (!isset($year)) {
				$showInfo = true;
				$result = &$issueDao->retrieve(
					'SELECT MAX(year) FROM issues WHERE journal_id = ? AND published = 1',
					$journal->getJournalId()
				);
				list($year) = $result->fields;
				$result = &$issueDao->retrieve(
					'SELECT * FROM issues WHERE journal_id = ? AND year = ? AND published = 1 ORDER BY current DESC, year ASC, volume ASC, number ASC',
					array($journal->getJournalId(), $year)
				);
			} else {
				$showInfo = false;
			}
			
			$issues = &new DAOResultFactory($result, $issueDao, '_returnIssueFromRow');
			
			$prevYear = null;
			$nextYear = null;
			if (isset($year)) {
				$result = &$issueDao->retrieve(
					'SELECT MAX(year) FROM issues WHERE journal_id = ? AND published = 1 AND year < ?',
					array($journal->getJournalId(), $year)
				);
				list($prevYear) = $result->fields;
				
				$result = &$issueDao->retrieve(
					'SELECT MIN(year) FROM issues WHERE journal_id = ? AND published = 1 AND year > ?',
					array($journal->getJournalId(), $year)
				);
				list($nextYear) = $result->fields;
			}
			
			$templateMgr->assign_by_ref('journal', $journal);
			$templateMgr->assign_by_ref('issues', $issues);
			$templateMgr->assign('year', $year);
			$templateMgr->assign('prevYear', $prevYear);
			$templateMgr->assign('nextYear', $nextYear);
			$templateMgr->assign('showInfo', $showInfo);
			
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

	/**
	 * Handle requests for gateway plugins.
	 */
	function plugin($args) {
		parent::validate();
		$pluginName = array_shift($args);

		$plugins =& PluginRegistry::loadCategory('gateways');
		if (isset($pluginName) && isset($plugins[$pluginName])) {
			$plugin =& $plugins[$pluginName];
			if (!$plugin->fetch($args)) {
				Request::redirect(null, 'index');
			}
		}
		else Request::redirect(null, 'index');
	}
}

?>
