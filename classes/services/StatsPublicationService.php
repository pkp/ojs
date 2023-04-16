<?php

/**
 * @file classes/services/StatsPublicationService.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsPublicationService
 *
 * @ingroup services
 *
 * @brief Helper class that encapsulates publication statistics business logic
 */

namespace APP\services;

use APP\services\queryBuilders\StatsPublicationQueryBuilder;

class StatsPublicationService extends \PKP\services\PKPStatsPublicationService
{
    protected function getAppSpecificFilters(StatsPublicationQueryBuilder &$statsQB, array $args = []): void
    {
        if (!empty(($args['issueIds']))) {
            $statsQB->filterByIssues($args['issueIds']);
        }
    }
}
