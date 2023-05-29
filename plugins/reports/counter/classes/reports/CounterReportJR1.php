<?php

/**
 * @file plugins/reports/counter/classes/reports/CounterReportJR1.php
 *
 * Copyright (c) 2014 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * @class CounterReportJR1
 *
 * @brief Journal Report 1
 */

namespace APP\plugins\reports\counter\classes\reports;

use APP\core\Application;
use APP\core\Services;
use APP\journal\JournalDAO;
use APP\plugins\reports\counter\classes\CounterReport;
use APP\statistics\StatisticsHelper;
use COUNTER\Identifier;
use COUNTER\PerformanceCounter;
use COUNTER\ReportItems;
use Exception;
use PKP\db\DAORegistry;
use PKP\db\DBResultRange;

class CounterReportJR1 extends CounterReport
{
    /**
     * Get the report title
     *
     * @return $string
     */
    public function getTitle()
    {
        return __('plugins.reports.counter.jr1.title');
    }

    /**
     * Convert an OJS metrics request to COUNTER ReportItems
     *
     * @param string|array $columns column (aggregation level) selection
     * @param array $filters report-level filter selection
     * @param array $orderBy order criteria
     * @param null|DBResultRange $range paging specification
     *
     * @see ReportPlugin::getMetrics for more details
     *
     * @return array COUNTER\ReportItem array
     */
    public function getReportItems($columns = [], $filters = [], $orderBy = [], $range = null)
    {
        // Columns are fixed for this report
        $defaultColumns = [StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID, StatisticsHelper::STATISTICS_DIMENSION_FILE_TYPE, StatisticsHelper::STATISTICS_DIMENSION_MONTH];
        if ($columns && array_diff($columns, $defaultColumns)) {
            $this->setError(new Exception(__('plugins.reports.counter.exception.column'), COUNTER_EXCEPTION_WARNING | COUNTER_EXCEPTION_BAD_COLUMNS));
        }
        // Check filters for correct context(s)
        $validFilters = $this->filterForContext($filters);
        // Filters defaults to last month, but can be provided by month or by day (which is defined in the $columns)
        if (!isset($filters['dateStart']) && !isset($filters['dateEnd'])) {
            $validFilters['dateStart'] = date_format(date_create('first day of previous month'), 'Ymd');
            $validFilters['dateEnd'] = date_format(date_create('last day of previous month'), 'Ymd');
        } elseif (!isset($filters['dateStart']) || !isset($filters['dateEnd'])) {
            // either start or end date not set
            $this->setError(new Exception(__('plugins.reports.counter.exception.filter'), COUNTER_EXCEPTION_WARNING | COUNTER_EXCEPTION_BAD_FILTERS));
        } elseif (isset($filters['dateStart']) && isset($filters['dateEnd'])) {
            $validFilters['dateStart'] = $filters['dateStart'];
            $validFilters['dateEnd'] = $filters['dateEnd'];
            unset($filters['dateStart']);
            unset($filters['dateEnd']);
        }
        if (!isset($filters['assocTypes'])) {
            $validFilters['assocTypes'] = [Application::ASSOC_TYPE_SUBMISSION_FILE];
            unset($filters['assocTypes']);
        } elseif ($filters['assocTypes'] != Application::ASSOC_TYPE_SUBMISSION_FILE) {
            $this->setError(new Exception(__('plugins.reports.counter.exception.filter'), COUNTER_EXCEPTION_ERROR | COUNTER_EXCEPTION_BAD_FILTERS));
        }
        // Any further filters aren't recognized (at this time, at least)
        if (array_keys($filters)) {
            $this->setError(new Exception(__('plugins.reports.counter.exception.filter'), COUNTER_EXCEPTION_WARNING | COUNTER_EXCEPTION_BAD_FILTERS));
        }
        // TODO: range
        $results = Services::get('publicationStats')->getQueryBuilder($validFilters)
            ->getSum($defaultColumns)
            // Ordering must be by Journal (ReportItem), and by Month (ItemPerformance) for JR1
            ->orderBy(StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID, StatisticsHelper::STATISTICS_ORDER_DESC)
            ->orderBy(StatisticsHelper::STATISTICS_DIMENSION_MONTH, StatisticsHelper::STATISTICS_ORDER_ASC)
            ->get()->toArray();
        $reportItems = [];
        if ($results) {
            // We'll create a new Report Item with these Metrics on a journal change
            $metrics = [];
            // We'll create a new Metric with these Performance Counters on a period change
            $counters = [];
            $lastPeriod = 0;
            $lastJournal = 0;
            foreach ($results as $rs) {
                // Identify the type of request
                $metricTypeKey = $this->getKeyForFiletype($rs->{StatisticsHelper::STATISTICS_DIMENSION_FILE_TYPE});
                // Period changes or greater trigger a new ItemPerformace metric
                if ($lastPeriod != $rs->{StatisticsHelper::STATISTICS_DIMENSION_MONTH} || $lastJournal != $rs->{StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID}) {
                    if ($lastPeriod != 0) {
                        $metrics[] = $this->createMetricByMonth($lastPeriod, $counters);
                        $counters = [];
                    }
                }
                $lastPeriod = $rs->{StatisticsHelper::STATISTICS_DIMENSION_MONTH};
                $counters[] = new PerformanceCounter($metricTypeKey, $rs->{StatisticsHelper::STATISTICS_METRIC});
                // Journal changes trigger a new ReportItem
                if ($lastJournal != $rs->{StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID}) {
                    if ($lastJournal != 0 && $metrics) {
                        $item = $this->_createReportItem($lastJournal, $metrics);
                        if ($item) {
                            $reportItems[] = $item;
                        } else {
                            $this->setError(new Exception(__('plugins.reports.counter.exception.partialData'), COUNTER_EXCEPTION_WARNING | COUNTER_EXCEPTION_PARTIAL_DATA));
                        }
                        $metrics = [];
                    }
                }
                $lastJournal = $rs->{StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID};
            }
            // Capture the last unprocessed ItemPerformance and ReportItem entries, if applicable
            if ($counters) {
                $metrics[] = $this->createMetricByMonth($lastPeriod, $counters);
            }
            if ($metrics) {
                $item = $this->_createReportItem($lastJournal, $metrics);
                if ($item) {
                    $reportItems[] = $item;
                } else {
                    $this->setError(new Exception(__('plugins.reports.counter.exception.partialData'), COUNTER_EXCEPTION_WARNING | COUNTER_EXCEPTION_PARTIAL_DATA));
                }
            }
        } else {
            $this->setError(new Exception(__('plugins.reports.counter.exception.noData'), COUNTER_EXCEPTION_ERROR | COUNTER_EXCEPTION_NO_DATA));
        }
        return $reportItems;
    }

