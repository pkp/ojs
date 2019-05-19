<?php

/**
 * @file classes/services/QueryBuilders/UserQueryBuilder.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
	 * Execute additional actions for app-specific query objects
	 *
	 * @param object Query object
	 * @return object Query object
	 */
	public function appGet($q) {

		if (!is_null($this->assignedToSectionId)) {
			$sectionId = $this->assignedToSectionId;

			$q->leftJoin('section_editors as se', function($table) use ($sectionId) {
				$table->on('u.user_id', '=', 'se.user_id');
				$table->on('se.section_id', '=', Capsule::raw((int) $sectionId));
			});

			$q->whereNotNull('se.section_id');
		}

		return $q;
	}
}
