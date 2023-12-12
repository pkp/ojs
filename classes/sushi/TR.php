<?php

/**
 * @file classes/sushi/TR.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TR
 *
 * @ingroup sushi
 *
 * @brief COUNTER R5 SUSHI Title Master Report (TR).
 *
 */

namespace APP\sushi;

use APP\core\Services;
use Illuminate\Support\Collection;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldText;
use PKP\statistics\PKPStatisticsHelper;
use PKP\sushi\CounterR5Report;

class TR extends CounterR5Report
{
    /** Section type */
    public const SECTION_TYPE = 'Article';

    /** The R5 data type the report is about. */
    public const DATA_TYPE = 'Journal';

    /**
     * Get report name defined by COUNTER.
     */
    public function getName(): string
    {
        return 'Title Master Report';
    }

    /**
     * Get report ID defined by COUNTER.
     */
    public function getID(): string
    {
        return 'TR';
    }

    /**
     * Get report description.
     */
    public function getDescription(): string
    {
        return __('sushi.reports.tr.description');
    }

    /**
     * Get API path defined by COUNTER for this report.
     */
    public function getAPIPath(): string
    {
        return 'reports/tr';
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
            'item_id',
            'metric_type',
            'data_type',
            'section_type',
            'yop',
            'access_type',
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
                'name' => 'YOP',
                'supportedValues' => [],
                'param' => 'yop'
            ],
            [
                'name' => 'Item_Id',
                'supportedValues' => [$this->context->getId()],
                'param' => 'item_id'
            ],
            [
                'name' => 'Access_Type',
                'supportedValues' => [self::ACCESS_TYPE],
                'param' => 'access_type'
            ],
            [
                'name' => 'Section_Type',
                'supportedValues' => [self::SECTION_TYPE],
                'param' => 'section_type'
            ],
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
     * Data_Type, Access_Method, Section_Type, and Access_Type are currently always the same for this report,
     * so they will only be displayed and not considered for metrics aggregation.
     * The only attributes considered for metrics aggregation are Attributes_To_Show=YOP and granularity=Month.
     */
    public function getSupportedAttributes(): array
    {
        return [
            [
                'name' => 'Attributes_To_Show',
                'supportedValues' => ['Data_Type', 'Access_Method', 'Section_Type', 'Access_Type', 'YOP'],
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
     * Set filters based on the requested parameters.
     */
    public function setFilters(array $filters): void
    {
        parent::setFilters($filters);
        foreach ($filters as $filter) {
            switch ($filter['Name']) {
                case 'YOP':
                    $this->yearsOfPublication = explode('|', $filter['Value']);
                    break;
            }
        }
    }

    /** Get DB query results for the report */
    protected function getQueryResults(): Collection
    {
        $params['contextIds'] = [$this->context->getId()];
        $params['institutionId'] = $this->customerId;
        $params['dateStart'] = $this->beginDate;
        $params['dateEnd'] = $this->endDate;
        $params['yearsOfPublication'] = $this->yearsOfPublication;
        // do not consider metric_type filter now, but for display

        $statsService = Services::get('sushiStats');
        $metricsQB = $statsService->getQueryBuilder($params);
        // consider attributes to group the metrics by
        $groupBy = $orderBy = [];
        if (in_array('YOP', $this->attributesToShow)) {
            $groupBy[] = 'YOP';
            $orderBy[] = ['YOP' => 'asc'];
        }
        if ($this->granularity == 'Month') {
            $groupBy[] = PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH;
            $orderBy[] = [PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH => 'asc'];
        }
        $metricsQB = $metricsQB->getSum($groupBy);
        // if set, consider results ordering
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

        // If YOP is requested attribute to show,
        // group results by YOP
        $resultsGroupedByYOP = $yearsOfPublication = $items = [];
        if (in_array('YOP', $this->attributesToShow)) {
            $yearsOfPublication = $results->pluck('YOP')->unique();
            $resultsGroupedByYOP = $results->groupBy('YOP');
        }

        // Apply the loop at least once:
        // if there is no grouping by YOP, there will be one item
        // else there will be one item per YOP
        $i = 0;
        do {
            if (isset($yearsOfPublication[$i])) {
                $yearOfPublication = $yearsOfPublication[$i];
                $results = $resultsGroupedByYOP[$yearOfPublication];
            }
            $item = [
                'Title' => $this->context->getName($this->context->getPrimaryLocale()),
                'Platform' => $this->platformName,
                'Publisher' => $this->context->getData('publisherInstitution'),
            ];
            $item['Item_ID'] = [
                ['Type' => 'Proprietary', 'Value' => $this->platformId . ':' . $this->context->getId()]
            ];
            if (null !== $this->context->getData('onlineIssn')) {
                $item['Item_ID'][] = [
                    'Type' => 'Online_ISSN',
                    'Value' => $this->context->getData('onlineIssn'),
                ];
            }
            if (null !== $this->context->getData('printIssn')) {
                $item['Item_ID'][] = [
                    'Type' => 'Print_ISSN',
                    'Value' => $this->context->getData('printIssn'),
                ];
            }

            foreach ($this->attributesToShow as $attributeToShow) {
                if ($attributeToShow == 'Data_Type') {
                    $item['Data_Type'] = self::DATA_TYPE;
                } elseif ($attributeToShow == 'Section_Type') {
                    $item['Section_Type'] = self::SECTION_TYPE;
                } elseif ($attributeToShow == 'Access_Type') {
                    $item['Access_Type'] = self::ACCESS_TYPE;
                } elseif ($attributeToShow == 'Access_Method') {
                    $item['Access_Method'] = self::ACCESS_METHOD;
                } elseif ($attributeToShow == 'YOP') {
                    $item['YOP'] = $yearOfPublication;
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
            $items[] = $item;
            $i++;
        } while ($i < count($yearsOfPublication));

        return $items;
    }

    /** Get TSV report column names */
    public function getTSVColumnNames(): array
    {
        $columnRow = ['Title', 'Publisher', 'Publisher ID', 'Platform', 'DOI', 'Proprietary_ID', 'ISBN', 'Print_ISSN', 'Online_ISSN', 'URI'];

        if (in_array('Data_Type', $this->attributesToShow)) {
            array_push($columnRow, 'Data_Type');
        }
        if (in_array('Section_Type', $this->attributesToShow)) {
            array_push($columnRow, 'Section_Type');
        }
        if (in_array('YOP', $this->attributesToShow)) {
            array_push($columnRow, 'YOP');
        }
        if (in_array('Access_Type', $this->attributesToShow)) {
            array_push($columnRow, 'Access_Type');
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

        // If YOP is requested attribute to show,
        // group results by YOP
        $resultsGroupedByYOP = $yearsOfPublication = $resultRows = [];
        if (in_array('YOP', $this->attributesToShow)) {
            $yearsOfPublication = $results->pluck('YOP')->unique();
            $resultsGroupedByYOP = $results->groupBy('YOP');
        }

        // Apply the loop at least once:
        // if there is no grouping by YOP, there will be one item
        // else there will be one item per YOP
        $i = 0;
        do {
            if (isset($yearsOfPublication[$i])) {
                $yearOfPublication = $yearsOfPublication[$i];
                $results = collect($resultsGroupedByYOP[$yearOfPublication]);
            }

            // filter here by requested metric types
            foreach ($this->metricTypes as $metricType) {
                // if the total numbers for the given metric type > 0
                if ($metricsTotal[$metricType] > 0) {
                    // construct the result row
                    $resultRow = [
                        $this->context->getName($this->context->getPrimaryLocale()), // Title
                        $this->context->getData('publisherInstitution'), // Publisher
                        '', // Publisher ID
                        $this->platformName, // Platform
                        '', // DOI
                        $this->platformId . ':' . $this->context->getId(), // Proprietary_ID
                        '', // ISBN
                        $this->context->getData('printIssn') ?? '', // Print_ISSN
                        $this->context->getData('onlineIssn') ?? '', // Online_ISSN
                        '', // URI
                    ];
                    if (in_array('Data_Type', $this->attributesToShow)) {
                        array_push($resultRow, self::DATA_TYPE); // Data_Type
                    }
                    if (in_array('Section_Type', $this->attributesToShow)) {
                        array_push($resultRow, self::SECTION_TYPE); // Section_Type
                    }
                    if (in_array('YOP', $this->attributesToShow)) {
                        array_push($resultRow, $yearOfPublication); // YOP
                    }
                    if (in_array('Access_Type', $this->attributesToShow)) {
                        array_push($resultRow, self::ACCESS_TYPE); // Access_Type
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
            $i++;
        } while ($i < count($yearsOfPublication));

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
            'groupId' => 'default',
            'value' => $metricTypes,
        ]);

        $attributesToShow = ['Data_Type', 'Access_Method', 'Section_Type', 'Access_Type', 'YOP'];
        $attributesToShowOptions = [];
        foreach ($attributesToShow as $attributeToShow) {
            $attributesToShowOptions[] = ['value' => $attributeToShow, 'label' => $attributeToShow];
        }
        $formFields[] = new FieldOptions('attributes_to_show', [
            'label' => __('manager.statistics.counterR5Report.settings.attributesToShow'),
            'options' => $attributesToShowOptions,
            'groupId' => 'default',
            'value' => [],
        ]);

        $formFields[] = new FieldText('yop', [
            'label' => __('manager.statistics.counterR5Report.settings.yop'),
            'description' => __('manager.statistics.counterR5Report.settings.date.yop.description'),
            'size' => 'small',
            'isMultilingual' => false,
            'isRequired' => false,
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
