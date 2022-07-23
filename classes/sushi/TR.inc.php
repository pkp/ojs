<?php

/**
* @file classes/sushi/TR.inc.php
*
* Copyright (c) 2022 Simon Fraser University
* Copyright (c) 2022 John Willinsky
* Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
*
* @class TR
* @ingroup sushi
*
* @brief COUNTER R5 SUSHI Title Master Report (TR).
*
*/

namespace APP\sushi;

use APP\core\Services;
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

    /**
     * Get report items
     */
    public function getReportItems(): array
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

        // If YOP is requested attribute to show,
        // group results by YOP
        $resultsGroupedByYOP = $yearsOfPublication = $items = [];
        if (in_array('YOP', $this->attributesToShow)) {
            foreach ($results as $result) {
                if (!in_array($result->YOP, $yearsOfPublication)) {
                    $yearsOfPublication[] = $result->YOP;
                }
                $resultsGroupedByYOP[$result->YOP][] = $result;
            }
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
}
