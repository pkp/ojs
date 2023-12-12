<?php

/**
 * @file classes/sushi/PR.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PR
 *
 * @ingroup sushi
 *
 * @brief COUNTER R5 SUSHI Platform Master Report (PR).
 *
 */

namespace APP\sushi;

use APP\core\Services;
use Illuminate\Support\Collection;
use PKP\components\forms\FieldOptions;
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
            'granularity',
            '_', // for ajax requests
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

    /** Get DB query results for the report */
    protected function getQueryResults(): Collection
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
        return $results;
    }

    /** Get report items */
    public function getReportItems(): array
    {
        $results = $this->getQueryResults();

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

    /** Get TSV report column names */
    public function getTSVColumnNames(): array
    {
        $columnRow = ['Platform'];
        if (in_array('Data_Type', $this->attributesToShow)) {
            array_push($columnRow, 'Data_Type');
        }
        if (in_array('Access_Method', $this->attributesToShow)) {
            array_push($columnRow, 'Access_Method');
        }
        array_push($columnRow, 'Metric_Type', 'Reporting_Period_Total');
        if ($this->granularity == 'Month') {
            $period = $this->getMonthlyDatePeriod();
            foreach ($period as $dt) {
                array_push($columnRow, $dt->format('M-Y'));
            }
        }
        return [$columnRow];
    }

    /** Get TSV report rows */
    public function getTSVReportItems(): array
    {
        $results = $this->getQueryResults();

        // get total numbers for every metric type
        $metricsTotal['Total_Item_Investigations'] = $results->pluck('metric_investigations')->sum();
        $metricsTotal['Unique_Item_Investigations'] = $results->pluck('metric_investigations_unique')->sum();
        $metricsTotal['Total_Item_Requests'] = $results->pluck('metric_requests')->sum();
        $metricsTotal['Unique_Item_Requests'] = $results->pluck('metric_requests_unique')->sum();

        $resultRows = [];
        // filter here by requested metric types
        foreach ($this->metricTypes as $metricType) {
            // if the total numbers for the given metric type > 0
            if ($metricsTotal[$metricType] > 0) {
                // construct the result row
                $resultRow = [];
                array_push($resultRow, $this->platformName); // Platform
                if (in_array('Data_Type', $this->attributesToShow)) {
                    array_push($resultRow, self::DATA_TYPE); // Data_Type
                }
                if (in_array('Access_Method', $this->attributesToShow)) {
                    array_push($resultRow, self::ACCESS_METHOD); // Access_Method
                }
                array_push($resultRow, $metricType); // Metric_Type
                array_push($resultRow, $metricsTotal[$metricType]); // Reporting_Period_Total
                if ($this->granularity == 'Month') { // metrics for each month in the given period
                    $period = $this->getMonthlyDatePeriod();
                    foreach ($period as $dt) {
                        $month = $dt->format('Ym');
                        $result = $results->firstWhere('month', '=', $month);
                        if ($result === null) {
                            array_push($resultRow, '0');
                        } else {
                            $metrics['Total_Item_Investigations'] = $result->metric_investigations;
                            $metrics['Unique_Item_Investigations'] = $result->metric_investigations_unique;
                            $metrics['Total_Item_Requests'] = $result->metric_requests;
                            $metrics['Unique_Item_Requests'] = $result->metric_requests_unique;
                            array_push($resultRow, $metrics[$metricType]);
                        }
                    }
                }
                $resultRows[] = $resultRow;
            }
        }

        return $resultRows;
    }

    /** Get report specific form fields */
    public static function getReportSettingsFormFields(): array
    {
        $formFields = parent::getCommonReportSettingsFormFields();

        $metricTypes = ['Total_Item_Investigations', 'Unique_Item_Investigations', 'Total_Item_Requests', 'Unique_Item_Requests'];
        $metricTypeOptions = [];
        foreach ($metricTypes as $metricType) {
            $metricTypeOptions[] = ['value' => $metricType, 'label' => $metricType];
        }
        $formFields[] = new FieldOptions('metric_type', [
            'label' => __('manager.statistics.counterR5Report.settings.metricType'),
            'options' => $metricTypeOptions,
            'value' => $metricTypes,
            'groupId' => 'default',
        ]);

        $attributesToShow = ['Data_Type', 'Access_Method'];
        $attributesToShowOptions = [];
        foreach ($attributesToShow as $attributeToShow) {
            $attributesToShowOptions[] = ['value' => $attributeToShow, 'label' => $attributeToShow];
        }
        $formFields[] = new FieldOptions('attributes_to_show', [
            'label' => __('manager.statistics.counterR5Report.settings.attributesToShow'),
            'options' => $attributesToShowOptions,
            'value' => [],
            'groupId' => 'default',
        ]);

        $formFields[] = new FieldOptions('granularity', [
            'label' => __('manager.statistics.counterR5Report.settings.excludeMonthlyDetails'),
            'options' => [
                ['value' => true, 'label' => __('manager.statistics.counterR5Report.settings.excludeMonthlyDetails')],
            ],
            'value' => false,
            'groupId' => 'default',
        ]);

        return $formFields;
    }
}
