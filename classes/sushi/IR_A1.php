<?php

/**
 * @file classes/sushi/IR_A1.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IR_A1
 *
 * @ingroup sushi
 *
 * @brief COUNTER R5 SUSHI Journal Article Requests (IR_A1).
 *
 */

namespace APP\sushi;

class IR_A1 extends IR
{
    /**
     * Get report name defined by COUNTER.
     */
    public function getName(): string
    {
        return 'Journal Article Requests';
    }

    /**
     * Get report ID defined by COUNTER.
     */
    public function getID(): string
    {
        return 'IR_A1';
    }

    /**
     * Get report description.
     */
    public function getDescription(): string
    {
        return __('sushi.reports.ir_a1.description');
    }

    /**
     * Get API path defined by COUNTER for this report.
     */
    public function getAPIPath(): string
    {
        return 'reports/ir_a1';
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
        $this->filters = $filters;
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
                'Value' => 'Total_Item_Requests|Unique_Item_Requests'
            ],
            [
                'Name' => 'Access_Method',
                'Value' => self::ACCESS_METHOD
            ],
            [
                'Name' => 'Data_Type',
                'Value' => self::DATA_TYPE
            ],
            [
                'Name' => 'Parent_Data_Type',
                'Value' => self::PARENT_DATA_TYPE
            ]
        ];
        $this->filters = array_merge($filters, $predefinedFilters);
    }

    /**
     * Set attributes based on the requested parameters.
     */
    public function setAttributes(array $attributes): void
    {
        $predefinedAttributes = [
            [
                'Name' => 'Attributes_To_Show',
                'Value' => 'Article_Version|Authors|Access_Type|Publication_Date'
            ],
            [
                'Name' => 'Include_Parent_Details',
                'Value' => 'True'
            ],
        ];
        parent::setAttributes($predefinedAttributes);
    }

    /** Get report specific form fields */
    public static function getReportSettingsFormFields(): array
    {
        return parent::getCommonReportSettingsFormFields();
    }
}
