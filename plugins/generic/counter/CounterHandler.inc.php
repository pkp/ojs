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


import('handler.Handler');

class CounterHandler extends Handler {
	/** Plugin associated with this request **/
	var $plugin;
	
	/**
	 * Constructor
	 **/
	function CounterHandler() {
		parent::Handler();
	}
	
	/**
	 * Display the main log analyzer page.
	 */
	function index() {
		$this->validate();
		$this->setupTemplate();
		$plugin =& $this->plugin;

		$counterReportDao =& DAORegistry::getDAO('CounterReportDAO');
		$years = $counterReportDao->getYears();

		$templateManager =& TemplateManager::getManager();
		$templateManager->assign('years', $years);
		$templateManager->display($plugin->getTemplatePath() . 'index.tpl');
	}

	/**
	* Internal function to collect structures for output
	*/
	function _arrangeEntry($entry, $months, $year) {
		$entryArray=null;

		$thisYear = date('Y');
		$thisMonth = date('n');

		for ($i = 0; $i <= 11; $i++) {
			if ( ($i+1 > $thisMonth) && ($year == $thisYear) ) break;
			$entryArray[$i]['start'] = date("Y-m-d", mktime(0, 0, 0, $i+1, 1, $year));
			$entryArray[$i]['end']   = date("Y-m-t", mktime(0, 0, 0, $i+1, 1, $year));
			$entryArray[$i]['count_total'] = $entry[$months[$i]];
		}

		$entryArray[$i]['start'] = date("Y-m-d", mktime(0, 0, 0, 1, 1, $year));
		$entryArray[$i]['end']   = date("Y-m-t", mktime(0, 0, 0, 12, 1, $year));
		$entryArray[$i]['count_total'] = $entry['count_ytd_total'];
		$entryArray[$i]['count_html']  = $entry['count_ytd_html'];
		$entryArray[$i]['count_pdf']   = $entry['count_ytd_pdf'];

		return $entryArray;
	}


	/**
	* Internal function to assign information for the Counter part of a report
	*/
	function _assignTemplateCounterXML($templateManager) {
		$journal =& Request::getJournal();
		$year = Request::getUserVar('year');
		
		if ($year < 2000) $year = 2000;

		$counterReportDao =& DAORegistry::getDAO('CounterReportDAO');
		$months = $counterReportDao->getMonthLabels();

		$entry = $counterReportDao->buildMonthlyTotalLog($year);
		$totalsArray = $this->_arrangeEntry($entry, $months, $year);

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalIds = $counterReportDao->getJournalIds();

		$i=0;

		foreach ($journalIds as $journalId) {
			$journal =& $journalDao->getJournal($journalId);
			if (!$journal) continue;
			$entry = $counterReportDao->buildMonthlyLog($journalId, $year);
			$journalsArray[$i]['entries'] = $this->_arrangeEntry($entry, $months, $year);
			$journalsArray[$i]['journalTitle'] = $journal->getLocalizedTitle();
			$journalsArray[$i]['publisherInstitution'] = $journal->getSetting('publisherInstitution');
			$journalsArray[$i]['printIssn'] = $journal->getSetting('printIssn');
			$journalsArray[$i]['onlineIssn'] = $journal->getSetting('onlineIssn');
			$i++;
		}

		$siteSettingsDao =& DAORegistry::getDAO('SiteSettingsDAO');
		$siteTitle = $siteSettingsDao->getSetting('title',Locale::getLocale());

		$base_url =& Config::getVar('general','base_url');

		$reqUser =& Request::getUser();
		$templateManager->assign_by_ref('reqUser', $reqUser);

		$templateManager->assign_by_ref('totalsArray', $totalsArray);
		$templateManager->assign_by_ref('journalsArray', $journalsArray);

		$templateManager->assign('siteTitle', $siteTitle);
		$templateManager->assign('base_url', $base_url);
	}


	/**
	* Counter report in XML
	*/
	function reportXML() {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate(true);

		$templateManager =& TemplateManager::getManager();

		$this->_assignTemplateCounterXML($templateManager);

		$templateManager->display($plugin->getTemplatePath() . 'reportxml.tpl', 'text/xml');
	}


	/**
	* SUSHI report
	*/
	function sushiXML() {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate(true);

		$templateManager =& TemplateManager::getManager();

		$this->_assignTemplateCounterXML($templateManager);

/*
		$SOAPRequest = '<soapenv:Envelope
						  xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
						  xmlns:coun="http://www.niso.org/schemas/sushi/counter"
						  xmlns:sus="http://www.niso.org/schemas/sushi">
						<soapenv:Header/>
						<soapenv:Body>
						  <coun:ReportRequest Created="?" ID="?">
							<sus:Requestor>
							  <sus:ID>reqIDdata99</sus:ID>
							  <sus:Name>reqNamedata99</sus:Name>
							  <sus:Email>recEmaildata99</sus:Email>
							</sus:Requestor>
							<sus:CustomerReference>
							  <sus:ID>test/prime</sus:ID>
							</sus:CustomerReference>
							<sus:ReportDefinition Name="JR1" Release="3">
							  <sus:Filters>
								 <sus:UsageDateRange>
									<sus:Begin>2009-01-01</sus:Begin>
									<sus:End>2009-06-01</sus:End>
								 </sus:UsageDateRange>
							  </sus:Filters>
							</sus:ReportDefinition>
						  </coun:ReportRequest>
						</soapenv:Body>
						</soapenv:Envelope>';
*/

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

		$templateManager->assign('requestorID', $requestorID);
		$templateManager->assign('requestorName', $requestorName);
		$templateManager->assign('requestorEmail', $requestorEmail);
		$templateManager->assign('customerReferenceID', $customerReferenceID);
		$templateManager->assign('reportName', $reportName);
		$templateManager->assign('reportRelease', $reportRelease);
		$templateManager->assign('usageDateBegin', $usageDateBegin);
		$templateManager->assign('usageDateEnd', $usageDateEnd);
		
		$templateManager->assign('templatePath', $plugin->getTemplatePath());

		$templateManager->display($plugin->getTemplatePath() . 'sushixml.tpl', 'text/plain');
	}


	function report() {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate(true);

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
				$journal->getLocalizedTitle(),
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
		$this->plugin =& $plugin;
		return true;
	}

	/**
	 * Set up common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the heirarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();

		$pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'));

		if ($subclass) $pageHierarchy[] = array(Request::url(null, 'counter'), 'plugins.generic.counter');

		$templateMgr->assign_by_ref('pageHierarchy', $pageHierarchy);
	}
}

?>
