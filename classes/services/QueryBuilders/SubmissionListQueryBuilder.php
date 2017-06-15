<?php

/**
 * @file classes/services/QueryBuilders/SubmissionListQueryBuilder.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionListQueryBuilder
 * @ingroup query_builders
 *
 * @brief Submission list Query builder
 */

namespace App\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;

class SubmissionListQueryBuilder extends PKPSubmissionListQueryBuilder {

	/** @var int|array Section ID(s) */
	protected $sectionIds = null;

	/**
	 * Set section filter
	 *
	 * @param int|array $sectionIds
	 *
	 * @return \App\Services\QueryBuilders\SubmissionListQueryBuilder
	 */
	public function filterBySections($sectionIds) {
		if (!is_null($sectionIds) && !is_array($sectionIds)) {
			$sectionIds = array($sectionIds);
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
			$q->whereIn('s.section_id', $this->sectionIds);
		}

		return $q;
	}
}
