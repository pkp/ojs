<?php

/**
* @file classes/sushi/PR.inc.php
*
* Copyright (c) 2013-2021 Simon Fraser University
* Copyright (c) 2003-2021 John Willinsky
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

class PR
{
    /** ID of the context the report is for. */
    public int $contextId;

    // Data used for the report header:
    /** Platform name, either press name or name defined in site settings */
    public string $platformName;
    /** Platform ID is used as namespace for item proprietary IDs. */
    public string $platformId;
    /** The requested customer ID is the DB institution_id */
    public int $customerId;
    /** Institution name */
    public string $institutionName;
    /** Institution ID. Currently we only provide proprietary and ROR. */
    public ?array $institutionId;

    // Report filters values:
    /** The following filters are always the same in our case. */
    public const DATA_TYPE = 'Journal';
    public const ACCESS_METHOD = 'Regular';
    /** The requested period, begin and end date, the report should be created for. */
    public string $beginDate;
    public string $endDate;
    /** Requested Year of Publication (YOP) */
    public array $yearsOfPublication = [];
    /** Requested metric types */
    public array $metricTypes = ['Total_Item_Investigations', 'Unique_Item_Investigations', 'Total_Item_Requests', 'Unique_Item_Requests'];

    /** Warnings that will be displayed in the report header. */
    public array $exceptions = [];

    /** List of all filters and attributes requested and applied that will be displayed in the report header.  */
    protected array $filters = [];
    protected array $attributes = [];

    /** Attributes to show in the report and the metrics will be grouped by. */
    protected array $attributesToShow = [];
    protected string $granularity = 'Month';

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
     * Get report release.
     */
    public function getRelease(): string
    {
        return '5';
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
        return ['customer_id', 'begin_date', 'end_date', 'platform', 'metric_type', 'data_type', 'access_method', 'attributes_to_show', 'granularity'];
    }

    /**
     * Get filters supported by this report.
     */
    public function getSupportedFilters(): array
    {
        return [
            ['name' => 'Data_Type', 'supportedValues' => ['Journal'], 'param' => 'data_type'],
            ['name' => 'Metric_Type', 'supportedValues' => ['Total_Item_Investigations', 'Unique_Item_Investigations', 'Total_Item_Requests', 'Unique_Item_Requests'], 'param' => 'metric_type'],
            ['name' => 'Access_Method', 'supportedValues' => ['Regular'], 'param' => 'access_method'],
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
            ['name' => 'Attributes_To_Show', 'supportedValues' => ['Data_Type', 'Access_Method'], 'param' => 'attributes_to_show'],
            ['name' => 'granularity', 'supportedValues' => ['Month', 'Totals'], 'param' => 'granularity'],
        ];
    }

    /**
     * Get used filters that will be displayed in the report header.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Get used attributes that will be displayed in the report header.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set filters based on the requested parameters.
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
        foreach ($filters as $filter) {
            switch ($filter['Name']) {
                case 'Begin_Date':
                    $this->beginDate = $filter['Value'];
                    break;
                case 'End_Date':
                    $this->endDate = $filter['Value'];
                    break;
                case 'Metric_Type':
                    $this->metricTypes = explode('|', $filter['Value']);
                    break;
            }
        }
    }

    /**
     * Set attributes based on the requested parameters.
     *
     * The attributes will be displayed and they define what the metrics will be aggregated by.
     * Data_Type and Access_Method are currently always the same for this report,
     * so they will only be displayed and not considered for metrics aggregation.
     * The only attribute considered for metrics aggregation is granularity=Month.
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
        foreach ($attributes as $attribute) {
            switch ($attribute['Name']) {
                case 'Attributes_To_Show':
                    $this->attributesToShow = explode('|', $attribute['Value']);
                    break;
                case 'granularity':
                    $this->granularity = $attribute['Value'];
                    break;
            }
        }
    }

    /**
     * Get report items
     */
    public function getReportItems(): array
    {
        // prepare stats service parameters
        $allowedParams['contextIds'] = $this->contextId;
        $allowedParams['institutionId'] = $this->customerId;
        $allowedParams['dateStart'] = $this->beginDate;
        $allowedParams['dateEnd'] = $this->endDate;
        // do not consider metric_type filter now, but for display

        $statsService = Services::get('sushiStats');
        $metricsQB = $statsService->getQueryBuilder($allowedParams);
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
        // get metrics results as array
        $results = $metricsQB->get()->toArray();
        if (empty($results)) {
            $this->exceptions[] = [
                'Code' => 3030,
                'Severity' => 'Error',
                'Message' => 'No Usage Available for Requested Dates',
                'Data' => __('sushi.exception.3030', ['beginDate' => $this->beginDate, 'endDate' => $this->endDate])
            ];
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

        $perfomances = [];
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
            $perfomances[] = $periodMetrics;
        }
        $item['Performance'] = $perfomances;
        $items = [$item];
        return $items;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\sushi\PR', '\PR');
}
