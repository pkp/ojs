<?php

/**
 * @defgroup pages_stats Statistics Pages
 */

/**
 * @file pages/stats/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_stats
 * @brief Handle requests for statistics pages.
 *
 */

import('pages.stats.StatsHandler');
define('HANDLER_CLASS', 'StatsHandler');
