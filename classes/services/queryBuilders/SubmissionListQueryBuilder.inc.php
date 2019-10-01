<?php

/**
 * @file classes/services/QueryBuilders/SubmissionListQueryBuilder.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
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

		$subQ = Capsule::table('submissions as s')
					->select('s.submission_id',
						Capsule::raw('COALESCE(stl.setting_value, stpl.setting_value) as section_title'),
						Capsule::raw('COALESCE(sal.setting_value, sapl.setting_value) as section_abbrev'))
					->leftJoin('section_settings as stpl',
								function($join) use ($primaryLocale) {
									$join->on('s.section_id', '=', 'stpl.section_id')
										->on('stpl.setting_name', '=', Capsule::raw("'section_title'"))
										->on('stpl.locale', '=', Capsule::raw("'$primaryLocale'"));
								})
					->leftJoin('section_settings as stl',
								function($join) use ($locale) {
									$join->on('s.section_id', '=', 'stl.section_id')
										->on('stl.setting_name', '=', Capsule::raw("'section_title'"))
										->on('stl.locale', '=', Capsule::raw("'$locale'"));
								})
					->leftJoin('section_settings as sapl',
								function($join) use ($primaryLocale) {
									$join->on('s.section_id', '=', 'sapl.section_id')
										->on('sapl.setting_name', '=', Capsule::raw("'section_abbrev'"))
										->on('sapl.locale', '=', Capsule::raw("'$primaryLocale'"));
								})
					->leftJoin('section_settings as sal',
								function($join) use ($locale) {
									$join->on('s.section_id', '=', 'sal.section_id')
										->on('sal.setting_name', '=', Capsule::raw("'section_abbrev'"))
										->on('sal.locale', '=', Capsule::raw("'$locale'"));
								})
					->groupBy('s.submission_id',
						'stl.setting_value',
						'stpl.setting_value',
						'sal.setting_value',
						'sapl.setting_value');

		$q->leftJoin(Capsule::raw("({$subQ->toSql()}) as aggregation"),
									function($join) {
										$join->on('s.submission_id', '=', 'aggregation.submission_id');
									});

		if (!empty($this->sectionIds)) {
			$q->whereIn('s.section_id', $this->sectionIds);
		}

		return $q;
	}
}
