<?php

/**
 * @file api/v1/stats/StatsEditorialController.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsEditorialController
 *
 * @ingroup api_v1_stats
 *
 * @brief Controller class to handle API requests for editorial statistics.
 *
 */

namespace APP\API\v1\stats\editorial;

class StatsEditorialController extends \PKP\API\v1\stats\editorial\PKPStatsEditorialController
{
    /** @var string The name of the section ids query param for this application */
    public $sectionIdsQueryParam = 'sectionIds';

    public function getSectionIdsQueryParam()
    {
        return $this->sectionIdsQueryParam;
    }
}
