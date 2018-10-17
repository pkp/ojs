<?php

/**
 * @file plugins/reports/counter/classes/CounterReport.inc.php
 *
 * Copyright (c) 2014 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * @class CounterReport
 * @ingroup plugins_reports_counter
 *
 * @brief A COUNTER report, base class
 */
require_once(dirname(dirname(__FILE__)).'/classes/COUNTER/COUNTER.php');

define('COUNTER_EXCEPTION_WARNING', 0);
define('COUNTER_EXCEPTION_ERROR', 1);
define('COUNTER_EXCEPTION_PARTIAL_DATA', 4);
define('COUNTER_EXCEPTION_NO_DATA', 8);
define('COUNTER_EXCEPTION_BAD_COLUMNS', 16);
define('COUNTER_EXCEPTION_BAD_FILTERS', 32);
define('COUNTER_EXCEPTION_BAD_ORDERBY', 64);
define('COUNTER_EXCEPTION_BAD_RANGE', 128);
define('COUNTER_EXCEPTION_INTERNAL', 256);

define('COUNTER_CLASS_PREFIX', 'CounterReport');

// COUNTER as of yet is not internationalized and requires English constants
define('COUNTER_LITERAL_ARTICLE', 'Article');
define('COUNTER_LITERAL_JOURNAL', 'Journal');
define('COUNTER_LITERAL_PROPRIETARY', 'Proprietary');

class CounterReport {

	/**
	 * @var string $_release A COUNTER release number
	 */
	var $_release;

	/**
	 * @var array $_errors An array of accumulated Exceptions
	 */
	var $_errors;

	/**
	 * Constructor
	 * @param string $release
	 */
	function CounterReport($release) {
		$this->_release = $release;
	}


	/**
	 * Get the COUNTER Release
	 * @return $string
	 */
	function getRelease() {
		return $this->_release;
	}

	/**
	 * Get the report code
	 * @return $string
	 */
	function getCode() {
		return substr(get_class($this), strlen(COUNTER_CLASS_PREFIX));
	}

	/**
	 * Get the COUNTER metric type for an Statistics file type
	 * @param $filetype string
	 * @return string
	 */
	function getKeyForFiletype($filetype) {
		switch ($filetype) {
			case STATISTICS_FILE_TYPE_HTML:
				$metricTypeKey = 'ft_html';
				break;
			case STATISTICS_FILE_TYPE_PDF:
				$metricTypeKey = 'ft_pdf';
				break;
			case STATISTICS_FILE_TYPE_OTHER:
			default:
				$metricTypeKey = 'other';
		}
		return $metricTypeKey;
	}

	/**
	 * Abstract method must be implemented in the child class
	 * Get the report title
	 * @return $string
	 */
	function getTitle() {
		assert(false);
	}

	/*
	 * Convert an OJS metrics request to COUNTER ReportItems
	 * Abstract method must be implemented by subclass
	 * @param $columns string|array column (aggregation level) selection
	 * @param $filters array report-level filter selection
	 * @param $orderBy array order criteria
	 * @param $range null|DBResultRange paging specification
	 * @see ReportPlugin::getMetrics for more details on parameters
	 * @return array COUNTER\ReportItem array
	 */
	function getReportItems($columns = array(), $filters = array(), $orderBy = array(), $range = null) {
		assert(false);
	}

	/**
	 * Get an array of errors
	 * @return array of Exceptions
	 */
	function getErrors() {
		return $this->_errors ? $this->_errors : array();
	}

	/**
	 * Set an errors condition; Proper Exception handling is deferred until the OJS 3.0 Release
	 * @param $error Exception
	 */
	function setError($error) {
		if (!$this->_errors) {
			$this->_errors = array();
		}
		array_push($this->_errors, $error);
	}

	/**
	 * Ensure that the $filters do not exceed the current Context
	 * @param array() $filters
	 * @return array()
	 */
	protected function filterForContext($filters) {
		$request = Application::getRequest();
		$journal = $request->getContext();
		$journalId = $journal ? $journal->getId() : '';
		// If the request context is at the journal level, the dimension context id must be that same journal id
		if ($journalId) {
			if (isset($filters[STATISTICS_DIMENSION_CONTEXT_ID]) && $filters[STATISTICS_DIMENSION_CONTEXT_ID] != $journalId) {
				$this->setError(new Exception(__('plugins.reports.counter.generic.exception.filter'), COUNTER_EXCEPTION_WARNING | COUNTER_EXCEPTION_BAD_FILTERS));
			}
			$filters[STATISTICS_DIMENSION_CONTEXT_ID] = $journalId;
		}
		return $filters;
	}

