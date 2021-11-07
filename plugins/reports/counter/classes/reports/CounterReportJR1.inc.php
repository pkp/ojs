<?php

/**
 * @file plugins/reports/counter/classes/reports/CounterReportJR1.inc.php
 *
 * Copyright (c) 2014 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * @class CounterReportJR1
 * @ingroup plugins_reports_counter
 *
 * @brief Journal Report 1
 */

use PKP\statistics\PKPStatisticsHelper;

import('plugins.reports.counter.classes.CounterReport');

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
     * @param string|array $columns column (aggregation level) selection
     * @param array $filters report-level filter selection
     * @param array $orderBy order criteria
     * @param null|DBResultRange $range paging specification
     * @see ReportPlugin::getMetrics for more details
     * @return array COUNTER\ReportItem array
     */
    public function getReportItems($columns = [], $filters = [], $orderBy = [], $range = null)
    {
        $metricsDao = DAORegistry::getDAO('MetricsDAO'); /** @var MetricsDAO $metricsDao */

        // Columns are fixed for this report
        $defaultColumns = [PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH, PKPStatisticsHelper::STATISTICS_DIMENSION_FILE_TYPE, PKPStatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID];
        if ($columns && array_diff($columns, $defaultColumns)) {
            $this->setError(new Exception(__('plugins.reports.counter.exception.column'), COUNTER_EXCEPTION_WARNING | COUNTER_EXCEPTION_BAD_COLUMNS));
        }
        // Check filters for correct context(s)
        $validFilters = $this->filterForContext($filters);
        // Filters defaults to last month, but can be provided by month or by day
        if (!isset($filters[PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH]) && !isset($filters[PKPStatisticsHelper::STATISTICS_DIMENSION_DAY])) {
            $validFilters[PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH] = [
                'from' => date_format(date_create('first day of previous month'), 'Ymd'),
                'to' => date_format(date_create('last day of previous month'), 'Ymd')
            ];
        } elseif (isset($filters[PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH])) {
            $validFilters[PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH] = $filters[PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH];
            unset($filters[PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH]);
        } elseif (isset($filters[PKPStatisticsHelper::STATISTICS_DIMENSION_DAY])) {
            $validFilters[PKPStatisticsHelper::STATISTICS_DIMENSION_DAY] = $filters[PKPStatisticsHelper::STATISTICS_DIMENSION_DAY];
            unset($filters[PKPStatisticsHelper::STATISTICS_DIMENSION_DAY]);
        }
        if (!isset($filters[PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE])) {
            $validFilters[PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE] = ASSOC_TYPE_SUBMISSION_FILE;
            unset($filters[PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE]);
        } elseif ($filters[PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE] != ASSOC_TYPE_SUBMISSION_FILE) {
            $this->setError(new Exception(__('plugins.reports.counter.exception.filter'), COUNTER_EXCEPTION_ERROR | COUNTER_EXCEPTION_BAD_FILTERS));
        }
        // Any further filters aren't recognized (at this time, at least)
        if (array_keys($filters)) {
            $this->setError(new Exception(__('plugins.reports.counter.exception.filter'), COUNTER_EXCEPTION_WARNING | COUNTER_EXCEPTION_BAD_FILTERS));
        }
        // Metric type is ojs::counter
        $metricType = METRIC_TYPE_COUNTER;
        // Ordering must be by Journal (ReportItem), and by Month (ItemPerformance) for JR1
        $validOrder = [PKPStatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID => PKPStatisticsHelper::STATISTICS_ORDER_DESC, PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH => PKPStatisticsHelper::STATISTICS_ORDER_ASC];
        // TODO: range
        $results = $metricsDao->getMetrics($metricType, $defaultColumns, $validFilters, $validOrder);
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
                $metricTypeKey = $this->getKeyForFiletype($rs[PKPStatisticsHelper::STATISTICS_DIMENSION_FILE_TYPE]);
                // Period changes or greater trigger a new ItemPerformace metric
                if ($lastPeriod != $rs[PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH] || $lastJournal != $rs[PKPStatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID]) {
                    if ($lastPeriod != 0) {
                        $metrics[] = $this->createMetricByMonth($lastPeriod, $counters);
                        $counters = [];
                    }
                }
                $lastPeriod = $rs[PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH];
                $counters[] = new COUNTER\PerformanceCounter($metricTypeKey, $rs[PKPStatisticsHelper::STATISTICS_METRIC]);
                // Journal changes trigger a new ReportItem
                if ($lastJournal != $rs[PKPStatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID]) {
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
                $lastJournal = $rs[PKPStatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID];
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
                    $journalPubIds[] = new COUNTER\Identifier(ucfirst($issnType) . '_ISSN', $journal->getData($issnType . 'Issn'));
                } catch (Exception $ex) {
                    // Just ignore it
                }
            }
        }
        $journalPubIds[] = new COUNTER\Identifier(COUNTER_LITERAL_PROPRIETARY, $journal->getPath());
        $reportItem = [];
        try {
            $reportItem = new COUNTER\ReportItems(__('common.software'), $journalName, COUNTER_LITERAL_JOURNAL, $metrics, null, $journalPubIds);
        } catch (Exception $e) {
            $this->setError($e, COUNTER_EXCEPTION_ERROR | COUNTER_EXCEPTION_INTERNAL);
        }
        return $reportItem;
    }
}
