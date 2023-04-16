<?php

/**
 * @file classes/statistics/StatisticsHelper.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatisticsHelper
 *
 * @ingroup statistics
 *
 * @brief Statistics helper class.
 */

namespace APP\statistics;

use PKP\statistics\PKPStatisticsHelper;

class StatisticsHelper extends PKPStatisticsHelper
{
    public const STATISTICS_DIMENSION_ISSUE_GALLEY_ID = 'issue_galley_id';
    public const STATISTICS_DIMENSION_ISSUE_ID = 'issue_id';

    // Metrics
    public const STATISTICS_METRIC_INVESTIGATIONS = 'metric_investigations';
    public const STATISTICS_METRIC_INVESTIGATIONS_UNIQUE = 'metric_investigations_unique';
    public const STATISTICS_METRIC_REQUESTS = 'metric_requests';
    public const STATISTICS_METRIC_REQUESTS_UNIQUE = 'metric_requests_unique';

    /**
     * Get COUNTER DB tables metrics columns
     */
    public static function getCounterMetricsColumns(): array
    {
        return [
            self::STATISTICS_METRIC_INVESTIGATIONS,
            self::STATISTICS_METRIC_INVESTIGATIONS_UNIQUE,
            self::STATISTICS_METRIC_REQUESTS,
            self::STATISTICS_METRIC_REQUESTS_UNIQUE,
        ];
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\statistics\StatisticsHelper', '\StatisticsHelper');
    define('STATISTICS_DIMENSION_ISSUE_ID', StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID);
}
