<?php

/**
 * @file classes/services/QueryBuilders/SubmissionQueryBuilder.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionQueryBuilder
 * @ingroup query_builders
 *
 * @brief Submission list Query builder
 */

namespace APP\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;

class SubmissionQueryBuilder extends \PKP\Services\QueryBuilders\PKPSubmissionQueryBuilder {

	/** @var int|array Section ID(s) */
	protected $sectionIds = null;

	/**
	 * Set section filter
	 *
	 * @param int|array $sectionIds
	 *
	 * @return \APP\Services\QueryBuilders\SubmissionQueryBuilder
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

		$this->columns[] = Capsule::raw('COALESCE(stl.setting_value, stpl.setting_value) AS section_title');
		$this->columns[] = Capsule::raw('COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev');

		$q->groupBy(Capsule::raw('COALESCE(stl.setting_value, stpl.setting_value)'));
		$q->groupBy(Capsule::raw('COALESCE(sal.setting_value, sapl.setting_value)'));

		$q->leftJoin('section_settings as stpl', function($join) use($primaryLocale) {
			$join->on('s.section_id', '=', Capsule::raw('stpl.section_id'));
			$join->on('stpl.setting_name', '=', Capsule::raw("'section_title'"));
			$join->on('stpl.locale', '=', Capsule::raw("'{$primaryLocale}'"));
		});

		$q->leftJoin('section_settings as stl', function($join) use($locale) {
			$join->on('s.section_id', '=', Capsule::raw('stl.section_id'));
			$join->on('stl.setting_name', '=', Capsule::raw("'section_title'"));
			$join->on('stl.locale', '=', Capsule::raw("'{$locale}'"));
		});

		$q->leftJoin('section_settings as sapl', function($join) use($primaryLocale) {
			$join->on('s.section_id', '=', Capsule::raw('sapl.section_id'));
			$join->on('sapl.setting_name', '=', Capsule::raw("'section_abbrev'"));
			$join->on('sapl.locale', '=', Capsule::raw("'{$primaryLocale}'"));
		});

		$q->leftJoin('section_settings as sal', function($join) use($locale) {
			$join->on('s.section_id', '=', Capsule::raw('sal.section_id'));
			$join->on('sal.setting_name', '=', Capsule::raw("'section_abbrev'"));
			$join->on('sal.locale', '=', Capsule::raw("'{$locale}'"));
		});

		if (!empty($this->sectionIds)) {
			$q->whereIn('s.section_id', $this->sectionIds);
		}

		return $q;
	}
}
