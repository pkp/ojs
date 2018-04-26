<?php

/**
 * @file classes/services/QueryBuilders/SubmissionListQueryBuilder.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionListQueryBuilder
 * @ingroup query_builders
 *
 * @brief Submission list Query builder
 */

namespace OJS\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;

class SubmissionListQueryBuilder extends \PKP\Services\QueryBuilders\PKPSubmissionListQueryBuilder {

	/** @var int|array Section ID(s) */
	protected $sectionIds = null;

	/**
	 * Set section filter
	 *
	 * @param int|array $sectionIds
	 *
	 * @return \OJS\Services\QueryBuilders\SubmissionListQueryBuilder
	 */
	public function filterBySections($sectionIds) {
		$this->sectionIds = $sectionIds;
		return $this;
	}

	/**
	 * Execute additional actions for app-specific query objects
	 *
	 * @param object Query object
	 * @return object Query object
	 */
	public function appGet($q) {
		$primaryLocale = \AppLocale::getPrimaryLocale();
		$locale = \AppLocale::getLocale();

		$this->columns[] = Capsule::raw('aggregation.section_title');
		$this->columns[] = Capsule::raw('aggregation.section_abbrev');

		$q->leftJoin(Capsule::raw("(
										SELECT s.submission_id,
											COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
											COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
										FROM submissions AS s
											LEFT JOIN section_settings AS stpl ON s.section_id = stpl.section_id and stpl.setting_name = 'section_title' and stpl.locale = '{$primaryLocale}'
											LEFT JOIN section_settings AS stl ON s.section_id = stl.section_id and stl.setting_name = 'section_title' and stl.locale = '{$locale}'
											LEFT JOIN section_settings AS sapl ON s.section_id = sapl.section_id and sapl.setting_name = 'section_abbrev' and sapl.locale = '{$primaryLocale}'
											LEFT JOIN section_settings AS sal ON s.section_id = sal.section_id and sal.setting_name = 'section_abbrev' and sal.locale = '{$locale}'
										GROUP BY s.submission_id, stl.setting_value, stpl.setting_value, sal.setting_value, sapl.setting_value
									) AS aggregation"),
									function($join) {
										$join->on('s.submission_id', '=', 'aggregation.submission_id');
									});

		if (!empty($this->sectionIds)) {
			$q->whereIn('s.section_id', $this->sectionIds);
		}

		return $q;
	}
}
