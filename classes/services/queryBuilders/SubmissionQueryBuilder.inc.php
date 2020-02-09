<?php

/**
 * @file classes/services/QueryBuilders/SubmissionQueryBuilder.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
		if (!is_null($sectionIds) && !is_array($sectionIds)) {
			$sectionIds = [$sectionIds];
		}
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

		if (!empty($this->sectionIds)) {
			$sectionIds = $this->sectionIds;
			$q->leftJoin('publications as section_p', 'section_p.submission_id', '=', 's.submission_id')
				->whereIn('section_p.section_id', $sectionIds);
		}

		return $q;
	}
}
