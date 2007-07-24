<?php

/**
 * @file CounterHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.counter
 * @class CounterHandler
 *
 * Counter statistics request handler.
 *
 * $Id$
 */

class CounterHandler extends Handler {
	/**
	 * Display the main log analyzer page.
	 */
	function index() {
		list($plugin) = CounterHandler::validate();
		CounterHandler::setupTemplate();

		// Fetch a list of years for which reports can be generated.
		$years = array();
		$logEntryDao =& DAORegistry::getDAO('LogEntryDAO');
		$log =& $logEntryDao->parse();
		foreach ($log as $entry) {
			$year = strftime('%Y', strtotime($entry->getStamp()));
			if (!in_array($year, $years)) {
				$years[] = $year;
			}
		}
		unset($log);

		$templateManager = &TemplateManager::getManager();
		$templateManager->assign('years', $years);
		$templateManager->display($plugin->getTemplatePath() . 'index.tpl');
	}

	function browseLog() {
		list($plugin) = CounterHandler::validate();
		CounterHandler::setupTemplate(true);

		$journal =& Request::getJournal();
		$rangeInfo = Handler::getRangeInfo('entries');

		$logEntryDao =& DAORegistry::getDAO('LogEntryDAO');
		$log =& $logEntryDao->parse();

		// Map session IDs to something more readable
		$sessions = array();
		$userNum = 0;
		foreach ($log as $entry) {
			if (!isset($sessions[$entry->getUser()])) {
 				$sessions[$entry->getUser()] = Locale::translate('plugins.generic.counter.sessionNumber', array('sessionNumber' => ++$userNum));
			}
		}

		if ($rangeInfo->isValid()) {
			$page = $rangeInfo->getPage();
			$count = $rangeInfo->getCount();
		} else {
			$page = 1;
			$count = min(count($log), $journal->getSetting('itemsPerPage'));
		}
		$logIterator = &new ArrayItemIterator($log, $page, $count);

		$templateManager = &TemplateManager::getManager();
		$templateManager->assign_by_ref ('entries', $logIterator);
		$templateManager->assign ('sessions', $sessions);

		$templateManager->display($plugin->getTemplatePath() . 'browser.tpl');
	}

