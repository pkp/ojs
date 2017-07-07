<?php

/**
 * @file plugins/generic/counter/CounterHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterHandler
 * @ingroup plugins_generic_counter
 *
 * @brief Counter statistics request handler.
 */



import('classes.handler.Handler');

class CounterHandler extends Handler {
	/** Plugin associated with this request **/
	var $plugin;
	
	
	/**
	 * Display the main log analyzer page.
	 */
	function index($args, $request) {
		$this->validate();
		$this->setupTemplate($request);
		$plugin =& $this->plugin;

		$counterReportDao = DAORegistry::getDAO('CounterReportDAO');
		$years = $counterReportDao->getYears();

		$templateManager = TemplateManager::getManager($request);
		$templateManager->assign('years', $years);
		$templateManager->display($plugin->getTemplatePath() . 'index.tpl');
	}

	/**
	 * Internal function to collect structures for output
	 * @param $entries
	 * @param $begin
	 * @param $end
	 * @return array
	 */
	function _arrangeEntries($entries, $begin, $end) {
		$ret=null;

		$i = 0;

		foreach ($entries as $entry) {
			$ret[$i]['start'] = date("Y-m-d", mktime(0, 0, 0, $entry['month'], 1, $entry['year']));
			$ret[$i]['end']   = date("Y-m-t", mktime(0, 0, 0, $entry['month'], 1, $entry['year']));
			$ret[$i]['count_html']  = $entry['count_html'];
			$ret[$i]['count_pdf']   = $entry['count_pdf'];
			$i++;
		}

		return $ret;
	}


	/**
	 * Internal function to assign information for the Counter part of a report
	 */
	function _assignTemplateCounterXML($templateManager, $begin, $end='') {
		$journal = Request::getJournal();
		
		$counterReportDao = DAORegistry::getDAO('CounterReportDAO');

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journalIds = $counterReportDao->getJournalIds();

		if ($end == '') $end = $begin;

		$i=0;

		foreach ($journalIds as $journalId) {
			$journal = $journalDao->getById($journalId);
			if (!$journal) continue;
			$entries = $counterReportDao->getMonthlyLogRange($journalId, $begin, $end);

			$journalsArray[$i]['entries'] = $this->_arrangeEntries($entries, $begin, $end);
			$journalsArray[$i]['journalTitle'] = $journal->getLocalizedName();
			$journalsArray[$i]['publisherInstitution'] = $journal->getSetting('publisherInstitution');
			$journalsArray[$i]['printIssn'] = $journal->getSetting('printIssn');
			$journalsArray[$i]['onlineIssn'] = $journal->getSetting('onlineIssn');
			$i++;
		}

		$siteSettingsDao = DAORegistry::getDAO('SiteSettingsDAO');
		$siteTitle = $siteSettingsDao->getSetting('title',AppLocale::getLocale());

		$reqUser = Request::getUser();
		$templateManager->assign('reqUser', Request::getUser());
		$templateManager->assign('journalsArray', $journalsArray);
		$templateManager->assign('siteTitle', $siteTitle);
		$templateManager->assign('base_url', Config::getVar('general','base_url'));
	}


	/**
	 * Counter report in XML
	 */
	function reportXML($args, $request) {
		$this->validate();
		$plugin = $this->plugin;
		$this->setupTemplate($request);

		$templateManager = TemplateManager::getManager($request);

		$year = $request->getUserVar('year');

		$begin = "$year-01-01";
		$end = "$year-12-01";

		$this->_assignTemplateCounterXML($templateManager, $begin, $end);

		$templateManager->display($plugin->getTemplatePath() . 'reportxml.tpl', 'text/xml');
	}


