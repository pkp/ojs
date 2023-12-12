<?php

/**
 * @file classes/sushi/PR_P1.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PR_P1
 *
 * @ingroup sushi
 *
 * @brief COUNTER R5 SUSHI Platform Usage Report (PR_P1).
 *
 */

namespace APP\sushi;

class PR_P1 extends PR
{
    /** Requested metric types */
    public array $metricTypes = [
        'Total_Item_Requests',
        'Unique_Item_Requests'
    ];

    /**
     * Get report name defined by COUNTER.
     */
    public function getName(): string
    {
        return 'Platform Usage';
    }

    /**
     * Get report ID defined by COUNTER.
     */
    public function getID(): string
    {
        return 'PR_P1';
    }

    /**
     * Get report description.
     */
    public function getDescription(): string
    {
        return __('sushi.reports.pr_p1.description');
    }

    /**
     * Get API path defined by COUNTER for this report.
     */
    public function getAPIPath(): string
    {
        return 'reports/pr_p1';
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
            '_', // for ajax requests
        ];
    }

    /**
     * Get filters supported by this report.
     */
    public function getSupportedFilters(): array
    {
        return [];
    }

    /**
     * Get attributes supported by this report.
     */
    public function getSupportedAttributes(): array
    {
        return [];
    }

    /**
     * Set filters based on the requested parameters.
     */
    public function setFilters(array $filters): void
    {
        // The filters predefined for this report
        $predefinedFilters = [
            [
                'Name' => 'Metric_Type',
                'Value' => 'Total_Item_Requests|Unique_Item_Requests'
            ],
            [
                'Name' => 'Access_Method',
                'Value' => self::ACCESS_METHOD
            ],
        ];
        $this->filters = array_merge($filters, $predefinedFilters);
    }

    /**
     * Set attributes based on the requested parameters.
     * No attributes are supported by this report.
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = [];
    }

    /** Get report specific form fields */
    public static function getReportSettingsFormFields(): array
    {
        return parent::getCommonReportSettingsFormFields();
    }
}
