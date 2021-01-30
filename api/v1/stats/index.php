<?php

/**
 * @defgroup api_v1_stats Publication statistics API requests
 */

/**
 * @file api/v1/stats/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_stats
 * @brief Handle API requests for publication statistics
 *
 */

$requestPath = Application::get()->getRequest()->getRequestPath();
if (strpos($requestPath, '/stats/publications')) {
  import('api.v1.stats.publications.StatsPublicationHandler');
  return new StatsPublicationHandler();
} elseif (strpos($requestPath, '/stats/editorial')) {
	import('api.v1.stats.editorial.StatsEditorialHandler');
	return new StatsEditorialHandler();
} elseif (strpos($requestPath, '/stats/users')) {
	import('lib.pkp.api.v1.stats.users.PKPStatsUserHandler');
	return new PKPStatsUserHandler();
}