	/**
	 * SUSHI report
	 */
	function sushiXML($args, $request) {
		$this->validate();
		$plugin = $this->plugin;
		$this->setupTemplate($request);

		$templateManager = TemplateManager::getManager($request);

		$SOAPRequest = file_get_contents('php://input');

		// crude handling of namespaces in the input
		// FIXME: only the last prefix in the input will be used for each namespace
		$soapEnvPrefix='';
		$sushiPrefix='';
		$counterPrefix='';

		$re = '/xmlns:([^=]+)="([^"]+)"/';
		preg_match_all($re, $SOAPRequest, $mat, PREG_SET_ORDER);

		foreach ($mat as $xmlns) {
			$modURI = $xmlns[2];
			if ((strrpos($modURI, '/')+1) == strlen($modURI)) $modURI = substr($modURI, 0, -1);
			switch ($modURI) {
				case 'http://schemas.xmlsoap.org/soap/envelope':
					$soapEnvPrefix = $xmlns[1];
					break;
				case 'http://www.niso.org/schemas/sushi':
					$sushiPrefix = $xmlns[1];
					break;
				case 'http://www.niso.org/schemas/sushi/counter':
					$counterPrefix = $xmlns[1];
					break;
			}			
		}
		
		if (strlen($soapEnvPrefix)>0) $soapEnvPrefix .= ':';
		if (strlen($sushiPrefix)>0)   $sushiPrefix .= ':';
		if (strlen($counterPrefix)>0) $counterPrefix .= ':';

		$parser = new XMLParser();
		$tree = $parser->parseText($SOAPRequest);
		$parser->destroy(); // is this necessary?

		if (!$tree) {
			$templateManager->assign('Faultcode', 'Client');
			$templateManager->assign('Faultstring', 'The parser was unable to parse the input.');
			header("HTTP/1.0 500 Internal Server Error");
			$templateManager->display($plugin->getTemplatePath() . 'soaperror.tpl', 'text/xml');
		} else {

			$reportRequestNode = $tree->getChildByName($soapEnvPrefix.'Body')->getChildByName($counterPrefix.'ReportRequest');

			$requestorID = $reportRequestNode->getChildByName($sushiPrefix.'Requestor')->getChildByName($sushiPrefix.'ID')->getValue();
			$requestorName = $reportRequestNode->getChildByName($sushiPrefix.'Requestor')->getChildByName($sushiPrefix.'Name')->getValue();
			$requestorEmail = $reportRequestNode->getChildByName($sushiPrefix.'Requestor')->getChildByName($sushiPrefix.'Email')->getValue();

			$customerReferenceID = $reportRequestNode->getChildByName($sushiPrefix.'CustomerReference')->getChildByName($sushiPrefix.'ID')->getValue();

			$reportName = $reportRequestNode->getChildByName($sushiPrefix.'ReportDefinition')->getAttribute('Name');
			$reportRelease = $reportRequestNode->getChildByName($sushiPrefix.'ReportDefinition')->getAttribute('Release');

			$usageDateRange = $reportRequestNode->getChildByName($sushiPrefix.'ReportDefinition')->getChildByName($sushiPrefix.'Filters')->getChildByName($sushiPrefix.'UsageDateRange');
			$usageDateBegin = $usageDateRange->getChildByName($sushiPrefix.'Begin')->getValue();
			$usageDateEnd = $usageDateRange->getChildByName($sushiPrefix.'End')->getValue();


			CounterHandler::_assignTemplateCounterXML($templateManager, $usageDateBegin, $usageDateEnd);

			$templateManager->assign('requestorID', $requestorID);
			$templateManager->assign('requestorName', $requestorName);
			$templateManager->assign('requestorEmail', $requestorEmail);
			$templateManager->assign('customerReferenceID', $customerReferenceID);
			$templateManager->assign('reportName', $reportName);
			$templateManager->assign('reportRelease', $reportRelease);
			$templateManager->assign('usageDateBegin', $usageDateBegin);
			$templateManager->assign('usageDateEnd', $usageDateEnd);

			$templateManager->assign('templatePath', $plugin->getTemplatePath());

			$templateManager->display($plugin->getTemplatePath() . 'sushixml.tpl', 'text/xml');
		}
	}


