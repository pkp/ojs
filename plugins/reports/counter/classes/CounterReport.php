<?php

/**
 * @file plugins/reports/counter/classes/CounterReport.php
 *
 * Copyright (c) 2014 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * @class CounterReport
 *
 * @ingroup plugins_reports_counter
 *
 * @brief A COUNTER report, base class
 */

namespace APP\plugins\reports\counter\classes;

require_once(dirname(__FILE__, 2) . '/classes/COUNTER/COUNTER.php');

use APP\core\Application;
use APP\statistics\StatisticsHelper;
use COUNTER\Contact;
use COUNTER\Customer;
use COUNTER\DateRange;
use COUNTER\Metric;
use COUNTER\Report;
use COUNTER\Reports;
use COUNTER\Vendor;
use DateTime;
use Exception;
use PKP\core\PKPString;
use PKP\db\DBResultRange;

define('COUNTER_EXCEPTION_WARNING', 0);
define('COUNTER_EXCEPTION_ERROR', 1);
define('COUNTER_EXCEPTION_PARTIAL_DATA', 4);
define('COUNTER_EXCEPTION_NO_DATA', 8);
define('COUNTER_EXCEPTION_BAD_COLUMNS', 16);
define('COUNTER_EXCEPTION_BAD_FILTERS', 32);
define('COUNTER_EXCEPTION_BAD_ORDERBY', 64);
define('COUNTER_EXCEPTION_BAD_RANGE', 128);
define('COUNTER_EXCEPTION_INTERNAL', 256);

// COUNTER as of yet is not internationalized and requires English constants
define('COUNTER_LITERAL_ARTICLE', 'Article');
define('COUNTER_LITERAL_JOURNAL', 'Journal');
define('COUNTER_LITERAL_PROPRIETARY', 'Proprietary');

class CounterReport
{
    public const COUNTER_CLASS_PREFIX = 'CounterReport';
    /**
     * @var string $_release A COUNTER release number
     */
    public $_release;

    /**
     * @var array $_errors An array of accumulated Exceptions
     */
    public $_errors;

    /**
     * Constructor
     *
     * @param string $release
     */
    public function __construct($release)
    {
        $this->_release = $release;
    }


    /**
     * Get the COUNTER Release
     *
     * @return $string
     */
    public function getRelease()
    {
        return $this->_release;
    }

    /**
     * Get the report code
     *
     * @return $string
     */
    public function getCode()
    {
        return substr(get_class($this), strlen(self::COUNTER_CLASS_PREFIX));
    }

    /**
     * Get the COUNTER metric type for an Statistics file type
     *
     * @param string $filetype
     *
     * @return string
     */
    public function getKeyForFiletype($filetype)
    {
        switch ($filetype) {
            case StatisticsHelper::STATISTICS_FILE_TYPE_HTML:
                $metricTypeKey = 'ft_html';
                break;
            case StatisticsHelper::STATISTICS_FILE_TYPE_PDF:
                $metricTypeKey = 'ft_pdf';
                break;
            case StatisticsHelper::STATISTICS_FILE_TYPE_OTHER:
            default:
                $metricTypeKey = 'other';
        }
        return $metricTypeKey;
    }

    /**
     * Abstract method must be implemented in the child class
     * Get the report title
     *
     * @return $string
     */
    public function getTitle()
    {
        assert(false);
    }

    /**
     * Convert an OJS metrics request to COUNTER ReportItems
     * Abstract method must be implemented by subclass
     *
     * @param string|array $columns column (aggregation level) selection
     * @param array $filters report-level filter selection
     * @param array $orderBy order criteria
     * @param null|DBResultRange $range paging specification
     *
     * @see ReportPlugin::getMetrics for more details on parameters
     *
     * @return array ReportItem array
     */
    public function getReportItems($columns = [], $filters = [], $orderBy = [], $range = null)
    {
        assert(false);
    }

    /**
     * Get an array of errors
     *
     * @return array of Exceptions
     */
    public function getErrors()
    {
        return $this->_errors ? $this->_errors : [];
    }

    /**
     * Set an errors condition; Proper Exception handling is deferred until the OJS 3.0 Release
     *
     * @param Exception $error
     */
    public function setError($error)
    {
        if (!$this->_errors) {
            $this->_errors = [];
        }
        array_push($this->_errors, $error);
    }

