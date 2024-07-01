<?php

/**
 * @file classes/sushi/IR.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IR
 *
 * @ingroup sushi
 *
 * @brief COUNTER R5 SUSHI Item Master Report (IR).
 *
 */

namespace APP\sushi;

use APP\core\Services;
use APP\facades\Repo;
use Illuminate\Support\Collection;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldText;
use PKP\statistics\PKPStatisticsHelper;
use PKP\sushi\CounterR5Report;

class IR extends CounterR5Report
{
    /** Section type */
    public const SECTION_TYPE = 'Article';

    /** Data type */
    public const DATA_TYPE = 'Article';

    /** Parent data type */
    public const PARENT_DATA_TYPE = 'Journal';

    /** The requested item i.e. article ID the report should be created for. */
    public int $itemId = 0;

    /** Article contributor */
    public string $itemContributor;

    /** If the details about the parent should be included */
    protected string $includeParentDetails = 'False';

    /**
     * Get report name defined by COUNTER.
     */
    public function getName(): string
    {
        return 'Item Master Report';
    }

    /**
     * Get report ID defined by COUNTER.
     */
    public function getID(): string
    {
        return 'IR';
    }

    /**
     * Get report description.
     */
    public function getDescription(): string
    {
        return __('sushi.reports.ir.description');
    }

