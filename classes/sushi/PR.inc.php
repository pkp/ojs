<?php

/**
* @file classes/sushi/PR.inc.php
*
* Copyright (c) 2022 Simon Fraser University
* Copyright (c) 2022 John Willinsky
* Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
*
* @class PR
* @ingroup sushi
*
* @brief COUNTER R5 SUSHI Platform Master Report (PR).
*
*/

namespace APP\sushi;

use APP\core\Services;
use PKP\statistics\PKPStatisticsHelper;
use PKP\sushi\CounterR5Report;

class PR extends CounterR5Report
{
    /** The R5 data type the report is about. */
    public const DATA_TYPE = 'Journal';

    /**
     * Get report name defined by COUNTER.
     */
    public function getName(): string
    {
        return 'Platform Master Report';
    }

    /**
     * Get report ID defined by COUNTER.
     */
    public function getID(): string
    {
        return 'PR';
    }

    /**
     * Get report description.
     */
    public function getDescription(): string
    {
        return __('sushi.reports.pr.description');
    }

    /**
     * Get API path defined by COUNTER for this report.
     */
    public function getAPIPath(): string
    {
        return 'reports/pr';
    }

    /**
     * Get request parameters supported by this report.
     */
    public function getSupportedParams(): array
    {
        return [
            'customer_id',
            'begin_date',
            'end_date',
            'platform',
            'metric_type',
            'data_type',
            'access_method',
            'attributes_to_show',
            'granularity'
        ];
    }

    /**
     * Get filters supported by this report.
     */
    public function getSupportedFilters(): array
    {
        return [
            [
                'name' => 'Data_Type',
                'supportedValues' => [self::DATA_TYPE],
                'param' => 'data_type'
            ],
            [
                'name' => 'Metric_Type',
                'supportedValues' => ['Total_Item_Investigations', 'Unique_Item_Investigations', 'Total_Item_Requests', 'Unique_Item_Requests'],
                'param' => 'metric_type'
            ],
            [
                'name' => 'Access_Method',
                'supportedValues' => [self::ACCESS_METHOD],
                'param' => 'access_method'
            ],
        ];
    }

    /**
     * Get attributes supported by this report.
     *
     * The attributes will be displayed and they define what the metrics will be aggregated by.
     * Data_Type and Access_Method are currently always the same for this report,
     * so they will only be displayed and not considered for metrics aggregation.
     * The only attribute considered for metrics aggregation is granularity=Month.
     */
    public function getSupportedAttributes(): array
    {
        return [
            [
                'name' => 'Attributes_To_Show',
                'supportedValues' => ['Data_Type', 'Access_Method'],
                'param' => 'attributes_to_show'
            ],
            [
                'name' => 'granularity',
                'supportedValues' => ['Month', 'Totals'],
                'param' => 'granularity'
            ],
        ];
    }

    /**
     * Get report items
     */
    public function getReportItems(): array
    {
        $params['contextIds'] = [$this->context->getId()];
        $params['institutionId'] = $this->customerId;
        $params['dateStart'] = $this->beginDate;
        $params['dateEnd'] = $this->endDate;
        // do not consider metric_type filter now, but for display

        $statsService = Services::get('sushiStats');
        $metricsQB = $statsService->getQueryBuilder($params);
        $groupBy = [];
        // consider granularity=Month to group the metrics by month
        if ($this->granularity == 'Month') {
            $groupBy = [PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH];
            $orderBy[] = [PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH => 'asc'];
        }
        $metricsQB = $metricsQB->getSum($groupBy);
        // if set, consider results ordering by month
        foreach ($orderBy as $orderByValue) {
            foreach ($orderByValue as $column => $direction) {
                $metricsQB = $metricsQB->orderBy($column, $direction);
            }
        }
        $results = $metricsQB->get();
        if (!$results->count()) {
            $this->addWarning([
                'Code' => 3030,
                'Severity' => 'Error',
                'Message' => 'No Usage Available for Requested Dates',
                'Data' => __('sushi.exception.3030', ['beginDate' => $this->beginDate, 'endDate' => $this->endDate])
            ]);
        }

        // There is only one platform, so there will be only one report item
        $item['Platform'] = $this->platformName;

        foreach ($this->attributesToShow as $attributeToShow) {
            if ($attributeToShow == 'Data_Type') {
                $item['Data_Type'] = self::DATA_TYPE;
            } elseif ($attributeToShow == 'Access_Method') {
                $item['Access_Method'] = self::ACCESS_METHOD;
            }
        }

        $performances = [];
        foreach ($results as $result) {
            // if granularity=Month, the results will contain metrics for each month
            // else the results will only contain the summarized metrics for the whole period
            if (isset($result->month)) {
                $periodBeginDate = date_format(date_create($result->month . '01'), 'Y-m-01');
                $periodEndDate = date_format(date_create($result->month . '01'), 'Y-m-t');
            } else {
                $periodBeginDate = date_format(date_create($this->beginDate), 'Y-m-01');
                $periodEndDate = date_format(date_create($this->endDate), 'Y-m-t');
            }
            $periodMetrics['Period'] = [
                'Begin_Date' => $periodBeginDate,
                'End_Date' => $periodEndDate,
            ];

            $instances = [];
            $metrics['Total_Item_Investigations'] = $result->metric_investigations;
            $metrics['Unique_Item_Investigations'] = $result->metric_investigations_unique;
            $metrics['Total_Item_Requests'] = $result->metric_requests;
            $metrics['Unique_Item_Requests'] = $result->metric_requests_unique;
            // filter here by requested metric types
            foreach ($this->metricTypes as $metricType) {
                if ($metrics[$metricType] > 0) {
                    $instances[] = [
                        'Metric_Type' => $metricType,
                        'Count' => (int) $metrics[$metricType]
                    ];
                }
            }
            $periodMetrics['Instance'] = $instances;
            $performances[] = $periodMetrics;
        }
        $item['Performance'] = $performances;
        $items = [$item];
        return $items;
    }
}
