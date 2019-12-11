<?php
/**
 * @file classes/services/StatsEditorialService.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPStatsEditorialService
 * @ingroup services
 *
 * @brief Helper class that encapsulates business logic for getting
 *   editorial stats
 */
namespace APP\Services;

class StatsEditorialService extends \PKP\Services\PKPStatsEditorialService {
	/**
	 * Process the sectionIds param when getting the query builder
	 *
	 * @param array $args
	 */
	protected function _getQueryBuilder($args = []) {
		$statsQB = parent::_getQueryBuilder($args);
		if (!empty(($args['sectionIds']))) {
			$statsQB->filterBySections($args['sectionIds']);
		}
		return $statsQB;
	}
}