	/**
	 * Internal function to form some of the CSV columns
	 */
	function _formColumns(&$cols, $entries) {
		$currTotal = '';
		$htmlTotal = '';
		$pdfTotal = '';
		for ($i = 1; $i <= 12; $i++) {
			$currTotal = '';
			foreach ($entries as $entry) {
				if ($i==$entry['month']) {
					$currTotal = $entry['count_html'] + $entry['count_pdf'];
					$htmlTotal += $entry['count_html'];
					$pdfTotal += $entry['count_pdf'];
					break;
				}
			}
			$cols[]=$currTotal;
		}
		$cols[] = $htmlTotal + $pdfTotal;
		$cols[] = $htmlTotal;
		$cols[] = $pdfTotal;
	}

	/**
	 * Counter report as CSV
	 */
	function report($args, $request) {
		$this->validate();
		$plugin = $this->plugin;
		$this->setupTemplate($request);

		$journal = $request->getJournal();
		$year = $request->getUserVar('year');
		$begin = "$year-01-01";
		$end = "$year-12-01";

		$counterReportDao = DAORegistry::getDAO('CounterReportDAO');

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=counter-' . date('Ymd') . '.csv');

		$fp = fopen('php://output', 'wt');
		fputcsv($fp, array(__('plugins.generic.counter.1a.title1')));
		fputcsv($fp, array(__('plugins.generic.counter.1a.title2', array('year' => $year))));
		fputcsv($fp, array()); // FIXME: Criteria should be here?
		fputcsv($fp, array(__('plugins.generic.counter.1a.dateRun')));
		fputcsv($fp, array(strftime("%Y-%m-%d")));

		$cols = array(
			'',
			__('plugins.generic.counter.1a.publisher'),
			__('plugins.generic.counter.1a.platform'),
			__('plugins.generic.counter.1a.printIssn'),
			__('plugins.generic.counter.1a.onlineIssn')
		);
		for ($i=1; $i<=12; $i++) {
			$time = strtotime($year . '-' . $i . '-01');
			strftime('%b', $time);
			$cols[] = strftime('%b-%Y', $time);
		}

		$cols[] = __('plugins.generic.counter.1a.ytdTotal');
		$cols[] = __('plugins.generic.counter.1a.ytdHtml');
		$cols[] = __('plugins.generic.counter.1a.ytdPdf');
		fputcsv($fp, $cols);

		// Display the totals first
		$totals = $counterReportDao->getMonthlyTotalRange($begin, $end);
		$cols = array(
			__('plugins.generic.counter.1a.totalForAllJournals'),
			'-', // Publisher
			'', // Platform
			'-',
			'-'
		);
		CounterHandler::_formColumns($cols, $totals);
		fputcsv($fp, $cols);

		// Get statistics from the log.
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journalIds = $counterReportDao->getJournalIds();
		foreach ($journalIds as $journalId) {
			$journal = $journalDao->getById($journalId);
			if (!$journal) continue;
			$entries = $counterReportDao->getMonthlyLogRange($journalId, $begin, $end);
			$cols = array(
				$journal->getLocalizedName(),
				$journal->getSetting('publisherInstitution'),
				'Open Journal Systems', // Platform
				$journal->getSetting('printIssn'),
				$journal->getSetting('onlineIssn')
			);
			CounterHandler::_formColumns($cols, $entries);
			fputcsv($fp, $cols);
			unset($journal, $entry);
		}

		fclose($fp);
	}

	/**
	 * Validate that user has site admin privileges or journal manager privileges.
	 * Redirects to the user index page if not properly authenticated.
	 * @param $canRedirect boolean Whether or not to redirect if the user cannot be validated; if not, the script simply terminates.
	 */
	function validate($canRedirect = true) {
		parent::validate();
		$journal = Request::getJournal();
		if (!Validation::isSiteAdmin()) {
			if ($canRedirect) Validation::redirectLogin();
			else exit;
		}

		$this->plugin = Registry::get('plugin');
		return true;
	}
}

?>
