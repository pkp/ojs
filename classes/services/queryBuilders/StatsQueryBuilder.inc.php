<?php

/**
 * @file classes/services/QueryBuilders/PKPStatsQueryBuilder.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPStatsQueryBuilder
 * @ingroup query_builders
 *
 * @brief Wrap the stats query builder with functions specific to OJS.
 */

namespace APP\Services\QueryBuilders;

class StatsQueryBuilder extends \PKP\Services\QueryBuilders\PKPStatsQueryBuilder {

	/**
	 * Set the section/series ids to get records for
	 *
	 * @param array|int $sectionIds
	 * @return \PKP\Services\QueryBuilders\PKPStatsQueryBuilder
	 */
	public function filterBySections($sectionIds) {
		$this->sectionIds = is_array($sectionIds) ? $sectionIds : [$sectionIds];
		return $this;
  }
}