	function report() {
		list($plugin) = CounterHandler::validate();
		CounterHandler::setupTemplate(true);

		$journal =& Request::getJournal();
		$year = Request::getUserVar('year');

		$logEntryDao =& DAORegistry::getDAO('LogEntryDAO');
		$log =& $logEntryDao->parse(null, $year);

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.title1')) . "\n";
		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.title2', array('year' => $year))) . "\n";
		echo "\n"; // FIXME: Criteria should be here?
		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.dateRun')) . "\n";
		echo CounterHandler::csvEscape(strftime("%Y-%m-%d")) . "\n";

		echo CounterHandler::csvEscape('') . "\t";
		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.publisher')) . "\t";
		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.platform')) . "\t";
		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.printIssn')) . "\t";
		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.onlineIssn')) . "\t";
		for ($i=1; $i<=12; $i++) {
			$time = strtotime($year . '-' . $i . '-01');
			strftime('%b', $time);
			echo CounterHandler::csvEscape(strftime('%b-%Y', $time)) . "\t";
		}
		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.ytdTotal')) . "\t";
		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.ytdHtml')) . "\t";
		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.ytdPdf')) . "\n";

		// Get statistics from the log.

		$journals = array();
		foreach ($log as $entry) if ($entry->getType() != LOG_ENTRY_TYPE_SEARCH) {
			$journalUrl = $entry->getJournalUrl();
			if (!isset($journals[$journalUrl])) {
				$journals[$journalUrl] = array(
					'journal' => $entry->getJournal(),
					'publisher' => $entry->getPublisher(),
					'printIssn' => $entry->getPrintIssn(),
					'onlineIssn' => $entry->getOnlineIssn(),
					'months' => array_fill(1, 12, 0),
					'ytdTotal' => 0,
					'ytdHtml' => 0,
					'ytdPdf' => 0
				);
			}
			$month = (int) strftime('%m', strtotime($entry->getStamp()));
			$journals[$journalUrl]['ytdTotal']++;
			$journals[$journalUrl]['months'][$month]++;
			switch ($entry->getType()) {
				case LOG_ENTRY_TYPE_HTML_ARTICLE:
					$journals[$journalUrl]['ytdHtml']++;
					break;
				case LOG_ENTRY_TYPE_PDF_ARTICLE:
					$journals[$journalUrl]['ytdPdf']++;
					break;
				case LOG_ENTRY_TYPE_OTHER_ARTICLE:
				default:
					break;
			}
		}

		// Display the totals.

		echo CounterHandler::csvEscape(Locale::translate('plugins.generic.counter.1a.totalForAllJournals')) . "\t";
		echo CounterHandler::csvEscape('') . "\t";
		echo CounterHandler::csvEscape(Locale::translate('common.openJournalSystems')) . "\t";
		echo CounterHandler::csvEscape('') . "\t";
		echo CounterHandler::csvEscape('') . "\t";

		$months = array_fill(1, 12, 0);
		$ytdTotal = $ytdHtml = $ytdPdf = 0;
		foreach ($journals as $journalEntry) {
			foreach ($journalEntry['months'] as $key => $value) $months[$key] += $value;
			$ytdTotal += $journalEntry['ytdTotal'];
			$ytdHtml += $journalEntry['ytdHtml'];
			$ytdPdf += $journalEntry['ytdPdf'];
		}
		foreach ($months as $month) echo CounterHandler::csvEscape($month) . "\t";
		echo CounterHandler::csvEscape($ytdTotal) . "\t";
		echo CounterHandler::csvEscape($ytdHtml) . "\t";
		echo CounterHandler::csvEscape($ytdPdf) . "\n";

		// Display entries for each journal.

		foreach ($journals as $journalEntry) {
			echo CounterHandler::csvEscape($journalEntry['journal']) . "\t";
			echo CounterHandler::csvEscape($journalEntry['publisher']) . "\t";
			echo CounterHandler::csvEscape(Locale::translate('common.openJournalSystems')) . "\t";
			echo CounterHandler::csvEscape($journalEntry['printIssn']) . "\t";
			echo CounterHandler::csvEscape($journalEntry['onlineIssn']) . "\t";

			foreach ($journalEntry['months'] as $month) echo CounterHandler::csvEscape($month) . "\t";
			echo CounterHandler::csvEscape($journalEntry['ytdTotal']) . "\t";
			echo CounterHandler::csvEscape($journalEntry['ytdHtml']) . "\t";
			echo CounterHandler::csvEscape($journalEntry['ytdPdf']) . "\n";
		}
	}

	function clearLog() {
		list($plugin) = CounterHandler::validate(false);
		$logEntryDao =& DAORegistry::getDAO('LogEntryDAO');
		$logEntryDao->clearLog();
		Request::redirect(null, 'counter');
	}

	/**
	 * Validate that user has site admin privileges or journal manager priveleges.
	 * Redirects to the user index page if not properly authenticated.
	 * @param $canRedirect boolean Whether or not to redirect if the user cannot be validated; if not, the script simply terminates.
	 */
	function validate($canRedirect = true) {
		parent::validate();
		$journal =& Request::getJournal();
		if (!Validation::isSiteAdmin()) {
			if ($canRedirect) Validation::redirectLogin();
			else exit;
		}

		$plugin =& Registry::get('plugin');
		return array(&$plugin);
	}

	/**
	 * Set up common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the heirarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();

		$pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'));

		if ($subclass) $pageHierarchy[] = array(Request::url(null, 'counter'), 'plugins.generic.counter');

		$templateMgr->assign_by_ref('pageHierarchy', $pageHierarchy);
	}

	function csvEscape($value) {
		$value = str_replace('"', '""', $value);
		return '"' . $value . '"';
	}
}

?>
