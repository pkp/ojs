<?php

/**
 * @file api/v1/stats/StatsEditorialHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StatsEditorialHandler
 * @ingroup api_v1_stats
 *
 * @brief Handle API requests for editorial statistics.
 *
 */

import('lib.pkp.api.v1.stats.editorial.PKPStatsEditorialHandler');

class StatsEditorialHandler extends PKPStatsEditorialHandler {
  /** @var string The name of the section ids query param for this application */
  public $sectionIdsQueryParam = 'sectionIds';
}