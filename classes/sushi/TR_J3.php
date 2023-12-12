<?php

/**
 * @file classes/sushi/TR_J3.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TR_J3
 *
 * @ingroup sushi
 *
 * @brief COUNTER R5 SUSHI Journal Usage by Access Type Report (TR_J3_J3).
 *
 */

namespace APP\sushi;

class TR_J3 extends TR
{
    /**
     * Get report name defined by COUNTER.
     */
    public function getName(): string
    {
        return 'Journal Usage by Access Type';
    }

    /**
     * Get report ID defined by COUNTER.
     */
    public function getID(): string
    {
        return 'TR_J3';
    }

    /**
     * Get report description.
     */
    public function getDescription(): string
    {
        return __('sushi.reports.tr_j3.description');
    }

    /**
     * Get API path defined by COUNTER for this report.
     */
    public function getAPIPath(): string
    {
        return 'reports/tr_j3';
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
        foreach ($filters as $filter) {
            switch ($filter['Name']) {
                case 'Begin_Date':
                    $this->beginDate = $filter['Value'];
                    break;
                case 'End_Date':
                    $this->endDate = $filter['Value'];
                    break;
            }
        }
        // The filters predefined for this report
        $predefinedFilters = [
            [
                'Name' => 'Metric_Type',
                'Value' => 'Total_Item_Investigations|Unique_Item_Investigations|Total_Item_Requests|Unique_Item_Requests'
            ],
            [
                'Name' => 'Access_Method',
                'Value' => self::ACCESS_METHOD
            ],
            [
                'Name' => 'Data_Type',
                'Value' => self::DATA_TYPE
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
