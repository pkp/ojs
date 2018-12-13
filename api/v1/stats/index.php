<?php

/**
 * @defgroup api_v1_stats Statistics API requests
 */

/**
 * @file api/v1/stats/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_stats
 * @brief Handle requests for statistics API functions.
 *
 */

import('api.v1.stats.StatsHandler');
return new StatsHandler();
