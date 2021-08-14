<?php

/**
* @file classes/statistics/StatisticsHelper.inc.php
*
* Copyright (c) 2013-2021 Simon Fraser University
* Copyright (c) 2003-2021 John Willinsky
* Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
*
* @class StatisticsHelper
* @ingroup statistics
*
* @brief Statistics helper class.
*/

namespace APP\statistics;

use PKP\statistics\PKPStatisticsHelper;

class StatisticsHelper extends PKPStatisticsHelper
{
    public const STATISTICS_DIMENSION_ISSUE_ID = self::STATISTICS_DIMENSION_ASSOC_OBJECT_ID;

    /**
     * @see PKPStatisticsHelper::getAppColumnTitle()
     */
    protected function getAppColumnTitle($column)
    {
        switch ($column) {
            case self::STATISTICS_DIMENSION_SUBMISSION_ID:
                return __('common.publication');
            case self::STATISTICS_DIMENSION_PKP_SECTION_ID:
                return __('section.section');
            case self::STATISTICS_DIMENSION_CONTEXT_ID:
                return __('context.context');
            default:
                assert(false);
        }
    }

    /**
     * @see PKPStatisticsHelper::getReportColumnsArray()
     */
    protected function getReportColumnsArray()
    {
        return array_merge(
            parent::getReportColumnsArray(),
            [self::STATISTICS_DIMENSION_ISSUE_ID => __('issue.issue')]
        );
    }

    /**
     * @see PKPStatisticsHelper::getReportObjectTypesArray()
     */
    protected function getReportObjectTypesArray()
    {
        $objectTypes = parent::getReportObjectTypesArray();
        $objectTypes = $objectTypes + [
            ASSOC_TYPE_JOURNAL => __('context.context'),
            ASSOC_TYPE_SECTION => __('section.section'),
            ASSOC_TYPE_ISSUE => __('issue.issue'),
            ASSOC_TYPE_ISSUE_GALLEY => __('editor.issues.galley'),
            ASSOC_TYPE_SUBMISSION => __('common.publication'),
            ASSOC_TYPE_SUBMISSION_FILE => __('submission.galleyFiles')
        ];

        return $objectTypes;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\statistics\StatisticsHelper', '\StatisticsHelper');
    define('STATISTICS_DIMENSION_ISSUE_ID', StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID);
}
