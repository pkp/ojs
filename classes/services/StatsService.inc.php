<?php

/**
 * @file classes/services/StatsService.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPStatsService
 * @ingroup services
 *
 * @brief Helper class that encapsulates statistics business logic
 */

namespace APP\Services;

class StatsService extends \PKP\Services\PKPStatsService {

	/**
	 * Apply the sectionIds query param to the QueryBuilder
	 */
	protected function getQueryBuilder($args = []) {
		$statsQB = parent::getQueryBuilder($args);

		if (!empty(($args['sectionIds']))) {
			$statsQB->filterBySections($args['sectionIds']);
		}

		return $statsQB;
	}
}