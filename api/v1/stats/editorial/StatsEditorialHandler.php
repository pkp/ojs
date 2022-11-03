<?php

/**
 * @file api/v1/stats/StatsEditorialHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsEditorialHandler
 * @ingroup api_v1_stats
 *
 * @brief Handle API requests for editorial statistics.
 *
 */

namespace APP\API\v1\stats\editorial;

class StatsEditorialHandler extends \PKP\API\v1\stats\editorial\PKPStatsEditorialHandler
{
    /** @var string The name of the section ids query param for this application */
    public $sectionIdsQueryParam = 'sectionIds';
}