    /**
     * Given a journalId and an array of COUNTER\Metrics, return a COUNTER\ReportItems
     *
     * @param int $journalId
     * @param array $metrics COUNTER\Metric array
     *
     * @return mixed COUNTER\ReportItems or false
     */
    private function _createReportItem($journalId, $metrics)
    {
        $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */
        $journal = $journalDao->getById($journalId);
        if (!$journal) {
            return false;
        }
        $journalName = $journal->getLocalizedName();
        $journalPubIds = [];
        foreach (['print', 'online'] as $issnType) {
            if ($journal->getData($issnType . 'Issn')) {
                try {
                    $journalPubIds[] = new Identifier(ucfirst($issnType) . '_ISSN', $journal->getData($issnType . 'Issn'));
                } catch (Exception $ex) {
                    // Just ignore it
                }
            }
        }
        $journalPubIds[] = new Identifier(COUNTER_LITERAL_PROPRIETARY, $journal->getPath());
        $reportItem = [];
        try {
            $reportItem = new ReportItems(__('common.software'), $journalName, COUNTER_LITERAL_JOURNAL, $metrics, null, $journalPubIds);
        } catch (Exception $e) {
            $this->setError($e, COUNTER_EXCEPTION_ERROR | COUNTER_EXCEPTION_INTERNAL);
        }
        return $reportItem;
    }
}
