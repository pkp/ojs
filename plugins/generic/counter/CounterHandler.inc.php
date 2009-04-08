<?php

/**
 * @file CounterHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterHandler
 * @ingroup plugins_generic_counter
 *
 * @brief Counter statistics request handler.
 */

// $Id$


class CounterHandler extends Handler {
	/**
	 * Display the main log analyzer page.
	 */
	function index() {
		list($plugin) = CounterHandler::validate();
		CounterHandler::setupTemplate();

		// Fetch a list of years for which reports can be generated.
		$counterReportDao =& DAORegistry::getDAO('CounterReportDAO');
		$years = $counterReportDao->getYears();

		$templateManager =& TemplateManager::getManager();
		$templateManager->assign('years', $years);
		$templateManager->display($plugin->getTemplatePath() . 'index.tpl');
	}

	function report() {
		list($plugin) = CounterHandler::validate();
		CounterHandler::setupTemplate(true);

		$journal =& Request::getJournal();
		$year = Request::getUserVar('year');

		$counterReportDao =& DAORegistry::getDAO('CounterReportDAO');

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array(Locale::translate('plugins.generic.counter.1a.title1')));
		String::fputcsv($fp, array(Locale::translate('plugins.generic.counter.1a.title2', array('year' => $year))));
		String::fputcsv($fp, array()); // FIXME: Criteria should be here?
		String::fputcsv($fp, array(Locale::translate('plugins.generic.counter.1a.dateRun')));
		String::fputcsv($fp, array(strftime("%Y-%m-%d")));

		$cols = array(
			'',
			Locale::translate('plugins.generic.counter.1a.publisher'),
			Locale::translate('plugins.generic.counter.1a.platform'),
			Locale::translate('plugins.generic.counter.1a.printIssn'),
			Locale::translate('plugins.generic.counter.1a.onlineIssn')
		);
		for ($i=1; $i<=12; $i++) {
			$time = strtotime($year . '-' . $i . '-01');
			strftime('%b', $time);
			$cols[] = strftime('%b-%Y', $time);
		}

		$cols[] = Locale::translate('plugins.generic.counter.1a.ytdTotal');
		$cols[] = Locale::translate('plugins.generic.counter.1a.ytdHtml');
		$cols[] = Locale::translate('plugins.generic.counter.1a.ytdPdf');
		fputcsv($fp, $cols);

		// Display the totals first
		$entry = $counterReportDao->buildMonthlyTotalLog($year);
		$cols = array(
			Locale::translate('plugins.generic.counter.1a.totalForAllJournals'),
			'-', // Publisher
			'', // Platform
			'-',
			'-'
		);
		$months = $counterReportDao->getMonthLabels();
		for ($i = 0; $i < 12; $i++) {
			$cols[] = $entry[$months[$i]];
		}
		$cols[] = $entry['count_ytd_total'];
		$cols[] = $entry['count_ytd_html'];
		$cols[] = $entry['count_ytd_pdf'];
		fputcsv($fp, $cols);

		// Get statistics from the log.
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalIds = $counterReportDao->getJournalIds();
		foreach ($journalIds as $journalId) {
			$journal =& $journalDao->getJournal($journalId);
			if (!$journal) continue;
			$entry = $counterReportDao->buildMonthlyLog($journalId, $year);
			$cols = array(
				$journal->getJournalTitle(),
				$journal->getLocalizedSetting('publisherInstitution'),
				'', // Platform
				$journal->getSetting('printIssn'),
				$journal->getSetting('onlineIssn')
			);
			$months = $counterReportDao->getMonthLabels();
			for ($i = 0; $i < 12; $i++) {
				$cols[] = $entry[$months[$i]];
			}
			$cols[] = $entry['count_ytd_total'];
			$cols[] = $entry['count_ytd_html'];
			$cols[] = $entry['count_ytd_pdf'];
			fputcsv($fp, $cols);
			unset($journal, $entry);
		}

		fclose($fp);
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
		$templateMgr =& TemplateManager::getManager();

		$pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'));

		if ($subclass) $pageHierarchy[] = array(Request::url(null, 'counter'), 'plugins.generic.counter');

		$templateMgr->assign_by_ref('pageHierarchy', $pageHierarchy);
	}
}

?>