    /**
     * Ensure that the $filters do not exceed the current Context
     *
     * @param array() $filters
     *
     * @return array()
     */
    protected function filterForContext($filters)
    {
        $request = Application::get()->getRequest();
        $journal = $request->getContext();
        $journalId = $journal ? $journal->getId() : '';
        // If the request context is at the journal level, the dimension context id must be that same journal id
        if ($journalId) {
            if (isset($filters['contextIds']) && $filters['contextIds'] != $journalId) {
                $this->setError(new Exception(__('plugins.reports.counter.generic.exception.filter'), COUNTER_EXCEPTION_WARNING | COUNTER_EXCEPTION_BAD_FILTERS));
            }
            $filters['contextIds'] = [$journalId];
        }
        return $filters;
    }

    /**
     * Given a Year-Month period and array of PerformanceCounters, create a Metric
     *
     * @param string $period Date in the format Y-m-01 for month
     * @param array $counters PerformanceCounter array
     *
     * @return Metric
     */
    protected function createMetricByMonth($period, $counters)
    {
        $metric = [];
        try {
            $metric = new Metric(
                // Date range for JR1 is beginning of the month to end of the month
                new DateRange(
                    DateTime::createFromFormat('Y-m-d H:i:s', $period . ' 00:00:00'),
                    DateTime::createFromFormat('Y-m-d H:i:s', substr($period, 0, 8) . date('t', strtotime($period)) . ' 23:59:59')
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
     *
     * @param array $reportItems ReportItem
     *
     * @return ?string xml
     */
    public function createXML($reportItems)
    {
        $errors = $this->getErrors();
        $fatal = false;
        foreach ($errors as $error) {
            if ($error->getCode() & COUNTER_EXCEPTION_ERROR) {
                $fatal = true;
            }
        }
        if (!$fatal) {
            try {
                $report = new Reports(
                    new Report(
                        PKPString::generateUUID(),
                        $this->getRelease(),
                        $this->getCode(),
                        $this->getTitle(),
                        new Customer(
                            '0', // customer id is unused
                            $reportItems,
                            __('plugins.reports.counter.allCustomers')
                        ),
                        new Vendor(
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
     *
     * @return $string
     */
    public function getVendorId()
    {
        return $this->_getVendorComponent('id');
    }

    /**
     * Get the Vendor Name
     *
     * @return $string
     */
    public function getVendorName()
    {
        return (string) $this->_getVendorComponent('name');
    }

    /**
     * Get the Vendor Contacts
     *
     * @return array() Contact
     */
    public function getVendorContacts()
    {
        return $this->_getVendorComponent('contacts');
    }

    /**
     * Get the Vendor Website URL
     *
     * @return string
     */
    public function getVendorWebsiteUrl()
    {
        return $this->_getVendorComponent('website');
    }

    /**
     * Get the Vendor Contacts
     *
     * @return array Contact
     */
    public function getVendorLogoUrl()
    {
        return $this->_getVendorComponent('logo');
    }

    /**
     * Get the Vendor Component by key
     *
     * @param string $key
     */
    public function _getVendorComponent($key)
    {
        $request = Application::get()->getRequest();
        $site = $request->getSite();
        $context = $request->getContext();
        $contextDao = Application::getContextDAO();
        $availableContexts = $contextDao->getAvailable();
        [$firstContext, $secondContext] = [$availableContexts->next(), $availableContexts->next()];
        switch ($key) {
            case 'name':
                if ($secondContext) { // Multiple contexts
                    $name = $site->getLocalizedTitle();
                } else {
                    $name = $context->getData('publisherInstitution');
                    if (empty($name)) {
                        $name = $context->getLocalizedName();
                    }
                }
                return $name;
            case 'id':
                return $request->getBaseUrl();
            case 'contacts':
                try {
                    if ($secondContext) { // Multiple contexts
                        $contactName = $site->getLocalizedContactName();
                        $contactEmail = $site->getLocalizedContactEmail();
                    } else {
                        $contactName = $context->getContactName();
                        $contactEmail = $context->getContactEmail();
                    }
                    $contact = new Contact($contactName, $contactEmail);
                } catch (Exception $e) {
                    $this->setError($e);
                    $contact = [];
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
