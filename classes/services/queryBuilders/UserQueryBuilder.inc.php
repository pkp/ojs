<?php

/**
 * @file classes/services/QueryBuilders/UserQueryBuilder.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserQueryBuilder
 * @ingroup query_builders
 *
 * @brief Submission list Query builder
 */

namespace APP\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;

class UserQueryBuilder extends \PKP\Services\QueryBuilders\PKPUserQueryBuilder {

	/** @var int Assigned as editor to this section id */
	protected $assignedToSectionId = null;

	/** @var int Assigned as editor to this category id */
	protected $assignedToCategoryId = null;

	/**
	 * Limit results to users assigned as editors to this section
	 *
	 * @param $sectionId int
	 *
	 * @return \PKP\Services\QueryBuilders\UserQueryBuilder
	 */
	public function assignedToSection($sectionId) {
		$this->assignedToSectionId = $sectionId;
		return $this;
	}

	/**
	 * Limit results to users assigned as editors to this category
	 *
	 * @param $categoryId int
	 *
	 * @return \PKP\Services\QueryBuilders\UserQueryBuilder
	 */
	public function assignedToCategory($categoryId) {
		$this->assignedToCategoryId = $categoryId;
		return $this;
	}

	/**
	 * Execute additional actions for app-specific query objects
	 *
	 * @param object Query object
	 * @return object Query object
	 */
	public function appGet($q) {

		if (!is_null($this->assignedToSectionId)) {
			$sectionId = $this->assignedToSectionId;

			$q->leftJoin('subeditor_submission_group as ssg', function($table) use ($sectionId) {
				$table->on('u.user_id', '=', 'ssg.user_id');
				$table->on('ssg.assoc_type', '=', Capsule::raw((int) ASSOC_TYPE_SECTION));
				$table->on('ssg.assoc_id', '=', Capsule::raw((int) $sectionId));
			});

			$q->whereNotNull('ssg.assoc_id');
		}
		if (!is_null($this->assignedToCategoryId)) {
			$categoryId = $this->assignedToCategoryId;

			$q->leftJoin('subeditor_submission_group as ssg', function($table) use ($categoryId) {
				$table->on('u.user_id', '=', 'ssg.user_id');
				$table->on('ssg.assoc_type', '=', Capsule::raw((int) ASSOC_TYPE_CATEGORY));
				$table->on('ssg.assoc_id', '=', Capsule::raw((int) $categoryId));
			});

			$q->whereNotNull('ssg.assoc_id');
		}

		return $q;
	}
}
