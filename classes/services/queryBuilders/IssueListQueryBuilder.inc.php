<?php

/**
 * @file classes/services/QueryBuilders/IssueListQueryBuilder.php
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

use PKP\Services\QueryBuilders\BaseQueryBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;

class IssueListQueryBuilder extends BaseQueryBuilder {

	/** @var int Context ID */
	protected $contextId = null;

	/** @var array list of columns for query */
	protected $columns = array();

	/** @var string order by column */
	protected $orderColumn = 'i.date_published';

	/** @var string order by direction */
	protected $orderDirection = 'DESC';

	/** @var boolean return published issues */
	protected $isPublished = null;

	/** @var array return issues in volume(s) */
	protected $volumes = null;

	/** @var array return issues with number(s) */
	protected $numbers = null;

	/** @var array return issues with year(s) */
	protected $years = null;

	/** @var bool whether to return only a count of results */
	protected $countOnly = null;

	/**
	 * Constructor
	 *
	 * @param $contextId int context ID
	 */
	public function __construct($contextId) {
		parent::__construct();
		$this->contextId = $contextId;
	}

	/**
	 * Set result order column and direction
	 *
	 * @param string $column
	 * @param string $direction
	 *
	 * @return \OJS\Services\QueryBuilders\SubmissionListQueryBuilder
	 */
	public function orderBy($column, $direction = 'DESC') {
		if ($column === 'lastModified') {
			$this->orderColumn = 'i.last_modified';
		} else {
			$this->orderColumn = 'i.date_published';
		}
		$this->orderDirection = $direction;
		return $this;
	}

	/**
	 * Set published filter
	 *
	 * @param boolean $isPublished
	 *
	 * @return \OJS\Services\QueryBuilders\SubmissionListQueryBuilder
	 */
	public function filterByPublished($isPublished) {
		$this->isPublished = $isPublished;
		return $this;
	}

	/**
	 * Set volumes filter
	 *
	 * @param array $volumes
	 *
	 * @return \OJS\Services\QueryBuilders\SubmissionListQueryBuilder
	 */
	public function filterByVolumes($volumes) {
		if (!is_null($volumes) && !is_array($volumes)) {
			$volumes = array($volumes);
		}
		$this->volumes = $volumes;
		return $this;
	}

	/**
	 * Set numbers filter
	 *
	 * @param array $numbers
	 *
	 * @return \OJS\Services\QueryBuilders\SubmissionListQueryBuilder
	 */
	public function filterByNumbers($numbers) {
		if (!is_null($numbers) && !is_array($numbers)) {
			$numbers = array($numbers);
		}
		$this->numbers = $numbers;
		return $this;
	}

	/**
	 * Set years filter
	 *
	 * @param array $years
	 *
	 * @return \OJS\Services\QueryBuilders\SubmissionListQueryBuilder
	 */
	public function filterByYears($years) {
		if (!is_null($years) && !is_array($years)) {
			$years = array($years);
		}
		$this->years = $years;
		return $this;
	}

	/**
	 * Whether to return only a count of results
	 *
	 * @param bool $enable
	 *
	 * @return \OJS\Services\QueryBuilders\SubmissionListQueryBuilder
	 */
	public function countOnly($enable = true) {
		$this->countOnly = $enable;
		return $this;
	}

	/**
	 * Execute query builder
	 *
	 * @return object Query object
	 */
	public function get() {
		$this->columns[] = 'i.*';
		$q = Capsule::table('issues as i')
					->where('i.journal_id','=', $this->contextId)
					->leftJoin('issue_settings as is', 'i.journal_id', '=', 'is.issue_id')
					->orderBy($this->orderColumn, $this->orderDirection)
					->groupBy('i.issue_id');

		// published
		if (!is_null($this->isPublished)) {
			$q->where('i.published', $this->isPublished ? 1 : 0);
		}

		// volumes
		if (!is_null($this->volumes)) {
			$q->whereIn('i.volume', $this->volumes);
		}

		// numbers
		if (!is_null($this->numbers)) {
			$q->whereIn('i.number', $this->numbers);
		}

		// years
		if (!is_null($this->years)) {
			$q->whereIn('i.year', $this->years);
		}

		// Allow third-party query statements
		\HookRegistry::call('Issue::getIssues::queryObject', array(&$q, $this));

		if (!empty($this->countOnly)) {
			$q->select(Capsule::raw('count(*) as issue_count'));
		} else {
			$q->select($this->columns);
		}

		return $q;
	}
}
