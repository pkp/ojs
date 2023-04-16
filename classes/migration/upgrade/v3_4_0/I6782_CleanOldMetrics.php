<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_CleanOldMetrics.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_CleanOldMetrics
 *
 * @brief Clean the old metrics:
 *  delete migrated entries with the metric type ojs::counter from the DB table metrics,
 *  move back the orphaned metrics from the temporary metrics_tmp,
 *  rename or delete the DB table metrics,
 *  delete DB table usage_stats_temporary_records.
 */

namespace APP\migration\upgrade\v3_4_0;

class I6782_CleanOldMetrics extends \PKP\migration\upgrade\v3_4_0\I6782_CleanOldMetrics
{
    protected function getMetricType(): string
    {
        return 'ojs::counter';
    }
}
