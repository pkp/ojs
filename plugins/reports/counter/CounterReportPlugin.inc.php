<?php

/**
 * @file plugins/reports/counter/CounterReportPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterReportPlugin
 * @ingroup plugins_reports_counter
 *
 * @brief Counter report plugin
 */

define('OJS_METRIC_TYPE_LEGACY_COUNTER', 'ojs::legacyCounterPlugin');

import('classes.plugins.ReportPlugin');

class CounterReportPlugin extends ReportPlugin {

	/**
	 * @see PKPPlugin::register($category, $path)
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);

		if($success) {
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getName()
	 */
	function getName() {
		return 'CounterReportPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.reports.counter');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.reports.counter.description');
	}

	/**
	 * @see PKPPlugin::isSitePlugin()
	 */
	function isSitePlugin() {
		return true;
	}

	/**
	 * @see ReportPlugin::setBreadcrumbs()
	 */
	function setBreadcrumbs() {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			),
			array(
				Request::url(null, 'manager', 'statistics'),
				'manager.statistics'
			)
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * @see ReportPlugin::display()
	 */
	function display(&$args, &$request) {
		parent::display($args);

		$journal =& $request->getJournal();
		if (!Validation::isSiteAdmin()) {
			Validation::redirectLogin();
		}

		$this->setBreadcrumbs();
		if ($request->getUserVar('type')) {
			$oldStats = (boolean) $request->getUserVar('useOldCounterStats');
			$year = (string) $request->getUserVar('year');
			$type = (string) $request->getUserVar('type');
			switch ($type) {
				case 'report':
					$this->_report($request, $year, $oldStats);
					break;
				case 'reportxml':
					$this->_reportXml($request, $year, $oldStats);
					break;
				case 'sushixml':
					$this->_sushiXML($oldStats);
					break;
			}
		} else {
			$years = $this->_getYears();
			$legacyYears = $this->_getYears(true);
			$templateManager =& TemplateManager::getManager();
			$templateManager->assign('years', $years);
			if (!empty($legacyYears)) $templateManager->assign('legacyYears', $legacyYears);
			$templateManager->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	/**
	 * Generate a report file.
	 * @param $request PKPRequest
	 * @param $year string
	 */
	function _report(&$request, $year, $useLegacyStats) {
		$journal =& $request->getJournal();
		list($begin, $end) = $this->_getLimitDates($year);

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=counter-' . date('Ymd') . '.csv');

		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array(__('plugins.reports.counter.1a.title1')));
		String::fputcsv($fp, array(__('plugins.reports.counter.1a.title2', array('year' => $year))));
		String::fputcsv($fp, array()); // FIXME: Criteria should be here?
		String::fputcsv($fp, array(__('plugins.reports.counter.1a.dateRun')));
		String::fputcsv($fp, array(strftime("%Y-%m-%d")));

		$cols = array(
				'',
		__('plugins.reports.counter.1a.publisher'),
		__('plugins.reports.counter.1a.platform'),
		__('plugins.reports.counter.1a.printIssn'),
		__('plugins.reports.counter.1a.onlineIssn')
		);
		for ($i=1; $i<=12; $i++) {
			$time = strtotime($year . '-' . $i . '-01');
			strftime('%b', $time);
			$cols[] = strftime('%b-%Y', $time);
		}

		$cols[] = __('plugins.reports.counter.1a.ytdTotal');
		$cols[] = __('plugins.reports.counter.1a.ytdHtml');
		$cols[] = __('plugins.reports.counter.1a.ytdPdf');
		fputcsv($fp, $cols);

		// Display the totals first
		$totals = $this->_getMonthlyTotalRange($begin, $end, $useLegacyStats);
		$cols = array(
		__('plugins.reports.counter.1a.totalForAllJournals'),
				'-', // Publisher
				'', // Platform
				'-',
				'-'
		);
		$this->_formColumns($cols, $totals);
		fputcsv($fp, $cols);

		// Get statistics from the log.
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalIds = $this->_getJournalIds($useLegacyStats);
		foreach ($journalIds as $journalId) {
			$journal =& $journalDao->getById($journalId);
			if (!$journal) continue;
			$entries = $this->_getMonthlyLogRange($journalId, $begin, $end, $useLegacyStats);
			$cols = array(
				$journal->getLocalizedTitle(),
				$journal->getSetting('publisherInstitution'),
				'Open Journal Systems', // Platform
				$journal->getSetting('printIssn'),
				$journal->getSetting('onlineIssn')
			);
			$this->_formColumns($cols, $entries);
			fputcsv($fp, $cols);
			unset($journal, $entry);
		}

		fclose($fp);
	}

	/**
	 *
	 * Counter report in XML
	 * @param $request PKPRequest
	 * @param $year string
	 * @param $useLegacyStats boolean
	 */
	function _reportXML(&$request, $year, $useLegacyStats) {
		$templateManager =& TemplateManager::getManager();
		list($begin, $end) = $this->_getLimitDates($year);

		$this->_assignTemplateCounterXML($templateManager, $begin, $end, $useLegacyStats);
		$templateManager->display($this->getTemplatePath() . 'reportxml.tpl', 'text/xml');
	}

	/**
	 * SUSHI report
	 * @param $useLegacyStats boolean
	*/
	function _sushiXML($useLegacyStats) {
		$templateManager =& TemplateManager::getManager();

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
			$templateManager->display($this->getTemplatePath() . 'soaperror.tpl', 'text/xml');
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


			$this->_assignTemplateCounterXML($templateManager, $usageDateBegin, $usageDateEnd, $useLegacyStats);

			$templateManager->assign('requestorID', $requestorID);
			$templateManager->assign('requestorName', $requestorName);
			$templateManager->assign('requestorEmail', $requestorEmail);
			$templateManager->assign('customerReferenceID', $customerReferenceID);
			$templateManager->assign('reportName', $reportName);
			$templateManager->assign('reportRelease', $reportRelease);
			$templateManager->assign('usageDateBegin', $usageDateBegin);
			$templateManager->assign('usageDateEnd', $usageDateEnd);

			$templateManager->assign('templatePath', $this->getTemplatePath());

			$templateManager->display($this->getTemplatePath() . 'sushixml.tpl', 'text/xml');
		}
	}

	/**
	* Internal function to form some of the CSV columns
	*/
	function _formColumns(&$cols, $entries) {
		$currTotal = 0;
		$htmlTotal = 0;
		$pdfTotal = 0;
		for ($i = 1; $i <= 12; $i++) {
			$currTotal = 0;
			foreach ($entries as $entry) {
				$month = (int) substr($entry[STATISTICS_DIMENSION_MONTH], 4, 2);
				if ($i== $month) {
					$metric = $entry[STATISTICS_METRIC];
					$currTotal += $metric;
					if ($entry[STATISTICS_DIMENSION_FILE_TYPE] == STATISTICS_FILE_TYPE_HTML) {
						$htmlTotal += $metric;
					} else {
						$pdfTotal += $metric;
					}
				}
			}
			$cols[]=$currTotal;
		}
		$cols[] = $htmlTotal + $pdfTotal;
		$cols[] = $htmlTotal;
		$cols[] = $pdfTotal;
	}

	/**
	 * Internal function to assign information for the Counter part of a report
	 * @param $templateManager PKPTemplateManager
	 * @param $begin string
	 * @param $end string
	 * @param $useLegacyStats boolean
	 */
	function _assignTemplateCounterXML(&$templateManager, $begin, $end='', $useLegacyStats) {
		$journal =& Request::getJournal();

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalIds = $this->_getJournalIds($useLegacyStats);

		if ($end == '') $end = $begin;

		$i=0;

		foreach ($journalIds as $journalId) {
			$journal =& $journalDao->getById($journalId);
			if (!$journal) continue;
			$entries = $this->_getMonthlyLogRange($journalId, $begin, $end, $useLegacyStats);

			$journalsArray[$i]['entries'] = $this->_arrangeEntries($entries, $begin, $end);
			$journalsArray[$i]['journalTitle'] = $journal->getLocalizedTitle();
			$journalsArray[$i]['publisherInstitution'] = $journal->getSetting('publisherInstitution');
			$journalsArray[$i]['printIssn'] = $journal->getSetting('printIssn');
			$journalsArray[$i]['onlineIssn'] = $journal->getSetting('onlineIssn');
			$i++;
		}

		$siteSettingsDao =& DAORegistry::getDAO('SiteSettingsDAO');
		$siteTitle = $siteSettingsDao->getSetting('title',AppLocale::getLocale());

		$base_url =& Config::getVar('general','base_url');

		$reqUser =& Request::getUser();
		$templateManager->assign_by_ref('reqUser', $reqUser);

		$templateManager->assign_by_ref('journalsArray', $journalsArray);

		$templateManager->assign('siteTitle', $siteTitle);
		$templateManager->assign('base_url', $base_url);
	}

	/**
	* Internal function to collect structures for output
	*/
	function _arrangeEntries($entries, $begin, $end) {
		$ret=null;

		$i = 0;

		foreach ($entries as $entry) {
			$year = substr($entry['month'], 0, 4);
			$month = substr($entry['month'], 4, 2);
			$start = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
			$end = date("Y-m-t", mktime(0, 0, 0, $month, 1, $year));

			$rangeExists = false;
			foreach ($ret as $key => $record) {
				if ($record['start'] == $start && $record['end'] == $end) {
					$rangeExists = true;
					break;
				}
			}

			if (!$rangeExists) {
				$workingKey = $i;
				$i++;

				$ret[$workingKey]['start'] = $start;
				$ret[$workingKey]['end'] = $end;
			} else {
				$workingKey = $key;
			}

			if ($entry[STATISTICS_DIMENSION_FILE_TYPE] == STATISTICS_FILE_TYPE_HTML) {
				$ret[$workingKey]['count_html']  = $entry[STATISTICS_METRIC];
			} else {
				$ret[$workingKey]['count_pdf']   = $entry[STATISTICS_METRIC];
			}
		}

		return $ret;
	}

	/**
	 * Return the begin and end dates
	 * based on the passed year.
	 * @param $year string
	 * @return array
	 */
	function _getLimitDates($year) {
		$begin = "$year-01-01";
		$end = "$year-12-01";

		return array($begin, $end);
	}

	/**
	* Get the years for which log entries exist in the DB.
	* @param $useLegacyStats boolean Use the old counter plugin data.
	* @return array
	*/
	function _getYears($useLegacyStats = false) {
		if ($useLegacyStats) {
			$metricType = OJS_METRIC_TYPE_LEGACY_COUNTER;
			$filter = array();
		} else {
			$metricType = OJS_METRIC_TYPE_COUNTER;
			$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_GALLEY);
		}
		$metricsDao =& DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$results = $metricsDao->getMetrics($metricType, array(STATISTICS_DIMENSION_MONTH), $filter);
		$years = array();
		foreach($results as $record) {
			$year = substr($record['month'], 0, 4);
			if (in_array($year, $years)) continue;
			$years[] = $year;
		}

		return $years;
	}

	/**
	 * Get the valid journal IDs for which log entries exist in the DB.
	 * @param $useLegacyStats boolean Use the old counter plugin data.
	 * @return array
	 */
	function _getJournalIds($useLegacyStats = false) {
		$metricsDao =& DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		if ($useLegacyStats) {
			$results = $metricsDao->getMetrics(OJS_METRIC_TYPE_LEGACY_COUNTER, array(STATISTICS_DIMENSION_ASSOC_ID));
			$fieldId = STATISTICS_DIMENSION_ASSOC_ID;
		} else {
			$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => ASSOC_TYPE_GALLEY);
			$results = $metricsDao->getMetrics(OJS_METRIC_TYPE_COUNTER, array(STATISTICS_DIMENSION_CONTEXT_ID), $filter);
			$fieldId = STATISTICS_DIMENSION_CONTEXT_ID;
		}
		$journalIds = array();
		foreach($results as $record) {
			$journalIds[] = $record[$fieldId];
		}
		return $journalIds;
	}


	/**
	 * Retrieve a monthly log entry range.
	 * @param $journalId int
	 * @param $begin
	 * @param $end
	 * @param $useLegacyStats boolean Use the old counter plugin data.
	 * @return 2D array
	 */
	function _getMonthlyLogRange($journalId = null, $begin, $end, $useLegacyStats = false) {
		$begin = date('Ym', strtotime($begin));
		$end = date('Ym', strtotime($end));

		$metricsDao =& DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$columns = array(STATISTICS_DIMENSION_MONTH, STATISTICS_DIMENSION_FILE_TYPE);
		$filter = array(
			STATISTICS_DIMENSION_MONTH => array('from' => $begin, 'to' => $end)
		);

		if ($useLegacyStats) {
			$dimension = STATISTICS_DIMENSION_ASSOC_ID;
			$metricType = OJS_METRIC_TYPE_LEGACY_COUNTER;
		} else {
			$dimension = STATISTICS_DIMENSION_CONTEXT_ID;
			$metricType = OJS_METRIC_TYPE_COUNTER;
			$filter[STATISTICS_DIMENSION_ASSOC_TYPE] = ASSOC_TYPE_GALLEY;
		}

		if ($journalId) {
			$columns[] = $dimension;
			$filter[$dimension] = $journalId;
		}

		$results = $metricsDao->getMetrics($metricType, $columns, $filter);

		if (empty($results)) {
			return null;
		}

		return $results;
	}

	/**
	 * Retrieve a monthly log entry range.
	 * @param $begin
	 * @param $end
	 * @param $useLegacyStats boolean Use the old counter plugin data.
	 * @return 2D array
	 */
	function _getMonthlyTotalRange($begin, $end, $useLegacyStats = false) {
		return $this->_getMonthlyLogRange(null, $begin, $end, $useLegacyStats);
	}
}

?>