    /**
     * Get API path defined by COUNTER for this report.
     */
    public function getAPIPath(): string
    {
        return 'reports/ir';
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
            'item_contributor',
            'metric_type',
            'data_type',
            'yop',
            'access_type',
            'access_method',
            'attributes_to_show',
            'include_component_details',
            'include_parent_details',
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
                'supportedValues' => [],
                'param' => 'item_id'
            ],
            [
                'name' => 'Access_Type',
                'supportedValues' => [self::ACCESS_TYPE],
                'param' => 'access_type'
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
     * Data_Type, Access_Method, Parent_Data_Type, and Access_Type are currently always the same for this report,
     * so they will only be displayed and not considered for metrics aggregation.
     * The only attributes considered for metrics aggregation are Attributes_To_Show=YOP and granularity=Month.
     */
    public function getSupportedAttributes(): array
    {
        return [
            [
                'name' => 'Attributes_To_Show',
                'supportedValues' => ['Article_Version', 'Authors', 'Access_Method', 'Access_Type', 'Data_Type', 'Publication_Date', 'YOP'],
                'param' => 'attributes_to_show'
            ],
            [
                'name' => 'Include_Component_Details',
                'supportedValues' => ['False'],
                'param' => 'include_component_details'
            ],
            [
                'name' => 'Include_Parent_Details',
                'supportedValues' => ['False', 'True'],
                'param' => 'include_parent_details'
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
                case 'Item_Id':
                    $this->itemId = (int) $filter['Value'];
                    break;
            }
        }
    }

    /**
     * Set attributes based on the requested parameters.
     */
    public function setAttributes(array $attributes): void
    {
        parent::setAttributes($attributes);
        foreach ($attributes as $attribute) {
            switch ($attribute['Name']) {
                case 'Include_Parent_Details':
                    $this->includeParentDetails = $attribute['Value'];
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
        if ($this->itemId > 0) {
            $params['submissionIds'] = [$this->itemId];
        }
        // do not consider metric_type filter now, but for display

        $statsService = Services::get('sushiStats');
        $metricsQB = $statsService->getQueryBuilder($params);
        // consider attributes to group the metrics by
        $groupBy = ['m.' . PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID];
        $orderBy = ['m.' . PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID => 'asc'];
        // The report is on submission level, and relationship between submission_id and YOP is one to one,
        // so no need to group or order by YOP -- it is enough to group and order by submission_id
        if ($this->granularity == 'Month') {
            $groupBy[] = 'm.' . PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH;
            $orderBy['m.' . PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH] = 'asc';
        }
        $metricsQB = $metricsQB->getSum($groupBy);
        foreach ($orderBy as $column => $direction) {
            $metricsQB = $metricsQB->orderBy($column, $direction);
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

        $items = [];
        $resultsGroupedBySubmission = $results->groupBy('submission_id');

        foreach ($resultsGroupedBySubmission as $submissionId => $submissionResults) {
            // Get the submission properties
            $submission = Repo::submission()->get($submissionId);
            if (!$submission || !$submission->getOriginalPublication()) {
                break;
            }
            $currentPublication = $submission->getCurrentPublication();
            $submissionLocale = $submission->getData('locale');
            $itemTitle = $currentPublication->getLocalizedTitle($submissionLocale);

            $item = [
                'Title' => $itemTitle,
                'Platform' => $this->platformName,
                'Publisher' => $this->context->getData('publisherInstitution'),
            ];
            $item['Item_ID'][] = [
                'Type' => 'Proprietary',
                'Value' => $this->platformId . ':' . $submissionId,
            ];
            $doi = $currentPublication->getDoi();
            if (isset($doi)) {
                $item['Item_ID'][] = [
                    'Type' => 'DOI',
                    'Value' => $doi,
                ];
            }
            if ($this->includeParentDetails == 'True') {
                $parentItem['Item_Name'] = $this->context->getName($this->context->getPrimaryLocale());
                $parentItem['Item_ID'] = [
                    ['Type' => 'Proprietary', 'Value' => $this->platformId . ':' . $this->context->getId()]
                ];
                if (null !== $this->context->getData('onlineIssn')) {
                    $parentItem['Item_ID'][] = [
                        'Type' => 'Online_ISSN',
                        'Value' => $this->context->getData('onlineIssn'),
                    ];
                }
                if (null !== $this->context->getData('printIssn')) {
                    $parentItem['Item_ID'][] = [
                        'Type' => 'Print_ISSN',
                        'Value' => $this->context->getData('printIssn'),
                    ];
                }
                $parentItem['Data_Type'] = self::PARENT_DATA_TYPE;
                $item['Item_Parent'] = $parentItem;
            }

            $datePublished = $submission->getOriginalPublication()->getData('datePublished');
            foreach ($this->attributesToShow as $attributeToShow) {
                if ($attributeToShow == 'Data_Type') {
                    $item['Data_Type'] = self::DATA_TYPE;
                } elseif ($attributeToShow == 'Access_Type') {
                    $item['Access_Type'] = self::ACCESS_TYPE;
                } elseif ($attributeToShow == 'Access_Method') {
                    $item['Access_Method'] = self::ACCESS_METHOD;
                } elseif ($attributeToShow == 'YOP') {
                    $item['YOP'] = date('Y', strtotime($datePublished));
                } elseif ($attributeToShow == 'Publication_Date') {
                    $item['Item_Dates'] = [
                        ['Type' => 'Publication_Date', 'Value' => $datePublished]
                    ];
                } elseif ($attributeToShow == 'Authors') {
                    $authors = $currentPublication->getData('authors');
                    $itemContributors = [];
                    foreach ($authors as $author) {
                        $itemContributor['Type'] = 'Author';
                        $itemContributor['Name'] = $author->getFullName(true, false, $submissionLocale);
                        $orcid = $author->getOrcid();
                        if (!empty($orcid)) {
                            $itemContributor['Identifier'] = $orcid;
                        }
                        $itemContributors[] = $itemContributor;
                    }
                    if (!empty($itemContributors)) {
                        $item['Item_Contributors'] = $itemContributors;
                    }
                } elseif ($attributeToShow == 'Article_Version') {
                    $item['Item_Attributes'] = [
                        ['Type' => 'Article_Version', 'Value' => 'VoR']
                    ];
                }
            }

            $performances = [];
            foreach ($submissionResults as $result) {
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
        }

        return $items;
    }

    /** Get TSV report column names */
    public function getTSVColumnNames(): array
    {
        $columnRow = ['Item', 'Publisher', 'Publisher ID', 'Platform'];

        if (in_array('Authors', $this->attributesToShow)) {
            array_push($columnRow, 'Authors');
        }
        if (in_array('Publication_Date', $this->attributesToShow)) {
            array_push($columnRow, 'Publication_Date');
        }
        if (in_array('Article_Version', $this->attributesToShow)) {
            array_push($columnRow, 'Article_Version');
        }

        array_push($columnRow, 'DOI', 'Proprietary_ID', 'ISBN', 'Print_ISSN', 'Online_ISSN', 'URI');

        if ($this->includeParentDetails == 'True') {
            array_push(
                $columnRow,
                'Parent_Title',
                'Parent_Authors',
                'Parent_Publication_Date',
                'Parent_Article_Version',
                'Parent_Data_Type',
                'Parent_DOI',
                'Parent_Proprietary_ID',
                'Parent_ISBN',
                'Parent_Print_ISSN',
                'Parent_Online_ISSN',
                'Parent_URI'
            );
        }

        if (in_array('Data_Type', $this->attributesToShow)) {
            array_push($columnRow, 'Data_Type');
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

        $resultRows = [];
        $resultsGroupedBySubmission = $results->groupBy('submission_id');

        foreach ($resultsGroupedBySubmission as $submissionId => $submissionResults) {
            $results = collect($submissionResults);

            // get total numbers for every metric type
            $metricsTotal['Total_Item_Investigations'] = $results->pluck('metric_investigations')->sum();
            $metricsTotal['Unique_Item_Investigations'] = $results->pluck('metric_investigations_unique')->sum();
            $metricsTotal['Total_Item_Requests'] = $results->pluck('metric_requests')->sum();
            $metricsTotal['Unique_Item_Requests'] = $results->pluck('metric_requests_unique')->sum();

            // filter here by requested metric types
            foreach ($this->metricTypes as $metricType) {
                // if the total numbers for the given metric type > 0,
                // construct the result row
                if ($metricsTotal[$metricType] > 0) {
                    $submission = Repo::submission()->get($submissionId);
                    if (!$submission || !$submission->getOriginalPublication()) {
                        break;
                    }
                    $currentPublication = $submission->getCurrentPublication();
                    $submissionLocale = $submission->getData('locale');
                    $datePublished = $submission->getOriginalPublication()->getData('datePublished');

                    $resultRow = [
                        $currentPublication->getLocalizedTitle($submissionLocale), // Item
                        $this->context->getData('publisherInstitution'), // Publisher
                        '', // Publisher ID
                        $this->platformName, // Platform
                    ];

                    if (in_array('Authors', $this->attributesToShow)) {
                        $authors = $currentPublication->getData('authors');
                        $authorRowValue = '';
                        foreach ($authors as $author) {
                            $authorRowValue = $author->getFullName(true, false, $submissionLocale);
                            $orcid = $author->getOrcid();
                            if (!empty($orcid)) {
                                $authorRowValue .= '(ORCID:' . $orcid . ')';
                            }
                        }
                        array_push($resultRow, $authorRowValue); // Authors
                    }

                    if (in_array('Publication_Date', $this->attributesToShow)) {
                        array_push($resultRow, $datePublished); // Publication_Date
                    }

                    if (in_array('Article_Version', $this->attributesToShow)) {
                        array_push($resultRow, 'VoR'); // Article_Version
                    }

                    $doi = $currentPublication->getDoi() ?? '';
                    array_push($resultRow, $doi); // DOI

                    array_push($resultRow, $this->platformId . ':' . $submissionId); // Proprietary_ID
                    array_push($resultRow, '', '', '', ''); // ISBN, Print_ISSN, Online_ISSN, URI

                    if ($this->includeParentDetails == 'True') {
                        array_push(
                            $resultRow,
                            $this->context->getName($this->context->getPrimaryLocale()), // Parent_Title
                            '', // Parent_Authors
                            '', // Parent_Publication_Date
                            '', // Parent_Article_Version
                            self::PARENT_DATA_TYPE, // Parent_Data_Type
                            '', // Parent_DOI
                            $this->platformId . ':' . $this->context->getId(), // Parent_Proprietary_ID
                            '', //Parent_ISBN
                        );
                        $printIssn = $this->context->getData('printIssn') ?? '';
                        array_push($resultRow, $printIssn); // Parent_Print_ISSN

                        $onlineIssn = $this->context->getData('onlineIssn') ?? '';
                        array_push($resultRow, $onlineIssn); // Parent_Online_ISSN

                        array_push($resultRow, ''); // Parent_URI
                    }

                    if (in_array('Data_Type', $this->attributesToShow)) {
                        array_push($resultRow, self::DATA_TYPE); // Data_Type
                    }
                    if (in_array('YOP', $this->attributesToShow)) {
                        array_push($resultRow, date('Y', strtotime($datePublished))); // YOP
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
                            $result = $submissionResults->firstWhere('month', '=', $month);
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

        $attributesToShow = ['Article_Version', 'Authors', 'Access_Method', 'Access_Type', 'Data_Type', 'Publication_Date', 'YOP'];
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

        $formFields[] = new FieldText('yop', [
            'label' => __('manager.statistics.counterR5Report.settings.yop'),
            'description' => __('manager.statistics.counterR5Report.settings.date.yop.description'),
            'size' => 'small',
            'isMultilingual' => false,
            'isRequired' => false,
            'groupId' => 'default',
        ]);

        $formFields[] = new FieldText('item_id', [
            'label' => __('manager.statistics.counterR5Report.settings.itemId'),
            'size' => 'small',
            'isMultilingual' => false,
            'isRequired' => false,
            'groupId' => 'default',
        ]);

        $formFields[] = new FieldOptions('include_parent_details', [
            'label' => __('manager.statistics.counterR5Report.settings.includeParentDetails'),
            'options' => [
                ['value' => true, 'label' => __('manager.statistics.counterR5Report.settings.includeParentDetails')],
            ],
            'value' => false,
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