	/**
	 * Given a Year-Month period and array of COUNTER\PerformanceCounters, create a COUNTER\Metric
	 * @param string $period Date in Ym format
	 * @param array $counters COUNTER\PerformanceCounter array
	 * @return COUNTER\Metric
	 */
	protected function createMetricByMonth($period, $counters) {
		$metric = array();
		try {
			$metric = new COUNTER\Metric(
				// Date range for JR1 is beginning of the month to end of the month
				new COUNTER\DateRange(
					DateTime::createFromFormat('Ymd His', $period.'01 000000'),
					DateTime::createFromFormat('Ymd His', $period.date('t', strtotime(substr($period, 0, 4).'-'.substr($period, 4).'-01')).' 235959')
				),
				'Requests',
				$counters
			);
		} catch (Exception $e) {
			$this->setError($e, COUNTER_EXCEPTION_ERROR | COUNTER_EXCEPTION_INTERNAL);
		}
		return $metric;
	}

	/**
	 * Construct a Reports result containing the provided performance metrics
	 * @param $reportItems array COUNTER\ReportItem
	 * @return string xml
	 */
	function createXML($reportItems) {
		$errors = $this->getErrors();
		$fatal = false;
		foreach ($errors as $error) {
			if ($error->getCode() & COUNTER_EXCEPTION_ERROR) {
				$fatal = true;
			}
		}
		if (!$fatal) {
			try {
				$report = new COUNTER\Reports(
					new COUNTER\Report(
						PKPString::generateUUID(),
						$this->getRelease(),
						$this->getCode(),
						$this->getTitle(),
						new COUNTER\Customer(
							'0', // customer id is unused
							$reportItems,
							__('plugins.reports.counter.allCustomers')
						),
						new COUNTER\Vendor(
							$this->getVendorID(),
							$this->getVendorName(),
							$this->getVendorContacts(),
							$this->getVendorWebsiteUrl(),
							$this->getVendorLogoUrl()
						)
					)
				);
			} catch (Exception $e) {
				$this->setError($e, COUNTER_EXCEPTION_ERROR | COUNTER_EXCEPTION_INTERNAL);
			}
			if (isset($report)) {
				return (string) $report;
			}
		}
		return;
	}

	/**
	 * Get the Vendor Id
	 * @return $string
	 */
	function getVendorId() {
		return $this->_getVendorComponent('id');
	}

	/**
	 * Get the Vendor Name
	 * @return $string
	 */
	function getVendorName() {
		return (string) $this->_getVendorComponent('name');
	}

	/**
	 * Get the Vendor Contacts
	 * @return array() COUNTER\Contact
	 */
	function getVendorContacts() {
		return $this->_getVendorComponent('contacts');
	}

	/**
	 * Get the Vendor Website URL
	 * @return string
	 */
	function getVendorWebsiteUrl() {
		return $this->_getVendorComponent('website');
	}

	/**
	 * Get the Vendor Contacts
	 * @return array COUNTER\Contact
	 */
	function getVendorLogoUrl() {
		return $this->_getVendorComponent('logo');
	}

	/**
	 * Get the Vendor Componet by key
	 * @param $key string
	 * @return mixed
	 */
	function _getVendorComponent($key) {
		$request = Application::getRequest();
		$site = $request->getSite();
		$context = $request->getContext();
		$contextDao = Application::getContextDAO();
		$availableContexts = $contextDao->getAvailable();
		switch ($key) {
			case 'name':
				if ($availableContexts->getCount() > 1) {
					$name = $site->getLocalizedTitle();
				} else {
					$name =  $context->getSetting('publisherInstitution');
					if (empty($name)) {
						$name = $context->getLocalizedName();
					}
				}
				return $name;
			case 'id':
				return $request->getBaseUrl();
			case 'contacts':
				try {
					if ($availableContexts->getCount() > 1) {
						$contactName = $site->getLocalizedContactName();
						$contactEmail =  $site->getLocalizedContactEmail();
					} else {
						$contactName = $context->getContactName();
						$contactEmail =  $context->getContactEmail();
					}
					$contact = new COUNTER\Contact($contactName, $contactEmail);
				} catch (Exception $e) {
					$this->setError($e);
					$contact = array();
				}
				return $contact;
			case 'website':
				return $request->getBaseUrl();
			case 'logo':
				return '';
			default:
				return;
		}
	}

}


